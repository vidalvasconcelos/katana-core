<?php

namespace Katana\Commands;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\SiteBuilder;

final class BuildFactory
{
    public static function make(Factory $viewFactory, Filesystem $filesystem): Command
    {
        $command = new Command('build');
        $command->setDescription('Generate the site static files.');
        $command->addOption('env', null, InputOption::VALUE_REQUIRED, 'Application Environment.', 'default');
        $command->addOption('force', null, InputOption::VALUE_NONE, 'Clear the cache before building.');
        $command->setCode(static function (InputInterface $input, OutputInterface $output) use ($viewFactory, $filesystem): int {
            $siteBuilder = new SiteBuilder($filesystem, $viewFactory, $input->getOption('env'), $input->getOption('force'));
            $siteBuilder->build();
            $output->writeln("<info>Site was generated successfully.</info>");
            return 0;
        });

        return $command;
    }
}
