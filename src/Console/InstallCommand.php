<?php

namespace jeremykenedy\Slack\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laravel\Lumen\Application;

class InstallCommand extends Command
{
    protected $name = 'slack:install';

    protected $description = 'Publish Slack configuration without replacing an existing file';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle()
    {
        $path = function_exists('config_path')
            ? config_path('slack.php')
            : $this->laravel->basePath('config/slack.php');
        if ($this->files->exists($path)) {
            $this->info('Slack configuration already exists and was left unchanged.');

            return 0;
        }

        if (! $this->publishConfiguration($path)) {
            return 1;
        }

        $this->info('Slack configuration published to '.$path.'.');
        $this->info('Set DEFAULT_SLACK_WEBHOOK_ENDPOINT in your environment.');
        if (! $this->laravel instanceof Application) {
            $this->info('If configuration is cached, rebuild it with php artisan config:cache.');
        }

        return 0;
    }

    public function fire()
    {
        return $this->handle();
    }

    private function publishConfiguration($path)
    {
        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)
            && ! $this->files->makeDirectory($directory, 0755, true, true)) {
            $this->error('Unable to create the configuration directory. Check its permissions.');

            return false;
        }

        if (! $this->files->copy(dirname(__DIR__).'/config/config.php', $path)) {
            $this->error('Unable to publish Slack configuration. Check directory permissions.');

            return false;
        }

        return true;
    }
}
