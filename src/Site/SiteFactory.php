<?php

namespace Katana\Site;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

final class SiteFactory
{
    public static function make(Factory $viewFactory, Filesystem $filesystem): Command
    {
        $command = new Command('build');
        $command->setDescription('Generate the site static files.');
        $command->addOption('env', null, InputOption::VALUE_REQUIRED, 'Application Environment.', 'default');
        $command->addOption('force', null, InputOption::VALUE_NONE, 'Clear the cache before building.');
        $command->setCode(new CommandHandler($viewFactory, $filesystem));

        return $command;
    }
}
