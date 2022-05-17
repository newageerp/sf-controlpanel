<?php

namespace Newageerp\SfControlpanel\Console;

class PropertiesUtils
{
    protected array $properties = [];

    public function __construct()
    {
        $this->properties = json_decode(
            file_get_contents(LocalConfigUtils::getPhpCachePath() . '/properties.json'),
            true
        );
    }

    public function getPropertyForPath(string $_path): ?array
    {
        $path = explode(".", $_path);
        $pathLen = count($path);
        if ($pathLen === 1) {
            return null;
        } else {
            $_schema = '';
            foreach ($path as $i => $pathPart) {
                if ($i === 0) {
                    $_schema = $pathPart;
                } else if ($i === $pathLen - 1) {
                    return $this->getPropertyForSchema($_schema, $pathPart);
                } else {
                    $_lastSchema = $_schema;
                    $_schema = '';
                    $prop = $this->getPropertyForSchema($_lastSchema, $pathPart);
                    if (
                        ($prop['type'] === 'array' || $prop['type'] === 'rel') &&
                        isset($prop['format']) &&
                        $prop['format']
                    ) {
                        $_schema = $prop['format'];
                    }
                }
            }
        }
        return null;
    }

    public function getPropertyForSchema(string $schema, string $key): ?array
    {
        foreach ($this->properties as $property) {
            if ($property['key'] === $key && $property['schema'] === $schema) {
                return $property;
            }
        }
        return null;
    }

    public function getPropertyNaeType(array $property): string
    {
        $isStringArray = $property['type'] === 'array' && $property['format'] === 'string';
        $isArray = $property['type'] === 'array' && !$isStringArray;

        $isFloat = $property['type'] === 'number' && $property['format'] === 'float';
        $isNumber = $isFloat || ($property['type'] === 'integer' && !isset($property['enum']));

        $isBoolean = $property['type'] === 'bool' || $property['type'] === 'boolean';
        $isDate = $property['type'] === 'string' && $property['format'] === 'date';

        $isDateTime = $property['type'] === 'string' && $property['format'] === 'datetime';

        $isLargeText = $property['type'] === 'string' && $property['format'] === 'text';

        $isObject = $property['type'] === 'rel';

        $isMultiString = $property['type'] === 'array' && isset($property['enum']) && count($property['enum']) > 0;
        $isMultiNumber = $property['type'] === 'array' &&
            $property['format'] === 'number' &&
            isset($property['enum']) && count($property['enum']) > 0;

        $isEnumString = $property['type'] === 'string' &&
            isset($property['enum']) && count($property['enum']) > 0;
        $isEnumInteger = ($property['type'] === 'integer' || $property['type'] === 'number') && isset($property['enum']) && count($property['enum']) > 0;

        if ($isObject) {
            return 'object';
        } else if ($isStringArray) {
            return 'string_array';
        } else if ($isFloat) {
            return 'float';
        } else if ($isNumber) {
            return 'number';
        } else if ($isDate) {
            return 'date';
        } else if ($isDateTime) {
            return 'datetime';
        } else if ($isBoolean) {
            return 'bool';
        } else if ($isLargeText) {
            return 'text';
        } else if ($isMultiNumber) {
            return 'enum_multi_number';
        } else if ($isMultiString) {
            return 'enum_multi_text';
        } else if ($isEnumString) {
            return 'enum_text';
        } else if ($isEnumInteger) {
            return 'enum_number';
        } else if ($isArray) {
            return 'array';
        }
        return 'string';
    }

    public function getPropertyTableAlignment(?array $property): string
    {
        if (!$property) {
            return 'tw3-text-left';
        }
        $naeType = $this->getPropertyNaeType($property);

        if ($naeType === 'float' || $naeType === 'number') {
            return 'tw3-text-right';
        }

        return 'tw3-text-left';
    }
}