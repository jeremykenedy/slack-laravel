# Upgrading

Existing applications can continue using the same package name, provider classes, facade alias, `jeremykenedy.slack` container binding, and `slacklaravel` publish tag. The PHP requirement remains `>=5.6.4`, and the Slack client dependency remains `~2.0`.

Composer updates do not publish files, rewrite configuration, install frontend packages, or send Slack messages. There are no views or frontend assets to migrate.

## Application configuration

On Laravel 5 and newer and on Lumen, `php artisan slack:install` and `php artisan slack:update` both create `config/slack.php` only when it is missing. Existing files remain unchanged, including custom settings and webhook endpoints. The commands do not change `.env` or clear configuration caches.

Review newly published defaults before deploying. If configuration is cached, rebuild it using `php artisan config:cache` after changing your environment or configuration.

The attachment Markdown setting now parses `text,title` and the previously documented `"'text','title'"` into separate fields. An unset value produces an empty array instead of an array containing `null`. An existing published configuration continues to behave as written. To enable the corrected behavior there, copy the `markdown_in_attachments` entry from `src/config/config.php`, or set an explicit array:

```php
'markdown_in_attachments' => ['text', 'title'],
```

## Application tests

`Slack::fake()` now keeps client defaults, including the default channel and any changes made to the client before faking. Message history still starts empty, and the assertion callback signature is unchanged.

`assertMessageSentTo('#channel')` now fails when messages were only sent elsewhere. Tests that depended on the earlier false positive must send to the expected channel or change their assertion.

The fake supports both legacy and namespaced PHPUnit assertion classes. Laravel 4 continues to use its existing `slack::` settings and service provider. The new publishing commands target Laravel 5 and newer and Lumen.

## Lumen

Lumen applications register `jeremykenedy\Slack\Laravel\ServiceProvider` manually in `bootstrap/app.php`. The provider detects Lumen before checking Laravel's version constant, loads the application's `slack` configuration, and fills in missing defaults. Existing calls to `$app->configure('slack')` remain valid.

The client binding, facade, and testing fakes work the same way on Lumen. Enable `$app->withFacades()` when using the facade; constructor injection does not require it. See [Lumen registration](../README.md#lumen-registration) for the bootstrap example.

Use `slack:install` or `slack:update` to publish missing configuration. These commands support Lumen without Laravel's `config_path()` helper and do not recommend the unavailable `config:cache` command.

## Runtime compatibility

Production source files retain PHP 5.6-compatible syntax. CI exercises every Laravel 5 minor release, each later major release through Laravel 13, and the Laravel 4.2 provider. Separate Lumen jobs cover every 5.x minor release and each later major release through Lumen 11. Current runtime testing includes PHP 8.5 and PHPUnit 13.

The upstream `jeremykenedy/slack` 2.4 client declares an implicitly nullable constructor argument. PHP 8.4 and newer emit a deprecation when that class is loaded. This does not fail the package tests, but applications that promote dependency deprecations to exceptions should account for it before upgrading PHP. The integration does not suppress that warning or patch vendor files.

Lumen 11's Illuminate console component also emits a null array-offset deprecation when commands write output on PHP 8.5. The command tests pass, but applications that convert deprecations to exceptions should test their framework dependencies before upgrading PHP.

Compatibility coverage for end-of-life runtimes is separate from security support. Use maintained PHP and Laravel versions for new deployments. The current-dependency CI job runs Composer's security audit without disabling security checks.

## Webhook behavior

Slack app webhooks use the channel, username, and icon configured by the Slack app. Legacy channel and identity override methods remain available, but Slack decides whether to honor them. See [Slack's incoming webhook documentation](https://docs.slack.dev/messaging/sending-messages-using-incoming-webhooks/).

This release does not change request timeouts or introduce automatic retries. Retrying a webhook request after an ambiguous network failure can send a duplicate message.
