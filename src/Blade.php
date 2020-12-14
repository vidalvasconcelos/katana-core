<?php

declare(strict_types=1);

namespace Katana;

use Illuminate\View\Compilers\BladeCompiler;

final class Blade
{
    protected BladeCompiler $bladeCompiler;

    public function __construct(BladeCompiler $bladeCompiler)
    {
        $this->bladeCompiler = $bladeCompiler;
        $this->registerMarkdownDirective();
        $this->registerURLDirective();
    }

    protected function registerMarkdownDirective(): void
    {
        $this->bladeCompiler->directive('markdown', function () {
            return "<?php echo \\Katana\\Markdown::parse(<<<'EOT'";
        });

        $this->bladeCompiler->directive('endmarkdown', function () {
            return "\nEOT\n); ?>";
        });
    }

    protected function registerURLDirective(): void
    {
        $this->bladeCompiler->directive('url', function ($expression) {
            $expression = substr($expression, 1, -1);
            $trailingSlash = !str_contains($expression, '.') ? '/' : '';

            return "<?php echo str_replace(['///', '//'], '/', \$base_url.'/'.trim({$expression}, '/').'{$trailingSlash}');  ?>";
        });
    }

    public function getCompiler(): BladeCompiler
    {
        return $this->bladeCompiler;
    }
}
