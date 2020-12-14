<?php

declare(strict_types=1);

namespace Katana\Command;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\Builder\Site;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Build extends Command
{
    protected Factory $viewFactory;
    protected Filesystem $filesystem;

    public function __construct(Factory $viewFactory, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('build')
            ->setDescription('Generate the site static files.')
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Application Environment.', 'default')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Clear the cache before building.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $siteBuilder = new Site(
            $this->filesystem,
            $this->viewFactory,
            $input->getOption('env'),
            $input->getOption('force')
        );

        $siteBuilder->build();

        $output->writeln("<info>Site was generated successfully.</info>");
    }
}
