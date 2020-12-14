<?php

namespace Katana;

use Mni\FrontYAML\Parser;
use Parsedown;

class Markdown
{
    /**
     * Parse markdown
     *
     * @param $text
     *
     * @return string
     */
    static function parse($text)
    {
        $parser = new Parsedown();

        $text = static::cleanLeadingSpace($text);

        return $parser->text($text);
    }

    /**
     * Remove initial leading space from each line
     *
     * Since @markdown can be placed inside any HTML element, there might
     * be leading space due to code editor indentation, here we trim it
     * to avoid compiling the whole markdown block as a code block.
     *
     * @param $text
     *
     * @return string
     */
    protected static function cleanLeadingSpace($text)
    {
        $i = 0;

        while (!$firstLine = explode("\n", $text)[$i]) {
            $i++;
        }

        preg_match('/^( *)/', $firstLine, $matches);

        return preg_replace('/^[ ]{' . strlen($matches[1]) . '}/m', '', $text);
    }

    /**
     * Parse markdown with YAML headers
     *
     * This method returns an array of: content as the first member and
     * YAML values as the second member.
     *
     * @param string $text
     *
     * @return array
     */
    public static function parseWithYAML($text)
    {
        $parser = new Parser();

        $parsed = $parser->parse($text);

        return [$parsed->getContent(), $parsed->getYAML()];
    }
}