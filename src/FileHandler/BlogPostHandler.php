<?php

declare(strict_types=1);

namespace Katana\FileHandler;

use Katana\Config;
use Katana\Markdown;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

final class BlogPostHandler extends BaseHandler
{
    public function getPostData(Config $config, SplFileInfo $file): object
    {
        $this->file = $file;

        if ($this->file->getExtension() == 'md') {
            $postData = Markdown::parseWithYAML($this->file->getContents())[1];
        } else {
            $view = $this->factory->make(str_replace('.blade.php', '', $this->file->getRelativePathname()));
            $postData = $view->getFactory()->getSections();

            $view->render();
        }

        // Get only values with keys starting with post::
        $postData = Arr::where($postData, static function ($key): bool {
            return Str::startsWith($key, 'post::');
        });

        // Remove 'post::' from $postData keys
        foreach ($postData as $key => $val) {
            $postData[str_replace('post::', '', $key)] = $val;

            unset($postData[$key]);
        }

        $postData['path'] = str_replace($config->public(), '', $this->getDirectoryPrettyName($config)) . '/';

        return json_decode(json_encode($postData), false);
    }

    protected function getDirectoryPrettyName(Config $config): string
    {
        $pathName = $this->normalizePath($this->file->getPathname());

        // If the post is inside a child directory of the _blog directory then
        // we deal with it like regular site files and generate a nested
        // directories based post path with exact file name.
        if ($this->isInsideBlogDirectory($pathName)) {
            return str_replace('/_blog/', '/', parent::getDirectoryPrettyName());
        }

        $fileBaseName = $this->getFileName();

        $fileRelativePath = $this->getBlogPostSlug($fileBaseName);

        return $config->public() . "/$fileRelativePath";
    }

    protected function isInsideBlogDirectory(string $pathName): bool
    {
        return Str::is('*/_blog/*/*', $pathName);
    }

    protected function getBlogPostSlug(string $fileBaseName): string
    {
        preg_match('/^(\d{4}-\d{2}-\d{2})-(.*)/', $fileBaseName, $matches);

        return $matches[2] . '-' . str_replace('-', '', $matches[1]);
    }
}
