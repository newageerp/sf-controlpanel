<?php

namespace Newageerp\SfControlpanel\Console;

class PropertiesUtilsV3
{
    protected array $properties = [];

    public function __construct()
    {
        $propertiesFile = LocalConfigUtilsV3::getConfigCpPath() . '/properties.json';
        $this->properties = [];
        if (file_exists($propertiesFile)) {
            $this->properties = json_decode(
                file_get_contents($propertiesFile),
                true
            );
        }
    }

    public function getPropertiesForEntitySlug(string $slug)
    {
        return array_filter(
            $this->properties,
            function ($item) use ($slug) {
                return $item['config']['entity'] === $slug;
            }
        );
    }

    public function getPropertyNaeType(array $property, array $column = []): string
    {
        if (!isset($property['as'])) {
            $property['as'] = '';
        }
        if (!isset($column['type'])) {
            $column['type'] = '';
        }

        $isStatus = $property['as'] === 'status' || $column['type'] === 'status';

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

        $isFile = $property['as'] === 'file';
        $isFileMultiple = $property['as'] === 'fileMultiple';
        $isColor = $property['as'] === 'color';
        $isImage = $property['as'] === 'image';
        $isAudio = $property['as'] === 'audio';

        if ($column && isset($column['customTemplate'])) {
            return $column['customTemplate'];
        } else if ($isStatus) {
            return 'status';
        } else if ($isFile) {
            return 'file';
        } else if ($isFileMultiple) {
            return 'fileMultiple';
        } else if ($isColor) {
            return 'color';
        } else if ($isImage) {
            return 'image';
        } else if ($isAudio) {
            return 'audio';
        } else if ($isObject) {
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
}
