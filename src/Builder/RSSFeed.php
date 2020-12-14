<?php

declare(strict_types=1);

namespace Katana\Builder;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

final class RSSFeed
{
    protected array $viewsData = [];
    protected Factory $viewFactory;
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, Factory $viewFactory, array $viewsData)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        $this->viewsData = $viewsData;
    }

    public function build(): void
    {
        if (!$view = $this->getRSSView()) {
            return;
        }

        $pageContent = $this->viewFactory->make($view, $this->viewsData)->render();

        $this->filesystem->put(
            sprintf('%s/%s', KATANA_PUBLIC_DIR, 'feed.rss'),
            $pageContent
        );
    }

    protected function getRSSView(): ?array
    {
        if (!isset($this->viewsData['rssFeedView']) || !@$this->viewsData['rssFeedView']) {
            return null;
        }

        if (!$this->viewFactory->exists($this->viewsData['rssFeedView'])) {
            throw new Exception(sprintf('The "%s" view is not found. Make sure the rssFeedView configuration key is correct.', $this->viewsData['rssFeedView']));
        }

        return $this->viewsData['rssFeedView'];
    }
}