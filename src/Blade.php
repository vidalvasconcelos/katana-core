<?php

declare(strict_types=1);

namespace Katana;

use Illuminate\View\Compilers\BladeCompiler;

final class Blade
{
    private BladeCompiler $compiler;

    public function __construct(BladeCompiler $compiler)
    {
        $this->compiler = $compiler;
        $this->registerMarkdownDirective();
        $this->registerURLDirective();
    }

    private function registerMarkdownDirective(): void
    {
        $this->compiler->directive('markdown', function () {
            return "<?php echo \\Katana\\Markdown::parse(<<<'EOT'";
        });

        $this->compiler->directive('endmarkdown', function () {
            return "\nEOT\n); ?>";
        });
    }

    private function registerURLDirective(): void
    {
        $this->compiler->directive('url', function ($expression) {
            $expression = substr($expression, 1, -1);
            $trailingSlash = !str_contains($expression, '.') ? '/' : '';

            return "<?php echo str_replace(['///', '//'], '/', \$base_url.'/'.trim({$expression}, '/').'{$trailingSlash}');  ?>";
        });
    }

    public function getCompiler(): BladeCompiler
    {
        return $this->compiler;
    }
}
