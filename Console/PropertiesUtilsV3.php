<?php

namespace Newageerp\SfControlpanel\Console;

class PropertiesUtilsV3
{
    protected array $properties = [];

    public function __construct()
    {
        $this->properties = LocalConfigUtils::getCpConfigFileData('properties');
        $this->enumsList = LocalConfigUtils::getCpConfigFileData('enums');
    }

    public static function swapSchemaToI($path)
    {
        $pathA = explode(".", $path);
        $pathA[0] = 'i';
        return implode(".", $pathA);
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
                        isset($prop['typeFormat']) &&
                        $prop['typeFormat']
                    ) {
                        $_schema = $prop['typeFormat'];
                    }
                }
            }
        }
        return null;
    }

    public function getPropertyTableAlignment(?array $property, ?array $column): string
    {
        if (!$property) {
            return 'tw3-text-left';
        }
        $naeType = $this->getPropertyNaeType($property, $column);

        if ($naeType === 'float' || $naeType === 'float4' || $naeType === 'number' || $naeType === 'seconds-to-time') {
            return 'tw3-text-right';
        }

        return 'tw3-text-left';
    }

    public function getPropertyForSchema(string $schema, string $key): ?array
    {
        $properties = $this->getPropertiesForEntitySlug($schema);

        foreach ($properties as $prop) {
            if ($prop['config']['key'] === $key) {
                return $prop['config'];
            }
        }

        return null;
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

    public function propertyHasEnum(array $prop)
    {
        $enumsList = array_filter(
            $this->enumsList,
            function ($item) use ($prop) {
                return $item['config']['entity'] === $prop['entity'] && $item['config']['property'] === $prop['key'];
            }
        );
        return count($enumsList) > 0;
    }

    public function getPropertyEnumsList(array $prop)
    {
        $enumsList = array_filter(
            $this->enumsList,
            function ($item) use ($prop) {
                return $item['config']['entity'] === $prop['entity'] && $item['config']['property'] === $prop['key'];
            }
        );
        $enumsData = [];
        foreach ($enumsList as $enum) {
            $enumsData[] = [
                'sort' => $enum['config']['sort'],
                'title' => $enum['config']['title'],
                'value' => $enum['config']['value'],
            ];
        }

        usort($enumsData, function ($pdfA, $pdfB) {
            if ($pdfA['sort'] < $pdfB['sort']) {
                return -1;
            }
            if ($pdfA['sort'] > $pdfB['sort']) {
                return 1;
            }
            if ($pdfA['title'] < $pdfB['title']) {
                return -1;
            }
            if ($pdfA['title'] > $pdfB['title']) {
                return 1;
            }
            return 0;
        });

        return  array_values(array_map(
            function ($en) use ($prop) {
                $isInt = $prop['config']['type'] === 'integer' || $prop['config']['type'] === 'int' || $prop['config']['type'] === 'number';
                return [
                    'label' => $en['title'],
                    'value' => $isInt ? ((int)$en['value']) : $en['value']
                ];
            },
            $enumsData
        ));
    }

    public function getPropertyNaeType(array $property, array $column = []): string
    {
        if (!isset($property['as'])) {
            $property['as'] = '';
        }
        if (!isset($column['type'])) {
            $column['type'] = '';
        }

        $hasEnum = $this->propertyHasEnum($property);

        $isStatus = $property['as'] === 'status' || $column['type'] === 'status';

        $isStringArray = $property['type'] === 'array' && $property['typeFormat'] === 'string';
        $isArray = $property['type'] === 'array' && !$isStringArray;

        $isFloat = $property['type'] === 'number' && $property['typeFormat'] === 'float';
        $isNumber = $isFloat || ($property['type'] === 'integer' && !$hasEnum);

        $isBoolean = $property['type'] === 'bool' || $property['type'] === 'boolean';
        $isDate = $property['type'] === 'string' && $property['typeFormat'] === 'date';

        $isDateTime = $property['type'] === 'string' && $property['typeFormat'] === 'datetime';

        $isLargeText = $property['type'] === 'string' && $property['typeFormat'] === 'text';

        $isObject = $property['type'] === 'rel';

        $isMultiString = $property['type'] === 'array' && $hasEnum;
        $isMultiNumber = $property['type'] === 'array' && $property['typeFormat'] === 'number' && $hasEnum;

        $isEnumString = $property['type'] === 'string' && $hasEnum;
        $isEnumInteger = ($property['type'] === 'integer' || $property['type'] === 'number') && $hasEnum;

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

    public function getDefaultPropertySearchComparison(?array $property, ?array $column): string
    {
        if (!$property) {
            return 'no';
        }

        $naeType = $this->getPropertyNaeType($property, $column ?: []);

        switch ($naeType) {
            case 'enum_text':
            case 'enum_number':
            case 'bool':
            case 'status':
            case 'enum_multi_number':
            case 'enum_multi_text':
                return 'enum';
                break;
            case 'fileMultiple':
            case 'file':
            case 'object':
            case 'string_array':
            case 'array':
                return 'no';
                break;
            case 'audio':
            case 'image':
            case 'color':
            case 'text':
            case 'string':
                return 'string';
                break;

            case 'number':
            case 'float':
                return 'number';
                break;
            case 'datetime':
            case 'date':
                return 'date';
                break;
            default:
                return 'no';
        }
    }
}
