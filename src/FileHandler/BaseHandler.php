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
    protected Factory $factory;
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, Factory $factory)
    {
        $this->factory = $factory;
        $this->filesystem = $filesystem;
    }

    public function handle(Config $config, SplFileInfo $file, array $data): void
    {
        $config->setCurrentViewPath($this->getViewPath($file));
        $config->setCurrentUriPath($this->getDirectoryPrettyName($config, $file));

        $content = $this->getFileContent($config, $file, $data);
        $filepath = sprintf(
            '%s/%s',
            $this->prepareAndGetDirectory($config),
            Str::endsWith($file->getFilename(), ['.blade.php', 'md']) ? 'index.html' : $file->getFilename()
        );

        $this->filesystem->put($filepath, $content);
    }

    protected function getViewPath(SplFileInfo $fileInfo): string
    {
        return str_replace(['.blade.php', '.md'], '', $fileInfo->getRelativePathname());
    }

    protected function getDirectoryPrettyName(Config $config, SplFileInfo $fileInfo): string
    {
        $fileBaseName = $this->buildFileName($fileInfo);
        $fileRelativePath = $this->normalizePath($fileInfo->getRelativePath());

        if (in_array($fileInfo->getExtension(), ['php', 'md']) && $fileBaseName != 'index') {
            $fileRelativePath .= $fileRelativePath ? "/$fileBaseName" : $fileBaseName;
        }

        return $config->publicPath() . ($fileRelativePath ? "/$fileRelativePath" : '');
    }

    protected function buildFileName(SplFileInfo $fileInfo): string
    {
        return str_replace(['.blade.php', '.php', '.md'], '', $fileInfo->getBasename());
    }

    protected function normalizePath(string $path): string
    {
        return str_replace("\\", '/', $path);
    }

    protected function getFileContent(Config $config, SplFileInfo $fileInfo, array $data): string
    {
        if (Str::endsWith($fileInfo->getFilename(), '.blade.php')) {
            return $this->renderBlade($config, $data);
        }

        if (Str::endsWith($fileInfo->getFilename(), '.md')) {
            return $this->renderMarkdown($config, $fileInfo, $data);
        }

        return $fileInfo->getContents();
    }

    protected function renderBlade(Config $config, array $data): string
    {
        $factory = $this->factory->make(
            $config->getCurrentViewPath(),
            $data
        );

        return $factory->render();
    }

    protected function renderMarkdown(Config $config, SplFileInfo $fileInfo, array $data): string
    {
        $markdownFileBuilder = new MarkdownFile($this->filesystem, $this->factory, $fileInfo, $config, $data);
        return $markdownFileBuilder->render();
    }

    protected function prepareAndGetDirectory(Config $config): ?string
    {
        if (!$this->filesystem->isDirectory($config->getDirectory())) {
            $this->filesystem->makeDirectory($config->getDirectory(), 0755, true);
        }

        return $config->getDirectory();
    }
}
