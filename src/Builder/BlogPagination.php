<?php

declare(strict_types=1);

namespace Katana\Builder;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\Support\Str;
use Katana\Config;

final class BlogPagination
{
    private array $pagesData;
    private Factory $factory;
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, Factory $factory)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
    }

    public function build(Config $config, array $data): void
    {
        $view = $this->getPostsListView($data);
        $postsPerPage = $data['postsPerPage'] ?? 5;
        $this->pagesData = array_chunk($data['blogPosts'], $postsPerPage);

        foreach ($this->pagesData as $pageIndex => $posts) {
            $this->buildPage($config, $data, $pageIndex, $view, $posts);
        }
    }

    private function getPostsListView(array $data): string
    {
        if (!isset($data['postsListView'])) {
            throw new Exception('The postsListView config value is missing.');
        }

        if (!$this->factory->exists($data['postsListView'])) {
            throw new Exception(sprintf('The "%s" view is not found. Make sure the postsListView configuration key is correct.', $data['postsListView']));
        }

        return $data['postsListView'];
    }

    private function buildPage(Config $config, array $data, int $pageIndex, string $view, array $posts): void
    {
        $viewData = array_merge($data, [
            'paginatedBlogPosts' => $posts,
            'previousPage' => $this->getPreviousPageLink($pageIndex),
            'nextPage' => $this->getNextPageLink($pageIndex),
        ]);

        $pageContent = $this->factory->make($view, $viewData)->render();
        $directory = sprintf('%s/blog-page/%d', $config->public(), $pageIndex + 1);

        $this->filesystem->makeDirectory($directory, 0755, true);

        $this->filesystem->put(
            sprintf('%s/%s', $directory, 'index.html'),
            $pageContent
        );
    }

    private function getPreviousPageLink(int $currentPageIndex): ?string
    {
        if (!isset($this->pagesData[$currentPageIndex - 1])) {
            return null;
        } elseif ($currentPageIndex == 1) {
            // If the current page is the second, then the first page's
            // link should point to the blog's main view.
            return '/' . $this->getBlogListPagePath();
        }

        return '/blog-page/' . $currentPageIndex . '/';
    }

    private function getBlogListPagePath(array $data): string
    {
        $path = str_replace('.', '/', $data['postsListView']);

        if (Str::endsWith($path, 'index')) {
            return rtrim($path, '/index');
        }

        return $path;
    }

    private function getNextPageLink($currentPageIndex): ?string
    {
        if (!isset($this->pagesData[$currentPageIndex + 1])) {
            return null;
        }

        return '/blog-page/' . ($currentPageIndex + 2) . '/';
    }
}