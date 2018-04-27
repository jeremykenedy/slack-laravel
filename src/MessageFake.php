<?php

namespace jeremykenedy\Slack\Fakes;

use jeremykenedy\Slack\Message;

class MessageFake extends Message
{
    public function __construct(SlackFake $client)
    {
        $this->client = $client;
    }
}
