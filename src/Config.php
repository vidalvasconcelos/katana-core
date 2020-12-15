<?php

declare(strict_types=1);

namespace Katana;

final class Config
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function enable(): bool
    {
        return $this->config['enable'] ?? false;
    }

    public function baseUri(): string
    {
        return $this->config['base_uri'];
    }

    public function perPage(): int
    {
        return $this->config['per_page'] ?? 5;
    }

    public function paginatedView(): string
    {
        return $this->config['paginated_view'];
    }

    public function name(): string
    {
        return $this->config['name'];
    }

    public function description(): string
    {
        return $this->config['description'];
    }

    public function publicPath(): string
    {
        return $this->config['public_path'];
    }

    public function cachePath(): string
    {
        return $this->config['cache_path'];
    }

    public function contentPath(): string
    {
        return $this->config['content_path'];
    }

    public function posts(): array
    {
        return $this->config['blog_posts'];
    }

    public function nextPage(): ?string
    {
        return count($this->posts()) > $this->perPage()
            ? '/blog-page/2'
            : null;
    }

    public function previousPage(): ?string
    {
        return $this->config['previous_page'] ?? null;
    }

    public function setPosts(array $posts): void
    {
        $this->config['blog_posts'] = $posts;
    }

    public function getCurrentViewPath(): ?string
    {
        return $this->config['current_view_path'] ?? null;
    }

    public function setCurrentViewPath(string $view): void
    {
        $this->config['current_view_path'] = $view;
    }

    public function getDirectory(): ?string
    {
        return $this->config['directory'] ?? null;
    }

    public function setCurrentUriPath(string $directory): void
    {
        $this->config['directory'] = $directory;

        $path = str_replace($this->publicPath(), '', $directory);

        $this->config['current_view_path'] = $path
            ? $path
            : '/';
    }

    public function paginatedBlogPost(): array
    {
        return array_slice($this->posts(), 0, $this->perPage(), true);
    }
}