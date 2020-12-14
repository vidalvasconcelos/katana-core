<?php

declare(strict_types=1);

namespace Katana\Builder;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\Config;

final class RSSFeed
{
    protected array $data = [];
    protected Factory $factory;
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, Factory $factory, array $data)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->data = $data;
    }

    public function build(Config $config): void
    {
        if (!$view = $this->getRSSView()) {
            return;
        }

        $pageContent = $this->factory->make($view, $this->data)->render();

        $this->filesystem->put(
            sprintf('%s/%s', $config->public(), 'feed.rss'),
            $pageContent
        );
    }

    protected function getRSSView(): ?string
    {
        if (!isset($this->data['rssFeedView']) || !@$this->data['rssFeedView']) {
            return null;
        }

        if (!$this->factory->exists($this->data['rssFeedView'])) {
            throw new Exception(sprintf('The "%s" view is not found. Make sure the rssFeedView configuration key is correct.', $this->data['rssFeedView']));
        }

        return $this->data['rssFeedView'];
    }
}