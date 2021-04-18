<?php

namespace Katana\Site;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

class RSSFeedBuilder
{
    protected $filesystem;
    protected $viewFactory;
    protected $viewsData;

    /**
     * RSSFeedBuilder constructor.
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
     * Build blog RSS feed file.
     *
     * @return void
     */
    public function build()
    {
        if (! $view = $this->getRSSView()) {
            return;
        }

        $pageContent = $this->viewFactory->make($view, $this->viewsData)->render();

        $this->filesystem->put(
            sprintf('%s/%s', DIRECTORY_PUBLIC, 'feed.rss'),
            $pageContent
        );
    }

    /**
     * Get the name of the view to be used for generating the RSS feed.
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getRSSView()
    {
        if (! isset($this->viewsData['rssFeedView']) || ! @$this->viewsData['rssFeedView']) {
            return null;
        }

        if (! $this->viewFactory->exists($this->viewsData['rssFeedView'])) {
            throw new \Exception(sprintf('The "%s" view is not found. Make sure the rssFeedView configuration key is correct.', $this->viewsData['rssFeedView']));
        }

        return $this->viewsData['rssFeedView'];
    }
}