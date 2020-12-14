<?php

declare(strict_types=1);

namespace Katana;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Katana\Command\Build;
use Katana\Command\Post;
use Symfony\Component\Console\Application as SymfonyConsole;

final class Katana
{
    protected Factory $viewFactory;
    protected Filesystem $filesystem;
    protected SymfonyConsole $application;

    public function __construct(SymfonyConsole $application)
    {
        $this->registerConstants();
        $this->application = $application;
        $this->filesystem = new Filesystem();
        $this->viewFactory = $this->createViewFactory();
    }

    protected function registerConstants(): void
    {
        // A place to save Blade's cached compilations.
        define('KATANA_CACHE_DIR', getcwd() . '/_cache');

        // A place to read site source files.
        define('KATANA_CONTENT_DIR', getcwd() . '/content');

        // A place to output the generated site.
        define('KATANA_PUBLIC_DIR', getcwd() . '/public');
    }

    protected function createViewFactory(): Factory
    {
        $resolver = new EngineResolver();
        $bladeCompiler = $this->createBladeCompiler();

        $resolver->register('blade', function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });

        $dispatcher = new Dispatcher();

        $dispatcher->listen('creating: *', function () {
            /**
             * On rendering Blade views we will mute error reporting as
             * we don't care about undefined variables or type
             * mistakes during compilation.
             */
            error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
        });

        return new Factory(
            $resolver,
            new FileViewFinder($this->filesystem, [KATANA_CONTENT_DIR]),
            $dispatcher
        );
    }

    protected function createBladeCompiler(): BladeCompiler
    {
        if (!$this->filesystem->isDirectory(KATANA_CACHE_DIR)) {
            $this->filesystem->makeDirectory(KATANA_CACHE_DIR);
        }

        $blade = new Blade(
            new BladeCompiler($this->filesystem, KATANA_CACHE_DIR)
        );

        return $blade->getCompiler();
    }

    public function handle(): void
    {
        $this->registerCommands();
        $this->application->run();
    }

    protected function registerCommands(): void
    {
        $this->application->addCommands([
            new Build($this->viewFactory, $this->filesystem),
            new Post($this->viewFactory, $this->filesystem)
        ]);
    }
}