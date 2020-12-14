<?php

namespace Katana\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\SiteBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    /**
     * The FileSystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The view factory instance.
     *
     * @var Factory
     */
    protected $viewFactory;

    /**
     * BuildCommand constructor.
     *
     * @param Factory $viewFactory
     * @param Filesystem $filesystem
     */
    public function __construct(Factory $viewFactory, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->viewFactory = $viewFactory;

        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Generate the site static files.')
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Application Environment.', 'default')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Clear the cache before building.');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteBuilder = new SiteBuilder(
            $this->filesystem,
            $this->viewFactory,
            $input->getOption('env'),
            $input->getOption('force')
        );

        $siteBuilder->build();

        $output->writeln("<info>Site was generated successfully.</info>");
    }
}
