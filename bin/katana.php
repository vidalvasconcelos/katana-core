#!/usr/bin/env php
<?php

declare(strict_types=1);

(static function (): void {
    $main = require_once __DIR__ . '/../src/main.php';
    $main([
        Katana\DIRECTORY_CACHE   => getcwd().'/_cache',
        Katana\DIRECTORY_CONTENT => getcwd().'/content',
        Katana\DIRECTORY_PUBLIC  => getcwd().'/public',
    ]);
})();
