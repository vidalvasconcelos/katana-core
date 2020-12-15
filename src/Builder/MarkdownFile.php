<?php

declare(strict_types=1);

namespace Katana\Builder;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Katana\Config;
use Katana\Markdown;
use Symfony\Component\Finder\SplFileInfo;

final class MarkdownFile
{
    private array $data;
    private array $fileYAML;
    private string $cached;
    private string $fileContent;
    private Factory $factory;
    private Filesystem $filesystem;
    private BladeCompiler $bladeCompiler;
    private SplFileInfo $file;
    private PhpEngine $engine;
    private Config $config;

    public function __construct(Filesystem $filesystem, Factory $factory, SplFileInfo $file, Config $config, array $data)
    {
        $this->filesystem = $filesystem;
        $this->factory = $factory;
        $this->file = $file;
        $this->data = $data;

        $parsed = Markdown::parseWithYAML($this->file->getContents());

        $this->fileContent = $parsed[0];
        $this->fileYAML = $parsed[1];
        $this->cached = $config->cachePath() . '/' . sha1($this->file->getRelativePathname()) . '.php';
        $this->bladeCompiler = $this->getBladeCompiler();
        $this->engine = $this->getEngine($filesystem);
        $this->config = $config;
    }

    private function getBladeCompiler(): BladeCompiler
    {
        return $this->factory->getEngineResolver()->resolve('blade')->getCompiler();
    }

    private function getEngine(Filesystem $filesystem): PhpEngine
    {
        return new PhpEngine($filesystem);
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

    private function buildBladeViewContent(): string
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

    private function isExpired(): bool
    {
        if (!$this->filesystem->exists($this->cached)) {
            return true;
        }

        $lastModified = $this->filesystem->lastModified($this->file->getPath());

        return $lastModified >= $this->filesystem->lastModified($this->cached);
    }

    private function getViewData(): array
    {
        $data = array_merge($this->factory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }
}