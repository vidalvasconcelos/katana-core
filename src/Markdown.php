<?php

declare(strict_types=1);

namespace Katana;

use Mni\FrontYAML\Parser;
use Parsedown;

final class Markdown
{
    public static function parse(string $text): string
    {
        $parser = new Parsedown();
        $text = self::cleanLeadingSpace($text);

        return $parser->text($text);
    }

    public static function cleanLeadingSpace(string $text): string
    {
        $i = 0;
        while (!$firstLine = explode("\n", $text)[$i]) {
            $i++;
        }

        preg_match('/^( *)/', $firstLine, $matches);

        return preg_replace('/^[ ]{' . strlen($matches[1]) . '}/m', '', $text);
    }

    public static function parseWithYAML(string $text): array
    {
        $parser = new Parser();
        $parsed = $parser->parse($text);

        return [$parsed->getContent(), $parsed->getYAML()];
    }
}