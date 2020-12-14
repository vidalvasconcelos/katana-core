<?php

namespace Katana;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Katana\FileHandlers\BaseHandler;
use Katana\FileHandlers\BlogPostHandler;
use Symfony\Component\Finder\SplFileInfo;

final class SiteBuilder
{
    protected array $configs;
    protected array $postsData;
    protected array $viewsData;
    protected string $environment;
    protected string $blogDirectory = '_blog';
    protected string $includesDirectory = '_includes';
    protected Factory $viewFactory;
    protected Filesystem $filesystem;
    protected BaseHandler $fileHandler;
    protected BlogPostHandler $blogPostHandler;
    protected bool $forceBuild = false;

    public function __construct(Filesystem $filesystem, Factory $viewFactory, $environment, $forceBuild = false)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        $this->environment = $environment;
        $this->fileHandler = new BaseHandler($filesystem, $viewFactory);
        $this->blogPostHandler = new BlogPostHandler($filesystem, $viewFactory);
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
            $this->postsData[] = $this->blogPostHandler->getPostData($file);
        }
    }

    protected function buildViewsData(): void
    {
        $this->viewsData = $this->configs + ['blogPosts' => array_reverse((array)$this->postsData)];
        $this->fileHandler->viewsData = $this->viewsData;
        $this->blogPostHandler->viewsData = $this->viewsData;
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
        $builder = new BlogPaginationBuilder(
            $this->filesystem,
            $this->viewFactory,
            $this->viewsData
        );

        $builder->build();
    }

    protected function buildRSSFeed(): void
    {
        $builder = new RSSFeedBuilder(
            $this->filesystem,
            $this->viewFactory,
            $this->viewsData
        );

        $builder->build();
    }

    public function setConfig(string $key, $value): void
    {
        $this->configs[$key] = $value;
    }
}
