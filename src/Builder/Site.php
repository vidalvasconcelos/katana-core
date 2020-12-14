<?php

namespace Katana\Builder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Katana\FileHandler\BaseHandler;
use Katana\FileHandler\BlogPostHandler;
use Symfony\Component\Finder\SplFileInfo;

final class Site
{
    protected array $data;
    protected array $posts;
    protected array $configs;
    protected string $environment;
    protected string $blogDirectory = '_blog';
    protected string $includesDirectory = '_includes';
    protected Factory $factory;
    protected Filesystem $filesystem;
    protected BaseHandler $fileHandler;
    protected BlogPostHandler $blogPostHandler;
    protected bool $forceBuild = false;

    public function __construct(Filesystem $filesystem, Factory $factory, string $environment, bool $forceBuild = false)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->environment = $environment;
        $this->fileHandler = new BaseHandler($filesystem, $factory);
        $this->blogPostHandler = new BlogPostHandler($filesystem, $factory);
        $this->forceBuild = $forceBuild;
    }

    public function build(): void
    {
        $this->readConfigs();
        $files = $this->getSiteFiles();

        $otherFiles = array_filter($files, function ($file) {
            return !str_contains($file->getRelativePath(), '_blog');
        });

        if (@$this->configs['enableBlog']) {
            $blogPostsFiles = array_filter($files, function ($file) {
                return str_contains($file->getRelativePath(), '_blog');
            });

            $this->readBlogPostsData($blogPostsFiles);
        }

        $this->buildViewsData();
        $this->filesystem->cleanDirectory(KATANA_PUBLIC_DIR);

        if ($this->forceBuild) {
            $this->filesystem->cleanDirectory(KATANA_CACHE_DIR);
        }

        $this->handleSiteFiles($otherFiles);

        if (@$this->configs['enableBlog']) {
            $this->handleBlogPostsFiles($blogPostsFiles);
            $this->buildBlogPagination();
            $this->buildRSSFeed();
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
    protected function readConfigs(): void
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

    protected function getSiteFiles(): array
    {
        $files = $this->filesystem->allFiles(KATANA_CONTENT_DIR);
        $files = array_filter($files, function (SplFileInfo $file) {
            return $this->filterFile($file);
        });

        $this->appendFiles($files);

        return $files;
    }

    protected function filterFile(SplFileInfo $file): bool
    {
        return !Str::startsWith(
            $file->getRelativePathname(),
            $this->includesDirectory
        );
    }

    protected function appendFiles(array &$files): void
    {
        if ($this->filesystem->exists(KATANA_CONTENT_DIR . '/.htaccess')) {
            $files[] = new SplFileInfo(KATANA_CONTENT_DIR . '/.htaccess', '', '.htaccess');
        }
    }

    protected function readBlogPostsData(array $files): void
    {
        foreach ($files as $file) {
            $this->posts[] = $this->blogPostHandler->getPostData($file);
        }
    }

    protected function buildViewsData(): void
    {
        $this->data = $this->configs + ['blogPosts' => array_reverse((array)$this->posts)];
        $this->fileHandler->data = $this->data;
        $this->blogPostHandler->data = $this->data;
    }

    protected function handleSiteFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->fileHandler->handle($file);
        }
    }

    protected function handleBlogPostsFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->blogPostHandler->handle($file);
        }
    }

    protected function buildBlogPagination(): void
    {
        $builder = new BlogPagination(
            $this->filesystem,
            $this->factory,
            $this->data
        );

        $builder->build();
    }

    protected function buildRSSFeed(): void
    {
        $builder = new RSSFeed(
            $this->filesystem,
            $this->factory,
            $this->data
        );

        $builder->build();
    }

    public function setConfig(string $key, $value): void
    {
        $this->configs[$key] = $value;
    }
}
