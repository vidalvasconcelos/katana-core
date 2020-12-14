<?php

namespace Katana;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Katana\Commands\BuildCommand;
use Katana\Commands\PostCommand;
use Symfony\Component\Console\Application as SymfonyConsole;

class Katana
{
    /**
     * The Symfony console instance.
     *
     * @var SymfonyConsole
     */
    protected $application;

    /**
     * The view factory instance.
     *
     * @var Factory
     */
    protected $viewFactory;

    /**
     * The file system instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Katana constructor.
     *
     * @param SymfonyConsole $application
     */
    public function __construct(SymfonyConsole $application)
    {
        $this->registerConstants();

        $this->application = $application;

        $this->filesystem = new Filesystem();

        $this->viewFactory = $this->createViewFactory();
    }

    /**
     * Register global constants.
     *
     * @return void
     */
    protected function registerConstants()
    {
        // A place to save Blade's cached compilations.
        define('KATANA_CACHE_DIR', getcwd() . '/_cache');

        // A place to read site source files.
        define('KATANA_CONTENT_DIR', getcwd() . '/content');

        // A place to output the generated site.
        define('KATANA_PUBLIC_DIR', getcwd() . '/public');
    }

    /**
     * Create the view factory with a Blade Compiler.
     *
     * @return Factory
     */
    protected function createViewFactory()
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

    /**
     * Create the Blade Compiler instance.
     *
     * @return BladeCompiler
     */
    protected function createBladeCompiler()
    {
        if (!$this->filesystem->isDirectory(KATANA_CACHE_DIR)) {
            $this->filesystem->makeDirectory(KATANA_CACHE_DIR);
        }

        $blade = new Blade(
            new BladeCompiler($this->filesystem, KATANA_CACHE_DIR)
        );

        return $blade->getCompiler();
    }

    /**
     * Handle incoming console requests.
     *
     * @return void
     */
    public function handle()
    {
        $this->registerCommands();

        $this->application->run();
    }

    /**
     * Register application commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->application->addCommands([
            new BuildCommand($this->viewFactory, $this->filesystem),
            new PostCommand($this->viewFactory, $this->filesystem)
        ]);
    }
}