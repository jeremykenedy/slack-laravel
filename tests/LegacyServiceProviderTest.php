<?php

namespace jeremykenedy\Slack\Laravel\Tests;

use Illuminate\Config\FileLoader;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade as LaravelFacade;
use jeremykenedy\Slack\Client;
use jeremykenedy\Slack\Laravel\Facade;
use jeremykenedy\Slack\Laravel\ServiceProvider;
use PHPUnit\Framework\TestCase;

class LegacyServiceProviderTest extends TestCase
{
    public function test_laravel_four_keeps_its_namespaced_settings_and_bindings()
    {
        $this->withApplication(function ($app) {
            $client = $app->make('jeremykenedy.slack');

            $this->assertSame($client, $app->make(Client::class));
            $this->assertSame($client, $app->make('jeremykenedy.slack'));
            $this->assertSame('https://example.invalid/webhook', $client->getEndpoint());
            $this->assertSame('#legacy', $client->getDefaultChannel());
            $this->assertSame('Release bot', $client->getDefaultUsername());
            $this->assertFalse($client->getAllowMarkdown());
            $this->assertFalse($client->getUnfurlMedia());
            $this->assertSame(['text'], $client->getMarkdownInAttachments());
        }, [
            'endpoint' => 'https://example.invalid/webhook',
            'channel' => '#legacy',
            'username' => 'Release bot',
            'allow_markdown' => false,
            'unfurl_media' => false,
            'markdown_in_attachments' => ['text'],
        ]);
    }

    public function test_laravel_four_keeps_fallbacks_for_missing_settings()
    {
        $this->withApplication(function ($app) {
            $client = $app->make(Client::class);

            $this->assertTrue($client->getAllowMarkdown());
            $this->assertTrue($client->getUnfurlMedia());
            $this->assertSame([], $client->getMarkdownInAttachments());
        });
    }

    public function test_laravel_four_can_fake_messages_with_legacy_defaults()
    {
        $this->withApplication(function ($app) {
            Facade::fake();
            Facade::send('Release ready');
            Facade::assertMessageSentTo('#legacy');

            $this->assertSame(Facade::getFacadeRoot(), $app->make(Client::class));
        }, ['channel' => '#legacy']);
    }

    private function withApplication($callback, array $settings = [])
    {
        if (version_compare(Application::VERSION, '5.0', '>=')) {
            $this->markTestSkipped('This provider is specific to Laravel 4.');
        }

        $files = new Filesystem;
        $config = new Repository(new FileLoader($files, __DIR__.'/fixtures'), 'testing');
        $app = new Application;
        $app->instance('config', $config);
        $app->instance('files', $files);
        $app->instance('path', __DIR__.'/fixtures');
        $app->instance('path.base', __DIR__.'/fixtures');

        foreach ($settings as $key => $value) {
            $config->set('slack::'.$key, $value);
        }

        LaravelFacade::clearResolvedInstances();
        LaravelFacade::setFacadeApplication($app);

        try {
            $provider = new ServiceProvider($app);
            $provider->register();
            $provider->boot();
            $callback($app);
        } finally {
            LaravelFacade::clearResolvedInstances();
            LaravelFacade::setFacadeApplication(null);
        }
    }
}
