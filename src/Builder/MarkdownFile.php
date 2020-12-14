<?php

declare(strict_types=1);

namespace Katana\Builder;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Katana\Markdown;
use Symfony\Component\Finder\SplFileInfo;

final class MarkdownFile
{
    protected array $data;
    protected array $fileYAML;
    protected string $cached;
    protected string $fileContent;
    protected Factory $viewFactory;
    protected Filesystem $filesystem;
    protected BladeCompiler $bladeCompiler;
    protected SplFileInfo $file;
    protected PhpEngine $engine;

    public function __construct(Filesystem $filesystem, Factory $viewFactory, SplFileInfo $file, array $data)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        $this->file = $file;
        $this->data = $data;

        $parsed = Markdown::parseWithYAML($this->file->getContents());

        $this->fileContent = $parsed[0];
        $this->fileYAML = $parsed[1];
        $this->cached = KATANA_CACHE_DIR . '/' . sha1($this->file->getRelativePathname()) . '.php';
        $this->bladeCompiler = $this->getBladeCompiler();
        $this->engine = $this->getEngine();
    }

    public function render(): string
    {
        $viewContent = $this->buildBladeViewContent();

        if ($this->isExpired()) {
            $this->filesystem->put($this->cached, $this->bladeCompiler->compileString($viewContent));
        }

        $data = $this->getViewData();

        return $this->engine->get($this->cached, $data);
    }

    protected function buildBladeViewContent(): string
    {
        $sections = '';

        foreach ($this->fileYAML as $name => $value) {
            $sections .= "@section('$name', '" . addslashes($value) . "')\n\r";
        }

        return
            "@extends('{$this->fileYAML['view::extends']}')
            $sections
            @section('{$this->fileYAML['view::yields']}')
            {$this->fileContent}
            @stop";
    }

    protected function getBladeCompiler(): BladeCompiler
    {
        return $this->viewFactory->getEngineResolver()->resolve('blade')->getCompiler();
    }

    protected function getEngine(): PhpEngine
    {
        return new PhpEngine;
    }

    protected function getViewData(): array
    {
        $data = array_merge($this->viewFactory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    protected function isExpired(): bool
    {
        if (!$this->filesystem->exists($this->cached)) {
            return true;
        }

        $lastModified = $this->filesystem->lastModified($this->file->getPath());

        return $lastModified >= $this->filesystem->lastModified($this->cached);
    }
}