<?php

namespace Katana;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\View\Compilers\BladeCompiler;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

class MarkdownFileBuilder
{
    protected $filesystem;
    protected $viewFactory;
    protected $file;
    protected $data;

    /**
     * The body of the file.
     *
     * @var string
     */
    protected $fileContent;

    /**
     * The YAML meta of the file.
     *
     * @var array
     */
    protected $fileYAML;

    /**
     * The BladeCompiler instance.
     *
     * @var BladeCompiler
     */
    protected $bladeCompiler;

    /**
     * The CompilerEngine instance.
     *
     * @var PhpEngine
     */
    protected $engine;

    /**
     * The path to the cached compilation.
     *
     * @var string
     */
    protected $cached;

    /**
     * MarkdownFileBuilder constructor.
     *
     * @param Filesystem $filesystem
     * @param Factory $viewFactory
     */
    public function __construct(Filesystem $filesystem, Factory $viewFactory, SplFileInfo $file, array $data)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        $this->file = $file;
        $this->data = $data;

        $parsed = Markdown::parseWithYAML($this->file->getContents());

        $this->fileContent = $parsed[0];

        $this->fileYAML = $parsed[1];

        $this->cached = DIRECTORY_CACHE.'/'.sha1($this->file->getRelativePathname()).'.php';

        $this->bladeCompiler = $this->getBladeCompiler();

        $this->engine = $this->getEngine();
    }

    /**
     * Get the evaluated contents of the file.
     */
    public function render()
    {
        $viewContent = $this->buildBladeViewContent();

        if ($this->isExpired()) {
            $this->filesystem->put($this->cached, $this->bladeCompiler->compileString($viewContent));
        }

        $data = $this->getViewData();

        return $this->engine->get($this->cached, $data);
    }

    /**
     * Build the content of the imaginary blade view.
     *
     * @return string
     */
    protected function buildBladeViewContent()
    {
        $sections = '';

        foreach ($this->fileYAML as $name => $value) {
            $sections .= "@section('$name', '".addslashes($value)."')\n\r";
        }

        return
            "@extends('{$this->fileYAML['view::extends']}')
            $sections
            @section('{$this->fileYAML['view::yields']}')
            {$this->fileContent}
            @stop";
    }

    /**
     * Return the BladeCompiler from the view factory.
     *
     * @return BladeCompiler
     */
    protected function getBladeCompiler()
    {
        return $this->viewFactory->getEngineResolver()->resolve('blade')->getCompiler();
    }

    /**
     * Return the PhpEngine.
     *
     * @return PhpEngine
     */
    protected function getEngine()
    {
        return new PhpEngine;
    }

    /**
     * Get variables to be passed to the view.
     *
     * @return array
     */
    protected function getViewData()
    {
        $data = array_merge($this->viewFactory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @return bool
     */
    protected function isExpired()
    {
        if (! $this->filesystem->exists($this->cached)) {
            return true;
        }

        $lastModified = $this->filesystem->lastModified($this->file->getPath());

        return $lastModified >= $this->filesystem->lastModified($this->cached);
    }
}