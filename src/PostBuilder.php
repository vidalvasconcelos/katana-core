<?php

namespace Katana;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Katana\FileHandlers\BlogPostHandler;
use Illuminate\Filesystem\Filesystem;
use Katana\FileHandlers\BaseHandler;
use Illuminate\View\Factory;
use Illuminate\Support\Str;

class PostBuilder
{   
    /**
     * The title instance.
     *
     * @var Filesystem
     */
    private $title;

    /**
     * The template instance.
     *
     * @var Filesystem
     */
    private $template;

    /**
     * The FileSystem instance.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * PostBuilder constructor.
     *
     * @param Filesystem $filesystem
     * @param string $environment
     */
    public function __construct(Filesystem $filesystem, string $title, string $template = null)
    {
        $this->filesystem = $filesystem;

        $this->title = $title;

        $this->template = $template;
    }

    /**
     * Build the template view post.
     *
     * @return void
     */
    public function build()
    {
        $this->filesystem->put(

            sprintf('/%s/_blog/%s', DIRECTORY_CONTENT, $this->nameFile()),

            $this->buildTemplate()

        );
    }

    /**
     * Return the default template of the new post
     *
     * @return string  
     */
    public function buildTemplate()
    {   
        return ($this->template)?
           "---
            \rview::extends: _includes.blog_post_base
            \rview::yields: post_body
            \rpageTitle: ".$this->title."
            \rpost::title: ".$this->title."
            \rpost::date: ".date('F d, Y')."
            \rpost::brief: Write the description of the post here!
            \r---
            
            \rWrite your post content here!":

           "@extends('_includes.blog_post_base')
            \r@section('post::title', '".$this->title."')
            \r@section('post::date', '".date('F d, Y')."')
            \r@section('post::brief', 'Write the description of the post here!')
            \r@section('pageTitle')- @yield('post::title')@stop
            \r@section('post_body')
                \r\t@markdown
                    \r\t\tWrite your the content of the post here!
                \r\t@endmarkdown
            \r@stop";
    }

    /**
     * Return the name file of the post
     *
     * @return string
     */
    public function nameFile()
    {   
        $slug = strtolower(trim($this->title));

        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);

        $slug = preg_replace('/-+/', "-", $slug);

        $extension = ($this->template)? "md": "blade.php";

        return sprintf('%s-%s-.%s', date('Y-m-d'), $slug, $extension);
    }

}
