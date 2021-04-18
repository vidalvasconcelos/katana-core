<?php

namespace Katana\Engine;

use Mni\FrontYAML\Parser;
use Parsedown;

class Markdown
{
    public static function parse($text): string
    {
        $parser = new Parsedown();
        $text = static::cleanLeadingSpace($text);

        return $parser->text($text);
    }

    public static function parseWithYAML(string $text): array
    {
        $parser = new Parser();
        $parsed = $parser->parse($text);

        return [$parsed->getContent(), $parsed->getYAML()];
    }

    /**
     * Remove initial leading space from each line
     *
     * Since @markdown can be placed inside any HTML element, there might
     * be leading space due to code editor indentation, here we trim it
     * to avoid compiling the whole markdown block as a code block.
     *
     * @param string $text
     *
     * @return string
     */
    protected static function cleanLeadingSpace(string $text): string
    {
        $i = 0;

        while (! $firstLine = explode("\n", $text)[$i]) {
            $i ++;
        }

        preg_match('/^( *)/', $firstLine, $matches);

        return preg_replace('/^[ ]{'.strlen($matches[1]).'}/m', '', $text);
    }
}