<?php

namespace Newageerp\SfControlpanel\Console;

class PropertiesUtils
{
    protected array $properties = [];

    protected EntitiesUtils $entitiesUtils;

    public function __construct(EntitiesUtils $entitiesUtils)
    {
        $this->properties = json_decode(
            file_get_contents(LocalConfigUtils::getPhpCachePath() . '/properties.json'),
            true
        );
        $this->entitiesUtils = $entitiesUtils;
    }

    /**
     * @return array|mixed
     */
    public function getProperties(): mixed
    {
        return $this->properties;
    }

    public function getClassNameForPath(string $path): string
    {
        $className = "";
        $property = $this->getPropertyForPath($path);
        if ($property) {
            $className = $this->entitiesUtils->getClassNameBySlug($property['format']);
        }
        return $className;
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

    public function getPropertyNaeType(array $property, array $column): string
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

        if ($isStatus) {
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

    public function getPropertyTableAlignment(?array $property, ?array $column): string
    {
        if (!$property) {
            return 'tw3-text-left';
        }
        $naeType = $this->getPropertyNaeType($property, $column);

        if ($naeType === 'float' || $naeType === 'number') {
            return 'tw3-text-right';
        }

        return 'tw3-text-left';
    }

    public function getDefaultPropertyTableValueTemplate(?array $property, ?array $column)
    {
        if (!$property || !$column) {
            return [
                "import" => '',
                "template" => '<Fragment/>'
            ];
        }

        $naeType = $this->getPropertyNaeType($property, $column);

        switch ($naeType) {
            case 'status':
                $compName = Utils::fixComponentName(ucfirst($property['schema']) . 'Statuses');
                return [
                    "import" => 'import { ' . $compName . ' } from "../../statuses/badges/' . $compName . '";',
                    "template" => '{' . $compName . '(TP_VALUE, "TP_KEY")}'
                ];
                break;
            case 'file':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'fileMultiple':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'image':
                return [
                    "import" => 'import { Image } from "@newageerp/data.table.image";',
                    "template" => '<Image value={TP_VALUE}/>'
                ];
                break;
            case 'audio':
                return [
                    "import" => 'import { Audio } from "@newageerp/data.table.audio";',
                    "template" => '<Audio value={TP_VALUE}/>'
                ];
                break;
            case 'color':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'object':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'string_array':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'float':
                return [
                    "import" => 'import { Float } from "@newageerp/data.table.float";',
                    "template" => '<Float value={TP_VALUE}/>'
                ];
                break;
            case 'number':
                return [
                    "import" => 'import { Int } from "@newageerp/data.table.int";',
                    "template" => '<Int value={TP_VALUE}/>'
                ];
                break;
            case 'date':
                return [
                    "import" => 'import { Date } from "@newageerp/data.table.date";',
                    "template" => '<Date value={TP_VALUE}/>'
                ];
                break;
            case 'datetime':
                return [
                    "import" => 'import { Datetime } from "@newageerp/data.table.datetime";',
                    "template" => '<Datetime value={TP_VALUE}/>'
                ];
                break;
            case 'bool':
                return [
                    "import" => 'import { Bool } from "@newageerp/data.table.bool";',
                    "template" => '<Bool value={TP_VALUE}/>'
                ];
                break;
            case 'text':
                return [
                    "import" => 'import { Text } from "@newageerp/data.table.text";',
                    "template" => '<Text value={TP_VALUE}/>'
                ];
                break;
            case 'enum_multi_number':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'enum_multi_text':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'enum_text':
                $compName = 'get' . Utils::fixComponentName(ucfirst($property['schema']) . 'Enums');
                $compFileName = Utils::fixComponentName(ucfirst($property['schema']) . 'Enums');
                return [
                    "import" => 'import { ' . $compName . ' } from "../../enums/view/' . $compFileName . '";',
                    "template" => '{' . $compName . '("TP_KEY", TP_VALUE)}'
                ];
                break;
            case 'enum_number':
                $compName = 'get' . Utils::fixComponentName(ucfirst($property['schema']) . 'Enums');
                $compFileName = Utils::fixComponentName(ucfirst($property['schema']) . 'Enums');
                return [
                    "import" => 'import { ' . $compName . ' } from "../../enums/view/' . $compFileName . '";',
                    "template" => '{' . $compName . '("TP_KEY", TP_VALUE.toString())}'
                ];
                break;
            case 'array':
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
                break;
            case 'string':
                return [
                    "import" => 'import { String } from "@newageerp/data.table.string";',
                    "template" => '<String value={TP_VALUE}/>'
                ];
                break;
        }
    }


    public function getDefaultPropertyEditValueTemplate(?array $property, ?array $column)
    {
        if (!$property || !$column) {
            return [
                "import" => '',
                "template" => '<Fragment/>'
            ];
        }

        $naeType = $this->getPropertyNaeType($property, $column);

        switch ($naeType) {
            case 'string':
                return [
                    "import" => 'import { Input } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<Input onChange={TP_ON_CHANGE} value={TP_VALUE}/>'
                ];
                break;
            case 'float':
                return [
                    "import" => 'import { InputFloat } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<InputFloat onChangeFloat={TP_ON_CHANGE} value={TP_VALUE}/>'
                ];
                break;
            case 'number':
                return [
                    "import" => 'import { InputInt } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<InputInt onChangeInt={TP_ON_CHANGE} value={TP_VALUE}/>'
                ];
                break;
            case 'date':
                return [
                    "import" => 'import { Datepicker } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<Datepicker value={TP_VALUE} onChange={TP_ON_CHANGE}/>'
                ];
                break;
            default :
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
        }
    }
}