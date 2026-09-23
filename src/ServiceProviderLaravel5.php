<?php

namespace jeremykenedy\Slack\Laravel;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use jeremykenedy\Slack\Client;
use jeremykenedy\Slack\Laravel\Console\InstallCommand;
use jeremykenedy\Slack\Laravel\Console\UpdateCommand;
use Laravel\Lumen\Application;

class ServiceProviderLaravel5 extends LaravelServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app instanceof Application) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/config.php' => config_path('slack.php'),
        ], 'slacklaravel');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app instanceof Application) {
            $this->app->configure('slack');
        }

        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'slack');

        $this->app->singleton('jeremykenedy.slack', function ($app) {
            return new Client(
                $app['config']->get('slack.endpoint'),
                [
                    'channel' => $app['config']->get('slack.channel'),
                    'username' => $app['config']->get('slack.username'),
                    'icon' => $app['config']->get('slack.icon'),
                    'link_names' => $app['config']->get('slack.link_names'),
                    'unfurl_links' => $app['config']->get('slack.unfurl_links'),
                    'unfurl_media' => $app['config']->get('slack.unfurl_media'),
                    'allow_markdown' => $app['config']->get('slack.allow_markdown'),
                    'markdown_in_attachments' => $app['config']->get('slack.markdown_in_attachments'),
                ],
                new Guzzle
            );
        });

        $this->app->bind('jeremykenedy\Slack\Client', 'jeremykenedy.slack');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, UpdateCommand::class]);
        }
    }
}
