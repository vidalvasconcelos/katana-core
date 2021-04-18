<?php

namespace Katana\Engine;

use Illuminate\View\Compilers\BladeCompiler;
use function Katana\str_contains;

class Blade
{
    protected $bladeCompiler;

    /**
     * Blade constructor.
     *
     * @param BladeCompiler $bladeCompiler
     */
    public function __construct(BladeCompiler $bladeCompiler)
    {
        $this->bladeCompiler = $bladeCompiler;

        $this->registerMarkdownDirective();

        $this->registerURLDirective();
    }

    /**
     * Get the blade compiler after extension.
     *
     * @return BladeCompiler
     */
    public function getCompiler()
    {
        return $this->bladeCompiler;
    }

    /**
     * Register the @markdown and @endmarkdown blade directives.
     *
     * @return void
     */
    protected function registerMarkdownDirective()
    {
        $this->bladeCompiler->directive('markdown', function () {
            return "<?php echo \\Katana\\Markdown::parse(<<<'EOT'";
        });

        $this->bladeCompiler->directive('endmarkdown', function () {
            return "\nEOT\n); ?>";
        });
    }

    /**
     * Register the @url blade directive.
     *
     * @return void
     */
    protected function registerURLDirective()
    {
        $this->bladeCompiler->directive('url', function ($expression) {
            $expression = substr($expression, 1, - 1);

            $trailingSlash = ! str_contains($expression, '.') ? '/' : '';

            return "<?php echo str_replace(['///', '//'], '/', \$base_url.'/'.trim({$expression}, '/').'{$trailingSlash}');  ?>";
        });
    }
}
