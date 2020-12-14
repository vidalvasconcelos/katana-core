<?php

declare(strict_types=1);

namespace Katana;

final class Config
{
    private string $cacheDirectory;
    private string $publicDirectory;
    private string $contentDirectory;

    public function __construct(array $config)
    {
        $this->cacheDirectory = $config['KATANA_CACHE_DIR'] ?? getcwd() . '/_cache';
        $this->contentDirectory = $config['KATANA_CONTENT_DIR'] ?? getcwd() . '/content';
        $this->publicDirectory = $config['KATANA_PUBLIC_DIR'] ?? getcwd() . '/public';
    }

    public function cache(): string
    {
        return $this->cacheDirectory;
    }

    public function content(): string
    {
        return $this->contentDirectory;
    }

    public function public(): string
    {
        return $this->publicDirectory;
    }
}