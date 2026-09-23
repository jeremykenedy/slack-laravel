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
        $client = static::getFacadeRoot();

        static::swap(new SlackFake($client->getEndpoint(), [
            'channel' => $client->getDefaultChannel(),
            'username' => $client->getDefaultUsername(),
            'icon' => $client->getDefaultIcon(),
            'link_names' => $client->getLinkNames(),
            'unfurl_links' => $client->getUnfurlLinks(),
            'unfurl_media' => $client->getUnfurlMedia(),
            'allow_markdown' => $client->getAllowMarkdown(),
            'markdown_in_attachments' => $client->getMarkdownInAttachments(),
        ]));
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
