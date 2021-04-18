<?php

namespace Katana\Post;

use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use const Katana\DIRECTORY_CONTENT;

final class CommandHandler
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $setting;

    public function __construct(Filesystem $filesystem, array $setting)
    {
        $this->filesystem = $filesystem;
        $this->setting = $setting;
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $post = new Builder(
            $this->setting[DIRECTORY_CONTENT],
            $input->getArgument('title'),
            $input->getOption('m'),
        );

        $this->filesystem->put($post->toFilepath(), $post->toTemplate());
        $output->writeln("<info>Post {$post->toTitle()} was generated successfully.</info>");

        return 0;
    }
}