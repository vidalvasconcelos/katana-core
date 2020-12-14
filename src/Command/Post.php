<?php

declare(strict_types=1);

namespace Katana\Command;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\Builder\Post as Builder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Post extends Command
{
    private Factory $viewFactory;
    private Filesystem $filesystem;

    public function __construct(Factory $viewFactory, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('post')
            ->setDescription('Generate a blog post.')
            ->addArgument('title', InputArgument::OPTIONAL, 'The Post Tilte', 'My New Post')
            ->addOption('m', null, InputOption::VALUE_NONE, 'Create a Markdown template file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $post = new Builder(
            $this->filesystem,
            $input->getArgument('title'),
            $input->getOption('m')
        );

        $post->build();
        $output->writeln(sprintf(
            "<info>Post \"%s\" was generated successfully.</info>",
            $input->getArgument('title')
        ));
    }
}
