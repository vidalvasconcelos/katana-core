<?php

namespace Katana\Site;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\Engine\MarkdownFileBuilder;
use Symfony\Component\Finder\SplFileInfo;
use function ends_with;
use const Katana\FileHandlers\KATANA_PUBLIC_DIR;

class BaseHandler
{
    protected $filesystem;
    protected $viewFactory;

    /**
     * The view file.
     *
     * @var SplFileInfo
     */
    protected $file;

    /**
     * The path to the blade view.
     *
     * @var string
     */
    protected $viewPath;

    /**
     * the path to the generated directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * Data to be passed to every view.
     *
     * @var array
     */
    public $viewsData = [];

    /**
     * AbstractHandler constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem, Factory $viewFactory)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
    }

    /**
     * Convert a blade view into a site page.
     *
     * @param SplFileInfo $file
     *
     * @return void
     */
    public function handle(SplFileInfo $file)
    {
        $this->file = $file;
        $this->viewPath = $this->getViewPath();
        $this->directory = $this->getDirectoryPrettyName();
        $this->appendViewInformationToData();

        if (
            @$this->viewsData['enableBlog']
            && @$this->viewsData['postsListView'] == $this->viewPath
        ) {
            $this->prepareBlogIndexViewData();
        }

        $content = $this->getFileContent();

        $this->filesystem->put(
            sprintf(
                '%s/%s',
                $this->prepareAndGetDirectory(),
                ends_with($file->getFilename(), ['.blade.php', 'md']) ? 'index.html' : $file->getFilename()
            ),
            $content
        );
    }

    protected function getFileContent(): string
    {
        if (ends_with($this->file->getFilename(), '.blade.php')) {
            return $this->renderBlade();
        } elseif (ends_with($this->file->getFilename(), '.md')) {
            return $this->renderMarkdown();
        }

        return $this->file->getContents();
    }

    protected function renderBlade(): string
    {
        return $this->viewFactory->make($this->viewPath, $this->viewsData)->render();
    }

    protected function renderMarkdown(): string
    {
        $markdownFileBuilder = new MarkdownFileBuilder($this->filesystem, $this->viewFactory, $this->file, $this->viewsData);
        return $markdownFileBuilder->render();
    }

    protected function prepareAndGetDirectory(): string
    {
        if (! $this->filesystem->isDirectory($this->directory)) {
            $this->filesystem->makeDirectory($this->directory, 0755, true);
        }

        return $this->directory;
    }

    protected function getDirectoryPrettyName(): string
    {
        $fileBaseName = $this->getFileName();
        $fileRelativePath = $this->normalizeWindowsFilepath($this->file->getRelativePath());

        if (in_array($this->file->getExtension(), ['php', 'md']) && $fileBaseName != 'index') {
            $fileRelativePath .= $fileRelativePath ? "/$fileBaseName" : $fileBaseName;
        }

        return KATANA_PUBLIC_DIR.($fileRelativePath ? "/$fileRelativePath" : '');
    }

    protected function getViewPath(): string
    {
        return str_replace(['.blade.php', '.md'], '', $this->file->getRelativePathname());
    }

    /**
     * Prepare the data for the blog landing page.
     *
     * We will pass only the first n posts and a next page path.
     *
     * @return void
     */
    protected function prepareBlogIndexViewData()
    {
        $postsPerPage = @$this->viewsData['postsPerPage'] ?: 5;

        $this->viewsData['nextPage'] = count($this->viewsData['blogPosts']) > $postsPerPage ? '/blog-page/2' : null;
        $this->viewsData['previousPage'] = null;
        $this->viewsData['paginatedBlogPosts'] = array_slice($this->viewsData['blogPosts'], 0, $postsPerPage, true);
    }

    protected function getFileName(SplFileInfo $file = null): string
    {
        $file = $file ?: $this->file;
        return str_replace(['.blade.php', '.php', '.md'], '', $file->getBasename());
    }

    protected function appendViewInformationToData(): void
    {
        $this->viewsData['currentViewPath'] = $this->viewPath;
        $this->viewsData['currentUrlPath'] = ($path = str_replace(KATANA_PUBLIC_DIR, '', $this->directory)) ? $path : '/';
    }

    protected function normalizeWindowsFilepath(string $path): string
    {
        return str_replace("\\", '/', $path);
    }
}
