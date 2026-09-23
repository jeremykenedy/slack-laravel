<?php

namespace jeremykenedy\Slack\Laravel\Tests\Fixtures;

use Illuminate\Foundation\Application;

class CustomConfigApplication extends Application
{
    public function configPath($path = '')
    {
        return $this->basePath().'/custom-config'.($path === '' ? '' : '/'.$path);
    }
}
