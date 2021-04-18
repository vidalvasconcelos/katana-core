<?php

namespace Katana\Post;

use DateTimeImmutable;

final class Builder
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $filepath;

    /**
     * @var string
     */
    private $template;

    public function __construct(string $directory, string $title, string $template = null)
    {
        $this->title = $title;
        $this->setFilename($title, $template);
        $this->setFilepath($directory);
        $this->setTemplate($title, $template, new DateTimeImmutable('now'));
    }

    public function toTitle(): string
    {
        return $this->title;
    }

    public function toFilename(): string
    {
        return $this->filename;
    }

    public function toFilepath(): string
    {
        return $this->filepath;
    }

    public function toTemplate(): string
    {
        return $this->template;
    }

    public function setFilepath(string $directory): void
    {
        $this->filepath = "/$directory/_blog/{$this->toFilename()}";
    }

    private function setFilename(string $title, bool $template): void
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', "-", $slug);

        $this->filename = sprintf('%s-%s-.%s', date('Y-m-d'), $slug, $template ? "md" : "blade.php");
    }

    public function setTemplate(string $title, bool $template, DateTimeImmutable $date): void
    {
        $this->template = $template
            ? $this->getMarkdownTemplate($title, $date)
            : $this->getBladeTemplate($title, $date);
    }

    private function getMarkdownTemplate(string $title, DateTimeImmutable $date): string
    {
        return "---
            \rview::extends: _includes.blog_post_base
            \rview::yields: post_body
            \rpageTitle: " . $title . "
            \rpost::title: " . $title . "
            \rpost::date: " . $date->format('F d, Y') . "
            \rpost::brief: Write the description of the post here!
            \r---
            
            \rWrite your post content here!";
    }

    private function getBladeTemplate(string $title, DateTimeImmutable $date): string
    {
        return "@extends('_includes.blog_post_base')
            \r@section('post::title', '" . $title . "')
            \r@section('post::date', '" . $date->format('F d, Y') . "')
            \r@section('post::brief', 'Write the description of the post here!')
            \r@section('pageTitle')- @yield('post::title')@stop
            \r@section('post_body')
                \r\t@markdown
                    \r\t\tWrite your the content of the post here!
                \r\t@endmarkdown
            \r@stop";
    }
}
