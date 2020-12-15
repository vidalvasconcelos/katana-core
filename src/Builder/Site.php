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
    private array $data;
    private array $posts;
    private array $configs;
    private string $environment;
    private string $blogDirectory = '_blog';
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
        $this->readConfigs();
        $files = $this->getSiteFiles($config);

        $otherFiles = array_filter($files, function ($file) {
            return !str_contains($file->getRelativePath(), '_blog');
        });

        if (@$this->configs['enableBlog']) {
            $blogPostsFiles = array_filter($files, function ($file) {
                return str_contains($file->getRelativePath(), '_blog');
            });

            $this->readBlogPostsData($config, $blogPostsFiles);
        }

        $this->buildViewsData();
        $this->filesystem->cleanDirectory($config->public());

        if ($this->forceBuild) {
            $this->filesystem->cleanDirectory($config->cache());
        }

        $this->handleSiteFiles($config, $otherFiles);

        if (@$this->configs['enableBlog']) {
            $this->handleBlogPostsFiles($config, $blogPostsFiles);
            $this->buildBlogPagination($config);
            $this->buildRSSFeed($config);
        }
    }

    /**
     * Read site configurations based on the current environment.
     *
     * It loads the default config file, then the environment specific
     * config file, if found, and finally merges any other configs.
     *
     * @return void
     */
    private function readConfigs(): void
    {
        $configs = include getcwd() . '/config.php';

        if (
            $this->environment != 'default' &&
            $this->filesystem->exists(getcwd() . '/' . $fileName = "config-{$this->environment}.php")
        ) {
            $configs = array_merge($configs, include getcwd() . '/' . $fileName);
        }

        $this->configs = array_merge($configs, (array)$this->configs);
    }

    private function getSiteFiles(Config $config): array
    {
        $dir = $config->content();
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
        $dir = $config->content();

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

    private function buildViewsData(): void
    {
        $this->data = $this->configs + ['blogPosts' => array_reverse((array)$this->posts)];
        $this->fileHandler->data = $this->data;
        $this->blogPostHandler->data = $this->data;
    }

    private function handleSiteFiles(Config $config, array $files): void
    {
        foreach ($files as $file) {
            $this->fileHandler->handle($config, $file);
        }
    }

    private function handleBlogPostsFiles(Config $config, array $files): void
    {
        foreach ($files as $file) {
            $this->blogPostHandler->handle($config, $file);
        }
    }

    private function buildBlogPagination(Config $config): void
    {
        $builder = new BlogPagination(
            $this->filesystem,
            $this->factory,
            $this->data
        );

        $builder->build($config);
    }

    private function buildRSSFeed(Config $config): void
    {
        $builder = new RSSFeed(
            $this->filesystem,
            $this->factory,
            $this->data
        );

        $builder->build($config);
    }

    public function setConfig(string $key, $value): void
    {
        $this->configs[$key] = $value;
    }
}
