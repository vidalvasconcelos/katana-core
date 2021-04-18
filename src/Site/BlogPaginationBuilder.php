<?php

namespace Katana\Site;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use function ends_with;

class BlogPaginationBuilder
{
    protected $filesystem;
    protected $viewFactory;
    protected $viewsData;
    protected $pagesData;

    /**
     * BlogPaginationBuilder constructor.
     *
     * @param Filesystem $filesystem
     * @param Factory $viewFactory
     * @param array $viewsData
     */
    public function __construct(Filesystem $filesystem, Factory $viewFactory, array $viewsData)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        $this->viewsData = $viewsData;
    }

    /**
     * Build blog pagination files.
     *
     * @return void
     */
    public function build()
    {
        $view = $this->getPostsListView();

        $postsPerPage = @$this->viewsData['postsPerPage'] ?: 5;

        $this->pagesData = array_chunk($this->viewsData['blogPosts'], $postsPerPage);

        foreach ($this->pagesData as $pageIndex => $posts) {
            $this->buildPage($pageIndex, $view, $posts);
        }
    }

    /**
     * Get the name of the view to be used for pages.
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getPostsListView()
    {
        if (! isset($this->viewsData['postsListView'])) {
            throw new \Exception('The postsListView config value is missing.');
        }

        if (! $this->viewFactory->exists($this->viewsData['postsListView'])) {
            throw new \Exception(sprintf('The "%s" view is not found. Make sure the postsListView configuration key is correct.', $this->viewsData['postsListView']));
        }

        return $this->viewsData['postsListView'];
    }

    /**
     * Build a pagination page.
     *
     * @param integer $pageIndex
     * @param string $view
     * @param array $posts
     *
     * @return void
     */
    protected function buildPage($pageIndex, $view, $posts)
    {
        $viewData = array_merge(
            $this->viewsData,
            [
                'paginatedBlogPosts' => $posts,
                'previousPage' => $this->getPreviousPageLink($pageIndex),
                'nextPage' => $this->getNextPageLink($pageIndex),
            ]
        );

        $pageContent = $this->viewFactory->make($view, $viewData)->render();

        $directory = sprintf('%s/blog-page/%d', DIRECTORY_PUBLIC, $pageIndex + 1);

        $this->filesystem->makeDirectory($directory, 0755, true);

        $this->filesystem->put(
            sprintf('%s/%s', $directory, 'index.html'),
            $pageContent
        );
    }

    /**
     * Get the link of the page before the given page.
     *
     * @param integer $currentPageIndex
     *
     * @return null|string
     */
    protected function getPreviousPageLink($currentPageIndex)
    {
        if (! isset($this->pagesData[$currentPageIndex - 1])) {
            return null;
        } elseif ($currentPageIndex == 1) {
            // If the current page is the second, then the first page's
            // link should point to the blog's main view.
            return '/'.$this->getBlogListPagePath();
        }

        return '/blog-page/'.$currentPageIndex.'/';
    }

    /**
     * Get the link of the page after the given page.
     *
     * @param integer $currentPageIndex
     *
     * @return null|string
     */
    protected function getNextPageLink($currentPageIndex)
    {
        if (! isset($this->pagesData[$currentPageIndex + 1])) {
            return null;
        }

        return '/blog-page/'.($currentPageIndex + 2).'/';
    }

    /**
     * Get the URL path to the blog list page.
     *
     * @return string
     */
    protected function getBlogListPagePath()
    {
        $path = str_replace('.', '/', $this->viewsData['postsListView']);

        if (ends_with($path, 'index')) {
            return rtrim($path, '/index');
        }

        return $path;
    }
}