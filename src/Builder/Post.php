<?php

declare(strict_types=1);

namespace Katana\Builder;

use Illuminate\Filesystem\Filesystem;
use Katana\Config;

final class Post
{
    private string $title;
    private bool $isMarkdown;
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, string $title, bool $isMarkdown)
    {
        $this->filesystem = $filesystem;
        $this->title = $title;
        $this->isMarkdown = $isMarkdown;
    }

    public function build(Config $config): void
    {
        $this->filesystem->put(
            sprintf('/%s/_blog/%s', $config->content(), $this->nameFile()),
            $this->buildTemplate()
        );
    }

    public function nameFile(): string
    {
        $slug = strtolower(trim($this->title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', "-", $slug);
        $extension = ($this->isMarkdown) ? "md" : "blade.php";

        return sprintf('%s-%s-.%s', date('Y-m-d'), $slug, $extension);
    }

    public function buildTemplate(): string
    {
        return ($this->isMarkdown) ?
            "---
            \rview::extends: _includes.blog_post_base
            \rview::yields: post_body
            \rpageTitle: " . $this->title . "
            \rpost::title: " . $this->title . "
            \rpost::date: " . date('F d, Y') . "
            \rpost::brief: Write the description of the post here!
            \r---
            
            \rWrite your post content here!" :

            "@extends('_includes.blog_post_base')
            \r@section('post::title', '" . $this->title . "')
            \r@section('post::date', '" . date('F d, Y') . "')
            \r@section('post::brief', 'Write the description of the post here!')
            \r@section('pageTitle')- @yield('post::title')@stop
            \r@section('post_body')
                \r\t@markdown
                    \r\t\tWrite your the content of the post here!
                \r\t@endmarkdown
            \r@stop";
    }
}
