<?php

namespace Katana\Site;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use Symfony\Component\Finder\SplFileInfo;
use const Katana\DIRECTORY_CACHE;
use const Katana\DIRECTORY_CONTENT;
use const Katana\DIRECTORY_PUBLIC;

class SiteBuilder
{
    protected $filesystem;
    protected $viewFactory;
    protected $blogPostHandler;
    protected $fileHandler;

    /**
     * The application environment.
     *
     * @var string
     */
    protected $environment;

    /**
     * The site configurations.
     *
     * @var array
     */
    protected $configs;

    /**
     * The data included in every view file of a post.
     *
     * @var array
     */
    protected $postsData;

    /**
     * The data to pass to every view.
     *
     * @var array
     */
    protected $viewsData;

    /**
     * The directory that contains blade sub views.
     *
     * @var array
     */
    protected $includesDirectory = '_includes';

    /**
     * The directory that contains blog posts.
     *
     * @var array
     */

    /**
     * Clear the cache before building.
     *
     * @var array
     */
    protected $forceBuild = false;

    /**
     * SiteBuilder constructor.
     *
     * @param Filesystem $filesystem
     * @param Factory $viewFactory
     * @param string $environment
     * @param bool $forceBuild
     */
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
        $files = $this->getSiteFiles();

        $otherFiles = array_filter($files, function ($file) {
            return ! str_contains($file->getRelativePath(), '_blog');
        });

        if (@$this->configs['enableBlog']) {
            $blogPostsFiles = array_filter($files, function ($file) {
                return str_contains($file->getRelativePath(), '_blog');
            });

            $this->readBlogPostsData($blogPostsFiles);
        }

        $this->buildViewsData();

        $this->filesystem->cleanDirectory(DIRECTORY_PUBLIC);

        if ($this->forceBuild) {
            $this->filesystem->cleanDirectory(DIRECTORY_CACHE);
        }

        $this->handleSiteFiles($otherFiles);

        if (@$this->configs['enableBlog']) {
            $this->handleBlogPostsFiles($blogPostsFiles);
            $this->buildBlogPagination();
            $this->buildRSSFeed();
        }
    }

    public function setConfig(string $key, string $value): void
    {
        $this->configs[$key] = $value;
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

    protected function getSiteFiles(): array
    {
        $files = array_filter($this->filesystem->allFiles(DIRECTORY_CONTENT), function (SplFileInfo $file) {
            return $this->filterFile($file);
        });

        $this->appendFiles($files);

        return $files;
    }

    protected function filterFile(SplFileInfo $file): bool
    {
        return ! Str::startsWith($file->getRelativePathname(), $this->includesDirectory);
    }

    protected function appendFiles(array &$files): void
    {
        if ($this->filesystem->exists(DIRECTORY_CONTENT.'/.htaccess')) {
            $files[] = new SplFileInfo(DIRECTORY_CONTENT.'/.htaccess', '', '.htaccess');
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
        $this->viewsData = $this->configs + ['blogPosts' => array_reverse($this->postsData)];
        $this->fileHandler->viewsData = $this->viewsData;
        $this->blogPostHandler->viewsData = $this->viewsData;
    }

    protected function buildBlogPagination(): void
    {
        $builder = new BlogPaginationBuilder($this->filesystem, $this->viewFactory, $this->viewsData);
        $builder->build();
    }

    protected function buildRSSFeed(): void
    {
        $builder = new RSSFeedBuilder($this->filesystem, $this->viewFactory, $this->viewsData);
        $builder->build();
    }
}
