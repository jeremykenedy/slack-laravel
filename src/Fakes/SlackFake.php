<?php

namespace jeremykenedy\Slack\Laravel\Fakes;

use Illuminate\Support\Collection;
use jeremykenedy\Slack\Client;
use jeremykenedy\Slack\Message;

class SlackFake extends Client
{
    public $messages;

    public function __construct($endpoint, $attributes = [], $guzzle = null)
    {
        parent::__construct($endpoint, $attributes, $guzzle);

        $this->messages = new Collection;
    }

    public function assertTrue($callback)
    {
        $assert = class_exists('PHPUnit\\Framework\\Assert')
            ? 'PHPUnit\\Framework\\Assert'
            : 'PHPUnit_Framework_Assert';

        $assert::assertTrue($callback());
    }

    public function sendMessage(Message $message)
    {
        $this->messages->push($message);
    }

    public function assertMessageSent($callback = null)
    {
        $this->assertTrue(function () {
            return $this->messages->count() > 0;
        });

        if ($callback) {
            $this->assertTrue(function () use ($callback) {
                return $callback($this->messages, null);
            });
        }
    }

    public function assertMessageSentTo($channel, $callback = null)
    {
        $messages = $this->messages->filter(function ($message) use ($channel) {
            return $message->getChannel() == $channel;
        });

        $this->assertTrue(function () use ($messages) {
            return $messages->count() > 0;
        });

        if ($callback) {
            $this->assertTrue(function () use ($callback, $messages) {
                return $callback($messages, null);
            });
        }
    }
}
