<?php

namespace Katana\Builder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Katana\Config;
use Katana\FileHandler\BaseHandler;
use Katana\FileHandler\BlogPostHandler;
use Symfony\Component\Finder\SplFileInfo;

final class Site
{
    private array $posts;
    private array $configs;
    private string $environment;
    private string $includesDirectory = '_includes';
    private bool $forceBuild;

    private Factory $factory;
    private Filesystem $filesystem;
    private BaseHandler $fileHandler;
    private BlogPostHandler $blogPostHandler;

    public function __construct(Filesystem $filesystem, Factory $factory, string $environment, bool $forceBuild = false)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->environment = $environment;
        $this->fileHandler = new BaseHandler($filesystem, $factory);
        $this->blogPostHandler = new BlogPostHandler($filesystem, $factory);
        $this->forceBuild = $forceBuild;
    }

    public function build(Config $config): void
    {
        $data = [];
        $files = $this->getSiteFiles($config);

        $otherFiles = array_filter($files, function ($file) {
            return !Str::contains($file->getRelativePath(), '_blog');
        });

        if ($config->enable()) {
            $blogPostsFiles = array_filter($files, function ($file) {
                return Str::contains($file->getRelativePath(), '_blog');
            });

            $this->readBlogPostsData($config, $blogPostsFiles);
        }

        $config->setPosts(array_reverse($this->posts));
        $this->filesystem->cleanDirectory($config->publicPath());

        if ($this->forceBuild) {
            $this->filesystem->cleanDirectory($config->cachePath());
        }

        $this->handleSiteFiles($config, $otherFiles, $data);

        if ($config->enable()) {
            $this->handleBlogPostsFiles($config, $blogPostsFiles, $data);
            $this->buildBlogPagination($config, $data);
            $this->buildRSSFeed($config, $data);
        }
    }

    private function getSiteFiles(Config $config): array
    {
        $dir = $config->contentPath();
        $files = $this->filesystem->allFiles($dir);

        $files = array_filter($files, function (SplFileInfo $file): bool {
            return $this->filterFile($file);
        });

        $this->appendFiles($config, $files);

        return $files;
    }

    private function filterFile(SplFileInfo $file): bool
    {
        return !Str::startsWith(
            $file->getRelativePathname(),
            $this->includesDirectory
        );
    }

    private function appendFiles(Config $config, array &$files): void
    {
        $dir = $config->contentPath();

        if ($this->filesystem->exists($dir . '/.htaccess')) {
            $files[] = new SplFileInfo($dir . '/.htaccess', '', '.htaccess');
        }
    }

    private function readBlogPostsData(Config $config, array $files): void
    {
        foreach ($files as $file) {
            $this->posts[] = $this->blogPostHandler->getPostData($config, $file);
        }
    }

    private function handleSiteFiles(Config $config, array $files, array $data): void
    {
        foreach ($files as $file) {
            $this->fileHandler->handle($config, $file, $data);
        }
    }

    private function handleBlogPostsFiles(Config $config, array $files, array $data): void
    {
        foreach ($files as $file) {
            $this->blogPostHandler->handle($config, $file, $data);
        }
    }

    private function buildBlogPagination(Config $config, array $data): void
    {
        $builder = new BlogPagination($this->filesystem, $this->factory);
        $builder->build($config, $data);
    }

    private function buildRSSFeed(Config $config, array $data): void
    {
        $builder = new RSSFeed($this->filesystem, $this->factory);
        $builder->build($config, $data);
    }
}
