<?php

namespace jeremykenedy\Slack\Laravel\Tests;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use jeremykenedy\Slack\Client;
use jeremykenedy\Slack\Laravel\Facade;
use jeremykenedy\Slack\Laravel\Fakes\SlackFake;
use jeremykenedy\Slack\Laravel\ServiceProviderLaravel5;

class ServiceProviderTest extends PackageTestCase
{
    public function test_it_binds_the_client_as_a_singleton_with_the_existing_service_name()
    {
        $this->withApplication(function ($app, $provider) {
            $client = $app->make('jeremykenedy.slack');

            $this->assertInstanceOf(Client::class, $client);
            $this->assertSame($client, $app->make('jeremykenedy.slack'));
            $this->assertSame($client, $app->make(Client::class));
            $this->assertSame($client, Facade::getFacadeRoot());
            $this->assertSame(['jeremykenedy.slack'], $provider->provides());
        });
    }

    public function test_it_merges_defaults_without_replacing_application_configuration()
    {
        $this->withApplication(function ($app) {
            $client = $app->make(Client::class);

            $this->assertSame('https://example.invalid/webhook', $client->getEndpoint());
            $this->assertSame('#releases', $client->getDefaultChannel());
            $this->assertSame('Robot', $client->getDefaultUsername());
            $this->assertFalse($client->getUnfurlMedia());
            $this->assertFalse($client->getAllowMarkdown());
            $this->assertSame(['text'], $client->getMarkdownInAttachments());
        }, [
            'endpoint' => 'https://example.invalid/webhook',
            'channel' => '#releases',
            'unfurl_media' => false,
            'allow_markdown' => false,
            'markdown_in_attachments' => ['text'],
        ]);
    }

    public function test_it_keeps_null_webhook_defaults()
    {
        $this->withApplication(function ($app) {
            $client = $app->make(Client::class);

            $this->assertNull($client->getDefaultChannel());
            $this->assertNull($client->getDefaultUsername());
            $this->assertNull($client->getDefaultIcon());
        }, ['channel' => null, 'username' => null, 'icon' => null]);
    }

    public function test_it_preserves_the_outgoing_message_payload()
    {
        $this->withApplication(function ($app) {
            $client = $app->make(Client::class);
            $message = $client->createMessage()->setText('Release ready');

            $this->assertSame([
                'text' => 'Release ready',
                'channel' => '#releases',
                'username' => 'Release bot',
                'link_names' => 1,
                'unfurl_links' => true,
                'unfurl_media' => false,
                'mrkdwn' => false,
                'icon_url' => 'https://example.invalid/icon.png',
                'attachments' => [],
            ], $client->preparePayload($message));
        }, [
            'channel' => '#releases',
            'username' => 'Release bot',
            'icon' => 'https://example.invalid/icon.png',
            'link_names' => true,
            'unfurl_links' => true,
            'unfurl_media' => false,
            'allow_markdown' => false,
        ]);
    }

    public function test_the_existing_publish_tag_and_destination_are_preserved()
    {
        $this->withApplication(function ($app) {
            $paths = LaravelServiceProvider::pathsToPublish(ServiceProviderLaravel5::class, 'slacklaravel');

            $this->assertSame([
                dirname(__DIR__).'/src/config/config.php' => $app->configPath().'/slack.php',
            ], $paths);
            $this->assertFalse(file_exists($app->configPath().'/slack.php'));
        });
    }

    public function test_the_facade_fake_keeps_runtime_defaults_and_container_bindings()
    {
        $this->withApplication(function ($app) {
            $client = $app->make(Client::class);
            $client->setDefaultChannel('#runtime');
            $client->setDefaultUsername('Release bot');
            $client->setDefaultIcon('https://example.invalid/icon.png');
            $client->setLinkNames(true);
            $client->setUnfurlLinks(true);
            $client->setUnfurlMedia(false);
            $client->setAllowMarkdown(false);
            $client->setMarkdownInAttachments(['title']);

            Facade::fake();
            $message = Facade::send('Release ready');
            $fake = Facade::getFacadeRoot();

            $this->assertInstanceOf(SlackFake::class, $fake);
            $this->assertSame($fake, $app->make(Client::class));
            $this->assertSame($fake, $app->make('jeremykenedy.slack'));
            $this->assertSame('https://example.invalid/webhook', $fake->getEndpoint());
            $this->assertSame('#runtime', $message->getChannel());
            $this->assertSame('Release bot', $message->getUsername());
            $this->assertSame('https://example.invalid/icon.png', $message->getIcon());
            $this->assertTrue($fake->getLinkNames());
            $this->assertTrue($fake->getUnfurlLinks());
            $this->assertFalse($fake->getUnfurlMedia());
            $this->assertFalse($message->getAllowMarkdown());
            $this->assertSame(['title'], $message->getMarkdownInAttachments());
            Facade::assertMessageSentTo('#runtime');
        }, ['endpoint' => 'https://example.invalid/webhook']);
    }

    public function test_calling_fake_again_clears_the_message_history()
    {
        $this->withApplication(function () {
            Facade::fake();
            Facade::send('First');
            Facade::fake();

            $this->assertCount(0, Facade::getFacadeRoot()->messages);
        });
    }
}
