<?php

declare(strict_types=1);

namespace Katana\Builder;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\Config;

final class RSSFeed
{
    private Factory $factory;
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, Factory $factory)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
    }

    public function build(Config $config, array $data): void
    {
        if (!$view = $this->getRSSView($data)) {
            return;
        }

        $pageContent = $this->factory->make($view, $data)->render();

        $this->filesystem->put(
            sprintf('%s/%s', $config->publicPath(), 'feed.rss'),
            $pageContent
        );
    }

    private function getRSSView(array $data): ?string
    {
        if (!isset($data['rss_feed_view']) || !@$data['rss_feed_view']) {
            return null;
        }

        if (!$this->factory->exists($data['rss_feed_view'])) {
            throw new Exception(sprintf('The "%s" view is not found. Make sure the rssFeedView configuration key is correct.', $data['rss_feed_view']));
        }

        return $data['rss_feed_view'];
    }
}