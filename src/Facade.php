<?php

namespace jeremykenedy\Slack\Laravel;

use jeremykenedy\Slack\Laravel\Fakes\SlackFake;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return void
     */
    public static function fake()
    {
        static::swap(new SlackFake(static::$app['config']->get('slack.endpoint')));
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'jeremykenedy.slack';
    }
}
