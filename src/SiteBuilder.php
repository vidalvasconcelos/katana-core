<?php

namespace Katana;

use Symfony\Component\Finder\SplFileInfo;
use Katana\FileHandlers\BlogPostHandler;
use Illuminate\Filesystem\Filesystem;
use Katana\FileHandlers\BaseHandler;
use Illuminate\View\Factory;
use Illuminate\Support\Str;

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
    protected $blogDirectory = '_blog';

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

    /**
     * Build the site from blade views.
     *
     * @return void
     */
    public function build()
    {
        $this->readConfigs();

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

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setConfig($key, $value)
    {
        $this->configs[$key] = $value;
    }

    /**
     * Read site configurations based on the current environment.
     *
     * It loads the default config file, then the environment specific
     * config file, if found, and finally merges any other configs.
     *
     * @return void
     */
    protected function readConfigs()
    {
        $configs = include getcwd().'/config.php';

        if (
            $this->environment != 'default' &&
            $this->filesystem->exists(getcwd().'/'.$fileName = "config-{$this->environment}.php")
        ) {
            $configs = array_merge($configs, include getcwd().'/'.$fileName);
        }

        $this->configs = array_merge($configs, (array) $this->configs);
    }

    /**
     * Handle non-blog site files.
     *
     * @param array $files
     *
     * @return void
     */
    protected function handleSiteFiles($files)
    {
        foreach ($files as $file) {
            $this->fileHandler->handle($file);
        }
    }

    /**
     * Handle blog posts files.
     *
     * @param array $files
     *
     * @return void
     */
    protected function handleBlogPostsFiles($files)
    {
        foreach ($files as $file) {
            $this->blogPostHandler->handle($file);
        }
    }

    /**
     * Get the site files that will be converted into pages.
     *
     * @return SplFileInfo[]
     */
    protected function getSiteFiles()
    {
        $files = array_filter($this->filesystem->allFiles(DIRECTORY_CONTENT), function (SplFileInfo $file) {
            return $this->filterFile($file);
        });

        $this->appendFiles($files);

        return $files;
    }

    /**
     * Filter un-needed files from.
     *
     * @param SplFileInfo $file
     * @return bool
     */
    protected function filterFile(SplFileInfo $file)
    {
        return ! Str::startsWith($file->getRelativePathname(), $this->includesDirectory);
    }

    /**
     * Append files to public.
     *
     * @param array $files
     */
    protected function appendFiles(array &$files)
    {
        if ($this->filesystem->exists(DIRECTORY_CONTENT.'/.htaccess')) {
            $files[] = new SplFileInfo(DIRECTORY_CONTENT.'/.htaccess', '', '.htaccess');
        }
    }

    /**
     * Read the data of every blog post.
     *
     * @param array $files
     *
     * @return void
     */
    protected function readBlogPostsData($files)
    {
        foreach ($files as $file) {
            $this->postsData[] = $this->blogPostHandler->getPostData($file);
        }
    }

    /**
     * Build array of data to be passed to every view.
     *
     * @return void
     */
    protected function buildViewsData()
    {
        $this->viewsData = $this->configs + ['blogPosts' => array_reverse((array) $this->postsData)];

        $this->fileHandler->viewsData = $this->viewsData;

        $this->blogPostHandler->viewsData = $this->viewsData;
    }

    /**
     * Build the blog pagination files.
     *
     * @return void
     */
    protected function buildBlogPagination()
    {
        $builder = new BlogPaginationBuilder(
            $this->filesystem,
            $this->viewFactory,
            $this->viewsData
        );

        $builder->build();
    }

    /**
     * Build the blog RSS feed.
     *
     * @return void
     */
    protected function buildRSSFeed()
    {
        $builder = new RSSFeedBuilder(
            $this->filesystem,
            $this->viewFactory,
            $this->viewsData
        );

        $builder->build();
    }
}
