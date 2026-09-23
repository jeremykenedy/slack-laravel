<?php

require dirname(__DIR__).'/vendor/autoload.php';

if (! class_exists('PHPUnit\\Framework\\TestCase')) {
    class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\\Framework\\TestCase');
}

class_alias(
    class_exists('PHPUnit_Framework_AssertionFailedError')
        ? 'PHPUnit_Framework_AssertionFailedError'
        : 'PHPUnit\\Framework\\AssertionFailedError',
    'jeremykenedy\\Slack\\Laravel\\Tests\\AssertionFailedError'
);
