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

    public function public(): string
    {
        return $this->config['public_path'];
    }

    public function cache(): string
    {
        return $this->config['cache_path'];
    }

    public function content(): string
    {
        return $this->config['content_path'];
    }
}