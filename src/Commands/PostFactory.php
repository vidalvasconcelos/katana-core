<?php

namespace Katana\Commands;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Katana\PostBuilder;

final class PostFactory extends Command
{
    public static function make(Filesystem $filesystem): Command
    {
        $command = new Command('post');
        $command->setDescription('Generate a blog post.');
        $command->addArgument('title', InputArgument::OPTIONAL, 'The Post Title', 'My New Post');
        $command->addOption('m', null, InputOption::VALUE_NONE, 'Create a Markdown template file');
        $command->setCode(static function (InputInterface $input, OutputInterface $output) use ($filesystem): int {
            $post = new PostBuilder($filesystem, $input->getArgument('title'), $input->getOption('m'));
            $post->build();
            $output->writeln(sprintf("<info>Post \"%s\" was generated successfully.</info>", $input->getArgument('title')));
            return 0;
        });

        return $command;
    }
}
