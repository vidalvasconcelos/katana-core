<?php

namespace Katana\Post;

use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class PostFactory extends Command
{
    public static function make(Filesystem $filesystem, array $settings): Command
    {
        $command = new Command('post');
        $command->setDescription('Generate a blog post.');
        $command->addArgument('title', InputArgument::OPTIONAL, 'The Post Title', 'My New Post');
        $command->addOption('markdown', 'm', InputOption::VALUE_NONE, 'Create a Markdown template file');
        $command->setCode(new CommandHandler($filesystem, $settings));

        return $command;
    }
}
