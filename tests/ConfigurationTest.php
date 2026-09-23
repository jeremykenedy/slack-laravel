<?php

namespace jeremykenedy\Slack\Laravel\Tests;

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_markdown_fields_accept_both_documented_formats()
    {
        $cases = [
            'text,title' => ['text', 'title'],
            "'text','title'" => ['text', 'title'],
            ' "text" , "title" ' => ['text', 'title'],
            'text, ,title,' => ['text', 'title'],
            'text' => ['text'],
            '' => [],
        ];

        foreach ($cases as $value => $expected) {
            $this->withMarkdownFields($value, function ($config) use ($expected) {
                $this->assertSame($expected, $config['markdown_in_attachments']);
            });
        }
    }

    public function test_missing_markdown_fields_produce_an_empty_list()
    {
        $this->withMarkdownFields(null, function ($config) {
            $this->assertSame([], $config['markdown_in_attachments']);
        });
    }

    public function test_configuration_can_be_cached_and_loaded_without_environment_values()
    {
        $this->withMarkdownFields('text,title', function ($config) {
            $path = tempnam(sys_get_temp_dir(), 'slack-config-');

            try {
                file_put_contents($path, '<?php return '.var_export($config, true).';');
                $cached = require $path;

                $this->assertSame($config, $cached);
            } finally {
                unlink($path);
            }
        });
    }

    private function withMarkdownFields($value, $callback)
    {
        if (! function_exists('env')) {
            $this->markTestSkipped('Laravel 4 does not use the Laravel 5 environment configuration.');
        }

        $name = 'DEFAULT_SLACK_MARKDOWN_FIELDS';
        $original = getenv($name);
        $server = isset($_SERVER[$name]) ? $_SERVER[$name] : null;
        $environment = isset($_ENV[$name]) ? $_ENV[$name] : null;
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($value === null ? $name : $name.'='.$value);

        if ($value !== null) {
            $_ENV[$name] = $value;
        }

        try {
            $callback(require dirname(__DIR__).'/src/config/config.php');
        } finally {
            putenv($original === false ? $name : $name.'='.$original);
            unset($_SERVER[$name], $_ENV[$name]);

            if ($server !== null) {
                $_SERVER[$name] = $server;
            }

            if ($environment !== null) {
                $_ENV[$name] = $environment;
            }
        }
    }
}
