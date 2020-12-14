<?php

declare(strict_types=1);

namespace Katana\Builder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

final class BlogPagination
{
    protected array $data;
    protected array $pagesData;
    protected Factory $factory;
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, Factory $factory, array $data)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->data = $data;
    }

    public function build(): void
    {
        $view = $this->getPostsListView();
        $postsPerPage = @$this->data['postsPerPage'] ?: 5;
        $this->pagesData = array_chunk($this->data['blogPosts'], $postsPerPage);

        foreach ($this->pagesData as $pageIndex => $posts) {
            $this->buildPage($pageIndex, $view, $posts);
        }
    }

    protected function getPostsListView(): string
    {
        if (! isset($this->data['postsListView'])) {
            throw new \Exception('The postsListView config value is missing.');
        }

        if (! $this->factory->exists($this->data['postsListView'])) {
            throw new \Exception(sprintf('The "%s" view is not found. Make sure the postsListView configuration key is correct.', $this->data['postsListView']));
        }

        return $this->data['postsListView'];
    }

    protected function buildPage(int $pageIndex, string $view, array $posts): void
    {
        $viewData = array_merge(
            $this->data,
            [
                'paginatedBlogPosts' => $posts,
                'previousPage' => $this->getPreviousPageLink($pageIndex),
                'nextPage' => $this->getNextPageLink($pageIndex),
            ]
        );

        $pageContent = $this->factory->make($view, $viewData)->render();

        $directory = sprintf('%s/blog-page/%d', KATANA_PUBLIC_DIR, $pageIndex + 1);

        $this->filesystem->makeDirectory($directory, 0755, true);

        $this->filesystem->put(
            sprintf('%s/%s', $directory, 'index.html'),
            $pageContent
        );
    }

    protected function getPreviousPageLink(int $currentPageIndex): ?string
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

    protected function getNextPageLink($currentPageIndex): ?string
    {
        if (! isset($this->pagesData[$currentPageIndex + 1])) {
            return null;
        }

        return '/blog-page/'.($currentPageIndex + 2).'/';
    }

    protected function getBlogListPagePath(): string
    {
        $path = str_replace('.', '/', $this->data['postsListView']);

        if (ends_with($path, 'index')) {
            return rtrim($path, '/index');
        }

        return $path;
    }
}