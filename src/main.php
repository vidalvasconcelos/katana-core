<?php

namespace Katana;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Katana\Commands\BuildFactory;
use Katana\Commands\PostFactory;
use Symfony\Component\Console\Application;

const DIRECTORY_CACHE   = 'DIRECTORY_PATH_CACHE_';
const DIRECTORY_CONTENT = 'DIRECTORY_PATH_CONTENT';
const DIRECTORY_PUBLIC  = 'DIRECTORY_PATH_PUBLIC';

return static function (array $setting): void {
    require_once __DIR__.'/../vendor/autoload.php';

    $filesystem = new Filesystem();
    $compiler = new BladeCompiler($filesystem, $setting[DIRECTORY_CACHE]);

    $resolver = new EngineResolver();
    $resolver->register('blade', static function () use ($compiler): CompilerEngine {
        return new CompilerEngine($compiler);
    });

    $dispatcher = new Dispatcher();
    $dispatcher->listen('creating: *', static function ($arg): void {
        var_dump($arg);
    });

    $viewFinder = new FileViewFinder($filesystem, $setting[DIRECTORY_CONTENT]);
    $factory = new Factory($resolver, $viewFinder, $dispatcher);

    $application = new Application();
    $application->add(BuildFactory::make($factory, $filesystem));
    $application->add(PostFactory::make($filesystem));
    $application->run();
};