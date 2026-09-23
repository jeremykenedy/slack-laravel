<?php

namespace jeremykenedy\Slack\Laravel\Console;

class UpdateCommand extends InstallCommand
{
    protected $name = 'slack:update';

    protected $description = 'Publish missing Slack configuration while preserving existing settings';
}
