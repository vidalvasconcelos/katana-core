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
use Symfony\Component\Console\Application;

final class Katana
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function handle(Config $config): void
    {
        $this->registerCommands($config);
        $this->application->run();
    }

    private function registerCommands(Config $config): void
    {
        $filesystem = new Filesystem();
        $factory = $this->createViewFactory($config, $filesystem);

        $this->application->addCommands([
            new Post($factory, $filesystem, $config),
            new Build($factory, $filesystem, $config),
        ]);
    }

    private function createViewFactory(Config $config, Filesystem $filesystem): Factory
    {
        $resolver = new EngineResolver();
        $bladeCompiler = $this->createBladeCompiler($config, $filesystem);

        $resolver->register('blade', static function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });

        $dispatcher = new Dispatcher();

        $dispatcher->listen('creating: *', static function (): void {
            /**
             * On rendering Blade views we will mute error reporting as
             * we don't care about undefined variables or type
             * mistakes during compilation.
             */
            error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
        });

        return new Factory(
            $resolver,
            new FileViewFinder($filesystem, [$config->contentPath()]),
            $dispatcher
        );
    }

    private function createBladeCompiler(Config $config, Filesystem $filesystem): BladeCompiler
    {
        $cache = $config->cachePath();

        if (!$filesystem->isDirectory($cache)) {
            $filesystem->makeDirectory($cache);
        }

        $blade = new Blade(
            new BladeCompiler($filesystem, $cache)
        );

        return $blade->getCompiler();
    }
}