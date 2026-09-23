<?php

namespace jeremykenedy\Slack\Laravel\Tests;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Collection;
use jeremykenedy\Slack\Laravel\Fakes\MessageFake;
use jeremykenedy\Slack\Laravel\Fakes\SlackFake;
use PHPUnit\Framework\TestCase;

class SlackFakeTest extends TestCase
{
    public function test_it_records_messages_without_sending_http_requests()
    {
        $http = $this->getMockBuilder(Guzzle::class)->getMock();
        $http->expects($this->never())->method(method_exists(Guzzle::class, 'post') ? 'post' : '__call');
        $fake = new SlackFake('https://example.invalid/webhook', [], $http);

        $message = $fake->to('#releases')->send('Release ready');

        $this->assertInstanceOf(Collection::class, $fake->messages);
        $this->assertCount(1, $fake->messages);
        $this->assertSame($message, $fake->messages->first());
        $this->assertSame('Release ready', $message->getText());
    }

    public function test_it_keeps_client_defaults()
    {
        $fake = new SlackFake('https://example.invalid/webhook', [
            'channel' => '#releases',
            'username' => 'Release bot',
            'icon' => 'https://example.invalid/icon.png',
            'link_names' => true,
            'unfurl_links' => true,
            'unfurl_media' => false,
            'allow_markdown' => false,
            'markdown_in_attachments' => ['text', 'title'],
        ]);

        $message = $fake->send('Release ready');

        $this->assertSame('https://example.invalid/webhook', $fake->getEndpoint());
        $this->assertSame('#releases', $message->getChannel());
        $this->assertSame('Release bot', $message->getUsername());
        $this->assertSame('https://example.invalid/icon.png', $message->getIcon());
        $this->assertTrue($fake->getLinkNames());
        $this->assertTrue($fake->getUnfurlLinks());
        $this->assertFalse($fake->getUnfurlMedia());
        $this->assertFalse($message->getAllowMarkdown());
        $this->assertSame(['text', 'title'], $message->getMarkdownInAttachments());
    }

    public function test_message_assertion_accepts_a_sent_message()
    {
        $fake = new SlackFake('');
        $fake->send('Release ready');
        $fake->assertMessageSent();
        $fake->assertMessageSent(function ($messages, $extra) {
            return $extra === null && $messages->first()->getText() === 'Release ready';
        });
    }

    public function test_message_assertion_rejects_an_empty_history()
    {
        $fake = new SlackFake('');

        $this->expectException(AssertionFailedError::class);

        $fake->assertMessageSent();
    }

    public function test_message_assertion_rejects_a_failed_callback()
    {
        $fake = new SlackFake('');
        $fake->send('Release ready');

        $this->expectException(AssertionFailedError::class);

        $fake->assertMessageSent(function () {
            return false;
        });
    }

    public function test_channel_assertion_accepts_a_matching_message()
    {
        $fake = new SlackFake('');
        $fake->to('#releases')->send('Release ready');

        $fake->assertMessageSentTo('#releases');
    }

    public function test_channel_assertion_rejects_messages_sent_elsewhere()
    {
        $fake = new SlackFake('');
        $fake->to('#general')->send('Release ready');

        $this->expectException(AssertionFailedError::class);

        $fake->assertMessageSentTo('#releases');
    }

    public function test_channel_assertion_rejects_an_empty_history()
    {
        $fake = new SlackFake('');

        $this->expectException(AssertionFailedError::class);

        $fake->assertMessageSentTo('#releases');
    }

    public function test_channel_callback_cannot_hide_a_missing_channel()
    {
        $fake = new SlackFake('');
        $fake->to('#general')->send('Release ready');

        $this->expectException(AssertionFailedError::class);

        $fake->assertMessageSentTo('#releases', function () {
            return true;
        });
    }

    public function test_channel_callback_receives_only_matching_messages()
    {
        $fake = new SlackFake('');
        $fake->to('#general')->send('First');
        $fake->to('#releases')->send('Second');
        $fake->to('#releases')->send('Third');

        $fake->assertMessageSentTo('#releases', function ($messages, $extra) {
            return $extra === null && $messages->count() === 2
                && $messages->first()->getText() === 'Second';
        });
    }

    public function test_channel_assertion_rejects_a_failed_callback()
    {
        $fake = new SlackFake('');
        $fake->to('#releases')->send('Release ready');

        $this->expectException(AssertionFailedError::class);

        $fake->assertMessageSentTo('#releases', function () {
            return false;
        });
    }

    public function test_assert_true_keeps_the_callback_api()
    {
        $fake = new SlackFake('');
        $fake->assertTrue(function () {
            return true;
        });

        $this->expectException(AssertionFailedError::class);

        $fake->assertTrue(function () {
            return false;
        });
    }

    public function test_message_fake_remains_usable_directly()
    {
        $fake = new SlackFake('');
        $message = new MessageFake($fake);
        $message->to('#releases')->send('Release ready');

        $this->assertSame($message, $fake->messages->first());
    }
}
