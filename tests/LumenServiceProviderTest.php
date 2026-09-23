<?php

namespace jeremykenedy\Slack\Laravel\Tests;

use Illuminate\Console\Application as Artisan;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade as LaravelFacade;
use jeremykenedy\Slack\Client;
use jeremykenedy\Slack\Laravel\Facade;
use jeremykenedy\Slack\Laravel\ServiceProvider;
use Laravel\Lumen\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LumenServiceProviderTest extends TestCase
{
    public function test_lumen_resolves_the_shared_client_without_enabling_facades()
    {
        $this->withApplication(function ($app, $provider) {
            $client = $app->make(Client::class);

            $this->assertInstanceOf(Client::class, $client);
            $this->assertSame($client, $app->make('jeremykenedy.slack'));
            $this->assertSame($client, $app->make(Client::class));
            $this->assertSame(['jeremykenedy.slack'], $provider->provides());
            $this->assertSame('Robot', $client->getDefaultUsername());
            $this->assertTrue($client->getAllowMarkdown());
        });
    }

    public function test_lumen_loads_application_settings_before_merging_defaults()
    {
        $this->withApplication(function ($app) {
            $client = $app->make(Client::class);

            $this->assertSame('https://example.invalid/webhook', $client->getEndpoint());
            $this->assertSame('#releases', $client->getDefaultChannel());
            $this->assertNull($client->getDefaultUsername());
            $this->assertFalse($client->getAllowMarkdown());
            $this->assertSame(['text'], $client->getMarkdownInAttachments());
            $this->assertTrue($client->getUnfurlMedia());
        }, [
            'endpoint' => 'https://example.invalid/webhook',
            'channel' => '#releases',
            'username' => null,
            'allow_markdown' => false,
            'markdown_in_attachments' => ['text'],
        ]);
    }

    public function test_lumen_preserves_settings_that_were_already_loaded()
    {
        $this->withApplication(function ($app) {
            $app->configure('slack');

            $this->assertSame('#runtime', $app->make(Client::class)->getDefaultChannel());
        }, ['channel' => '#configured'], function ($app) {
            $app->configure('slack');
            $app['config']->set('slack.channel', '#runtime');
        });
    }

    public function test_lumen_facades_can_fake_messages_with_application_defaults()
    {
        $this->withApplication(function ($app) {
            $app->withFacades();
            Facade::fake();
            $message = Facade::send('Release ready');
            Facade::assertMessageSentTo('#releases');

            $this->assertSame(Facade::getFacadeRoot(), $app->make(Client::class));
            $this->assertSame('#releases', $message->getChannel());
            $this->assertSame('https://example.invalid/webhook', Facade::getEndpoint());
        }, ['channel' => '#releases', 'endpoint' => 'https://example.invalid/webhook']);
    }

    public function test_lumen_commands_create_missing_configuration_and_are_repeatable()
    {
        foreach (['slack:install', 'slack:update'] as $name) {
            $this->withApplication(function ($app) use ($name) {
                rmdir($app->basePath('config'));
                $artisan = new Artisan($app, $app['events'], $app->version());
                $tester = new CommandTester($artisan->find($name));
                $path = $app->basePath('config/slack.php');

                $this->assertSame(0, $tester->execute([], ['interactive' => false]));
                $this->assertSame(file_get_contents(dirname(__DIR__).'/src/config/config.php'), file_get_contents($path));
                $this->assertFalse(strpos($tester->getDisplay(), 'config:cache') !== false);
                $this->assertSame(0, $tester->execute([], ['interactive' => false]));
                $this->assertTrue(strpos($tester->getDisplay(), 'left unchanged') !== false);
            });
        }
    }

    public function test_lumen_commands_preserve_existing_configuration_exactly()
    {
        foreach (['slack:install', 'slack:update'] as $name) {
            $this->withApplication(function ($app) use ($name) {
                $path = $app->basePath('config/slack.php');
                $original = file_get_contents($path);
                $artisan = new Artisan($app, $app['events'], $app->version());
                $tester = new CommandTester($artisan->find($name));

                $this->assertSame(0, $tester->execute([], ['interactive' => false]));
                $this->assertSame($original, file_get_contents($path));
                $this->assertTrue(strpos($tester->getDisplay(), 'left unchanged') !== false);
                $this->assertFalse(strpos($tester->getDisplay(), 'private') !== false);
            }, ['endpoint' => 'https://example.invalid/private']);
        }
    }

    private function withApplication($callback, $settings = null, $beforeRegister = null)
    {
        if (! class_exists(Application::class)) {
            $this->markTestSkipped('These tests require Lumen.');
        }

        $path = sys_get_temp_dir().'/slack-lumen-'.uniqid();
        $files = new Filesystem;
        $files->makeDirectory($path.'/config', 0755, true);

        if ($settings !== null) {
            $files->put($path.'/config/slack.php', '<?php return '.var_export($settings, true).';');
        }

        $reporting = error_reporting();
        $app = new Application($path);
        restore_error_handler();
        restore_exception_handler();
        error_reporting($reporting);
        LaravelFacade::clearResolvedInstances();

        try {
            if ($beforeRegister !== null) {
                $beforeRegister($app);
            }

            $provider = new ServiceProvider($app);
            $app->register($provider);

            if (method_exists($app, 'boot')) {
                $app->boot();
            }

            $callback($app, $provider);
        } finally {
            LaravelFacade::clearResolvedInstances();
            LaravelFacade::setFacadeApplication(null);
            $app->flush();
            $files->deleteDirectory($path);
        }
    }
}
