<?php

declare(strict_types=1);

namespace Katana\Command;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\Builder\Site;
use Katana\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Build extends Command
{
    private Config $config;
    private Factory $factory;
    private Filesystem $filesystem;

    public function __construct(Factory $factory, Filesystem $filesystem, Config $config)
    {
        $this->config = $config;
        $this->factory = $factory;
        $this->filesystem = $filesystem;
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
        $env = $input->getOption('env');
        $force = $input->getOption('force');

        $siteBuilder = new Site($this->filesystem, $this->factory, $env, $force);
        $siteBuilder->build($this->config);

        $output->writeln("<info>Site was generated successfully.</info>");
    }
}
