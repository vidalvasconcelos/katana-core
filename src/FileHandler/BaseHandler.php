<?php

declare(strict_types=1);

namespace Katana\FileHandler;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\Support\Str;
use Katana\Builder\MarkdownFile;
use Katana\Config;
use Symfony\Component\Finder\SplFileInfo;

class BaseHandler
{
    protected string $view;
    protected string $directory;
    protected Factory $factory;
    protected Filesystem $filesystem;
    protected SplFileInfo $file;

    public function __construct(Filesystem $filesystem, Factory $viewFactory)
    {
        $this->filesystem = $filesystem;
        $this->factory = $viewFactory;
    }

    public function handle(Config $config, SplFileInfo $file, array $data): void
    {
        $this->file = $file;
        $this->view = $this->getViewPath();
        $this->directory = $this->getDirectoryPrettyName($config);
        $this->appendViewInformationToData($config, $data);

        if ($this->indexViewShouldBePrepared($data)) {
            $this->prepareBlogIndexViewData($data);
        }

        $content = $this->getFileContent($config, $data);
        $filepath = sprintf(
            '%s/%s',
            $this->prepareAndGetDirectory(),
            Str::endsWith($file->getFilename(), ['.blade.php', 'md']) ? 'index.html' : $file->getFilename()
        );

        $this->filesystem->put($filepath, $content);
    }

    protected function getViewPath(): string
    {
        return str_replace(['.blade.php', '.md'], '', $this->file->getRelativePathname());
    }

    protected function getDirectoryPrettyName(Config $config): string
    {
        $fileBaseName = $this->getFileName();
        $fileRelativePath = $this->normalizePath($this->file->getRelativePath());

        if (in_array($this->file->getExtension(), ['php', 'md'])
            && $fileBaseName != 'index') {
            $fileRelativePath .= $fileRelativePath ? "/$fileBaseName" : $fileBaseName;
        }

        return $config->public() . ($fileRelativePath ? "/$fileRelativePath" : '');
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

    protected function appendViewInformationToData(Config $config, array &$data): void
    {
        $data['currentViewPath'] = $this->view;
        $data['currentUrlPath'] = ($path = str_replace($config->public(), '', $this->directory)) ? $path : '/';
    }

    protected function prepareBlogIndexViewData(array &$data): void
    {
        $postsPerPage = $data['postsPerPage'] ?? 5;

        $data['nextPage'] = count($data['blogPosts']) > $postsPerPage ? '/blog-page/2' : null;
        $data['previousPage'] = null;
        $data['paginatedBlogPosts'] = array_slice($data['blogPosts'], 0, $postsPerPage, true);
    }

    protected function getFileContent(Config $config, array $data): string
    {
        if (Str::endsWith($this->file->getFilename(), '.blade.php')) {
            return $this->renderBlade($data);
        }

        if (Str::endsWith($this->file->getFilename(), '.md')) {
            return $this->renderMarkdown($config, $data);
        }

        return $this->file->getContents();
    }

    protected function renderBlade(array $data): string
    {
        return $this->factory->make($this->view, $data)->render();
    }

    protected function renderMarkdown(Config $config, array $data): string
    {
        $markdownFileBuilder = new MarkdownFile($this->filesystem, $this->factory, $this->file, $config, $data);
        return $markdownFileBuilder->render();
    }

    protected function prepareAndGetDirectory(): string
    {
        if (!$this->filesystem->isDirectory($this->directory)) {
            $this->filesystem->makeDirectory($this->directory, 0755, true);
        }

        return $this->directory;
    }

    private function indexViewShouldBePrepared(array $data): bool
    {
        return $data['enableBlog'] ?? false
            && $data['postsListView'] == $this->view;
    }
}
