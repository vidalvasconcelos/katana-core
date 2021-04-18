<?php

namespace Katana\Site;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandHandler
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Factory $factory, Filesystem $filesystem)
    {
        $this->factory = $factory;
        $this->filesystem = $filesystem;
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $siteBuilder = new SiteBuilder(
            $this->filesystem,
            $this->factory,
            $input->getOption('env'),
            $input->getOption('force'),
        );

        $siteBuilder->build();
        $output->writeln("<info>Site was generated successfully.</info>");
        return 0;
    }
}