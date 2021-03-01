<?php


namespace OpenProvider\API;


class CamelCaseUnderscoreConverter
{
    public static function convertToCamelCase($params)
    {
        $convertedData = [];
        foreach ($params as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = self::convertToCamelCase($value);
            }
            $key = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $convertedData[$key] = $value;
        }

        return $convertedData;
    }

    public static function convertToUnderscore($params)
    {
        $convertedData = [];
        foreach ($params as $key => $value) {
            if (is_array($params[$key]) || is_object($params[$key])) {
                $value = self::convertToUnderscore($value);
            }
            $key = strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $key));
            $convertedData[$key] = $value;
        }

        return $convertedData;
    }
}