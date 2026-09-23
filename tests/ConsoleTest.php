<?php

namespace jeremykenedy\Slack\Laravel\Tests;

use Illuminate\Console\Application as Artisan;
use Illuminate\Filesystem\Filesystem;
use jeremykenedy\Slack\Laravel\Console\InstallCommand;
use jeremykenedy\Slack\Laravel\Tests\Fixtures\CustomConfigApplication;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleTest extends PackageTestCase
{
    public function test_install_and_update_are_registered_and_publish_configuration()
    {
        foreach (['slack:install', 'slack:update'] as $name) {
            $this->withApplication(function ($app) use ($name) {
                $artisan = new Artisan($app, $app['events'], $app->version());
                $tester = new CommandTester($artisan->find($name));

                $this->assertSame(0, $tester->execute([], ['interactive' => false]));
                $this->assertSame(
                    file_get_contents(dirname(__DIR__).'/src/config/config.php'),
                    file_get_contents($app->configPath().'/slack.php')
                );
                $this->assertTrue(strpos($tester->getDisplay(), 'DEFAULT_SLACK_WEBHOOK_ENDPOINT') !== false);
                $this->assertTrue(strpos($tester->getDisplay(), 'config:cache') !== false);
            });
        }
    }

    public function test_install_and_update_preserve_existing_configuration_exactly()
    {
        foreach (['slack:install', 'slack:update'] as $name) {
            $this->withApplication(function ($app) use ($name) {
                $path = $app->configPath().'/slack.php';
                $config = "<?php\nreturn ['endpoint' => 'https://example.invalid/private'];\n";
                file_put_contents($path, $config);
                $artisan = new Artisan($app, $app['events'], $app->version());
                $tester = new CommandTester($artisan->find($name));

                $this->assertSame(0, $tester->execute([], ['interactive' => false]));
                $this->assertSame($config, file_get_contents($path));
                $this->assertTrue(strpos($tester->getDisplay(), 'left unchanged') !== false);
                $this->assertFalse(strpos($tester->getDisplay(), 'private') !== false);
            });
        }
    }

    public function test_install_can_be_run_twice_without_changing_configuration()
    {
        $this->withApplication(function ($app) {
            $artisan = new Artisan($app, $app['events'], $app->version());
            $tester = new CommandTester($artisan->find('slack:install'));
            $this->assertSame(0, $tester->execute([], ['interactive' => false]));
            $path = $app->configPath().'/slack.php';
            $original = file_get_contents($path);

            $this->assertSame(0, $tester->execute([], ['interactive' => false]));
            $this->assertSame($original, file_get_contents($path));
        });
    }

    public function test_install_creates_a_missing_configuration_directory()
    {
        $this->withApplication(function ($app) {
            rmdir($app->configPath());
            $artisan = new Artisan($app, $app['events'], $app->version());
            $tester = new CommandTester($artisan->find('slack:install'));

            $this->assertSame(0, $tester->execute([], ['interactive' => false]));
            $this->assertTrue(is_file($app->configPath().'/slack.php'));
        });
    }

    public function test_install_reports_a_failed_copy()
    {
        $this->withApplication(function ($app) {
            $files = $this->getMockBuilder(Filesystem::class)->getMock();
            $files->method('exists')->willReturn(false);
            $files->method('isDirectory')->willReturn(true);
            $files->expects($this->once())->method('copy')->willReturn(false);
            $command = new InstallCommand($files);
            $command->setLaravel($app);
            $tester = new CommandTester($command);

            $this->assertSame(1, $tester->execute([], ['interactive' => false]));
            $this->assertTrue(strpos($tester->getDisplay(), 'Unable to publish') !== false);
            $this->assertFalse(file_exists($app->configPath().'/slack.php'));
        });
    }

    public function test_install_respects_the_application_configuration_path()
    {
        $this->withApplication(function ($app) {
            $directory = $app->basePath().'/custom-config';

            $artisan = new Artisan($app, $app['events'], $app->version());
            $tester = new CommandTester($artisan->find('slack:install'));

            $this->assertSame(0, $tester->execute([], ['interactive' => false]));
            $this->assertTrue(is_file($directory.'/slack.php'));
            $this->assertFalse(is_file($app->basePath().'/config/slack.php'));
        }, [], CustomConfigApplication::class);
    }

    public function test_install_reports_a_failed_directory_creation()
    {
        $this->withApplication(function ($app) {
            $files = $this->getMockBuilder(Filesystem::class)->getMock();
            $files->method('exists')->willReturn(false);
            $files->method('isDirectory')->willReturn(false);
            $files->method('makeDirectory')->willReturn(false);
            $files->expects($this->never())->method('copy');
            $command = new InstallCommand($files);
            $command->setLaravel($app);
            $tester = new CommandTester($command);

            $this->assertSame(1, $tester->execute([], ['interactive' => false]));
            $this->assertTrue(strpos($tester->getDisplay(), 'Unable to create') !== false);
        });
    }
}
