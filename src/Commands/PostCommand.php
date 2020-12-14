<?php

namespace Katana\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\PostBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PostCommand extends Command
{
    /**
     * The FileSystem instance.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The FileSystem instance.
     *
     * @var Factory
     */
    private $viewFactory;

    /**
     * PostCommand constructor.
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
        $this->setName('post')
            ->setDescription('Generate a blog post.')
            ->addArgument('title', InputArgument::OPTIONAL, 'The Post Tilte', 'My New Post')
            ->addOption('m', null, InputOption::VALUE_NONE, 'Create a Markdown template file');
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
        $post = new PostBuilder(
            $this->filesystem,
            $input->getArgument('title'),
            $input->getOption('m')
        );

        $post->build();

        $output->writeln(

            sprintf("<info>Post \"%s\" was generated successfully.</info>", $input->getArgument('title'))
        );
    }
}
