<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

class SnakeCaseUnderscore
{
    /**
     * @param string $string
     * @return string
     */
    public static function underscoreToSnakeCase(string $string): string
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $str[0] = strtolower($str[0]);
        return $str;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function snakeCaseToUnderscore(string $string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }
}
