<?php

/**
 * @file
 */

namespace Pantheon\TerminusAliases\Model;

use Consolidation\Comments\Comments;
use Symfony\Component\Yaml\Yaml;

class Template
{
    /**
     * Return the path to the template
     */
    public static function path($filename)
    {
        return dirname(dirname(__DIR__)) . "/templates/$filename";
    }

    /**
     * Load the template
     */
    public static function load($filename)
    {
        $path = static::path($filename);
        $contents = file_get_contents($path);

        return $contents;
    }

    /**
     * Load and makes replacements in the template
     */
    public static function replace($filename, $replacements)
    {
        $contents = static::load($filename);
        $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        return $contents;
    }

    /**
     * Template::process loads a template, makes all of the provided
     * replacements, and then removes the unwanted parts that are left
     * over per the rules below.
     */
    public static function process($filename, $replacements)
    {
        $contents = static::replace($filename, $replacements);
        $contents = static::removeUnwantedParts($contents);

        return $contents;
    }

    /**
     * Tamplate::removeUnwantedParts removes leftover markers from the template.
     *
     * Rule: If there are any unreplaced variables (e.g. '{{foo}}')
     * on a line that begins with '##', then remove all lines that
     * begin with '##'.  Otherwise, replace '##' at the beginning of
     * lines with '  '.
     */
    protected static function removeUnwantedParts($contents)
    {
        if (preg_match('%^##[^\r\n]+{{%m', $contents)) {
            return preg_replace('%^##.*[\r\n]+%m', '', $contents);
        }

        return preg_replace('%^##%m', '  ', $contents);
    }
}
