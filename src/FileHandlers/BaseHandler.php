<?php

declare(strict_types=1);

namespace Katana\FileHandlers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\MarkdownFileBuilder;
use Symfony\Component\Finder\SplFileInfo;

class BaseHandler
{
    public array $viewsData = [];
    protected Factory $viewFactory;
    protected Filesystem $filesystem;
    protected SplFileInfo $file;
    protected string $viewPath;
    protected string $directory;

    public function __construct(Filesystem $filesystem, Factory $viewFactory)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
    }

    public function handle(SplFileInfo $file): void
    {
        $this->file = $file;
        $this->viewPath = $this->getViewPath();
        $this->directory = $this->getDirectoryPrettyName();
        $this->appendViewInformationToData();

        if (@$this->viewsData['enableBlog']
            && @$this->viewsData['postsListView'] == $this->viewPath) {
            $this->prepareBlogIndexViewData();
        }

        $content = $this->getFileContent();

        $this->filesystem->put(
            sprintf(
                '%s/%s',
                $this->prepareAndGetDirectory(),
                ends_with($file->getFilename(), ['.blade.php', 'md']) ? 'index.html' : $file->getFilename()
            ),
            $content
        );
    }

    protected function getViewPath(): string
    {
        return str_replace(['.blade.php', '.md'], '', $this->file->getRelativePathname());
    }

    protected function getDirectoryPrettyName(): string
    {
        $fileBaseName = $this->getFileName();
        $fileRelativePath = $this->normalizePath($this->file->getRelativePath());

        if (in_array($this->file->getExtension(), ['php', 'md'])
            && $fileBaseName != 'index') {
            $fileRelativePath .= $fileRelativePath ? "/$fileBaseName" : $fileBaseName;
        }

        return KATANA_PUBLIC_DIR . ($fileRelativePath ? "/$fileRelativePath" : '');
    }

    protected function getFileName(SplFileInfo $file = null): string
    {
        $file = $file ?: $this->file;

        return str_replace(['.blade.php', '.php', '.md'], '', $file->getBasename());
    }

    protected function normalizePath(string $path): string
    {
        return str_replace("\\", '/', $path);
    }

    protected function appendViewInformationToData(): void
    {
        $this->viewsData['currentViewPath'] = $this->viewPath;

        $this->viewsData['currentUrlPath'] = ($path = str_replace(KATANA_PUBLIC_DIR, '', $this->directory)) ? $path : '/';
    }

    protected function prepareBlogIndexViewData(): void
    {
        $postsPerPage = @$this->viewsData['postsPerPage'] ?: 5;

        $this->viewsData['nextPage'] = count($this->viewsData['blogPosts']) > $postsPerPage ? '/blog-page/2' : null;
        $this->viewsData['previousPage'] = null;
        $this->viewsData['paginatedBlogPosts'] = array_slice($this->viewsData['blogPosts'], 0, $postsPerPage, true);
    }

    protected function getFileContent(): string
    {
        if (ends_with($this->file->getFilename(), '.blade.php')) {
            return $this->renderBlade();
        }

        if (ends_with($this->file->getFilename(), '.md')) {
            return $this->renderMarkdown();
        }

        return $this->file->getContents();
    }

    protected function renderBlade(): string
    {
        return $this->viewFactory->make(
            $this->viewPath,
            $this->viewsData
        )->render();
    }

    protected function renderMarkdown(): string
    {
        $markdownFileBuilder = new MarkdownFileBuilder(
            $this->filesystem,
            $this->viewFactory,
            $this->file,
            $this->viewsData
        );

        return $markdownFileBuilder->render();
    }

    protected function prepareAndGetDirectory(): string
    {
        if (!$this->filesystem->isDirectory($this->directory)) {
            $this->filesystem->makeDirectory($this->directory, 0755, true);
        }

        return $this->directory;
    }
}
