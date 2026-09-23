<?php

namespace jeremykenedy\Slack\Laravel\Tests;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use jeremykenedy\Slack\Laravel\ServiceProvider;
use PHPUnit\Framework\TestCase;

abstract class PackageTestCase extends TestCase
{
    protected function withApplication($callback, array $config = [])
    {
        if (version_compare(Application::VERSION, '5.0', '<')) {
            $this->markTestSkipped('Laravel 4 uses the legacy provider tests.');
        }

        $path = sys_get_temp_dir().'/slack-laravel-'.uniqid();
        $files = new Filesystem;
        $files->makeDirectory($path.'/config', 0755, true);
        $files->makeDirectory($path.'/bootstrap/cache', 0755, true);

        $app = new Application($path);
        $app->instance('config', new Repository(['slack' => $config]));
        $app->instance('path.config', $path.'/config');
        $app->instance('files', $files);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);

        try {
            $provider = $app->register(ServiceProvider::class);
            $app->boot();
            $callback($app, $provider);
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
            $app->flush();
            $files->deleteDirectory($path);
        }
    }
}
