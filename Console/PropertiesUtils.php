<?php

namespace Newageerp\SfControlpanel\Console;

class PropertiesUtils
{
    protected array $properties = [];

    protected EntitiesUtils $entitiesUtils;

    public function __construct(EntitiesUtils $entitiesUtils)
    {
        $propertiesFile = LocalConfigUtils::getPhpCachePath() . '/properties.json';
        $this->properties = [];
        if (file_exists($propertiesFile)) {
            $this->properties = json_decode(
                file_get_contents($propertiesFile),
                true
            );
        }
        $this->entitiesUtils = $entitiesUtils;
    }

    /**
     * @return array|mixed
     */
    public function getProperties(): mixed
    {
        return $this->properties;
    }

    public function getRelPropertiesForTarget(string $target)
    {
        return array_filter(
            $this->properties,
            function ($item) use ($target) {
                return $item['type'] === 'rel' && $item['format'] === $target;
            }
        );
    }

    public function getArraySchemasForTarget(string $target)
    {
        return array_filter(
            $this->properties,
            function ($item) use ($target) {
                return $item['type'] === 'array' && $item['format'] === $target;
            }
        );
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
            case 'seconds-to-time':
                return [
                    "import" => 'import { SecondsToTime } from "@newageerp/data.table.seconds-to-time";',
                    "template" => '<SecondsToTime seconds={TP_VALUE}/>'
                ];
                break;
            case 'status-short':
                $compName = Utils::fixComponentName(ucfirst($property['schema']) . 'Statuses');
                return [
                    "import" => 'import { ' . $compName . ' } from "../../statuses/badges/' . $compName . '";',
                    "template" => '{' . $compName . '(TP_VALUE, "TP_KEY", true, true)}'
                ];
                break;
            case 'status':
                $compName = Utils::fixComponentName(ucfirst($property['schema']) . 'Statuses');
                return [
                    "import" => 'import { ' . $compName . ' } from "../../statuses/badges/' . $compName . '";',
                    "template" => '{' . $compName . '(TP_VALUE, "TP_KEY", false, true)}'
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
                    "import" => 'import { String } from "@newageerp/data.table.string";',
                    "template" => '<String value={TP_VALUE.join(", ")}/>'
                ];
                break;
            case 'float':
                return [
                    "import" => 'import { Float } from "@newageerp/data.table.float";',
                    "template" => '<Float value={TP_VALUE}/>'
                ];
                break;
            case 'float4':
                return [
                    "import" => 'import { Float4 } from "@newageerp/data.table.float-4";',
                    "template" => '<Float4 value={TP_VALUE}/>'
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
                if (isset($column['editable']) && $column['editable']) {
                    return [
                        "import" => 'import { TextEditable } from "@newageerp/data.table.text-editable";',
                        "template" => '<TextEditable value={TP_VALUE} propertyKey={"TP_KEY"} schema={"TP_SCHEMA"} elementId={item.id} saveFunc={OpenApi.useUSave}/>'
                    ];
                }
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
            default:
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
        }
    }

    public function getDefaultPropertyViewValueTemplate(?array $property, ?array $column)
    {
        if (!$column) {
            $column = [];
        }

        if (!$property || !$column) {
            return [
                "import" => '',
                "template" => '<Fragment/>'
            ];
        }

        $naeType = $this->getPropertyNaeType($property, $column);

        switch ($naeType) {
            case 'seconds-to-time':
                return [
                    "import" => 'import { SecondsToTime } from "@newageerp/data.table.seconds-to-time";',
                    "template" => '<SecondsToTime seconds={TP_VALUE}/>'
                ];
                break;
            case 'status-short':
                $compName = Utils::fixComponentName(ucfirst($property['schema']) . 'Statuses');
                return [
                    "import" => 'import { ' . $compName . ' } from "../../statuses/badges/' . $compName . '";',
                    "template" => '{' . $compName . '(TP_VALUE, "TP_KEY", true)}'
                ];
                break;
            case 'status':
                $compName = Utils::fixComponentName(ucfirst($property['schema']) . 'Statuses');
                return [
                    "import" => 'import { ' . $compName . ' } from "../../statuses/badges/' . $compName . '";',
                    "template" => '{' . $compName . '(TP_VALUE, "TP_KEY")}'
                ];
                break;
            case 'file':
                return [
                    "import" => 'import { Image } from "@newageerp/data.table.file";',
                    "template" => '<File val={TP_VALUE}/>'
                ];
            case 'fileMultiple':
                return [
                    "import" => 'import { Image } from "@newageerp/data.table.file-multiple";',
                    "template" => '<FileMultiple val={TP_VALUE}/>'
                ];
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
                    "import" => 'import { String } from "@newageerp/data.table.string";',
                    "template" => '<String value={TP_VALUE.join(", ")}/>'
                ];
                break;
            case 'float':
                return [
                    "import" => 'import { Float } from "@newageerp/data.table.float";',
                    "template" => '<Float value={TP_VALUE}/>'
                ];
                break;
            case 'float4':
                return [
                    "import" => 'import { Float } from "@newageerp/data.table.float-4";',
                    "template" => '<Float4 value={TP_VALUE}/>'
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
                    "template" => '<Input onChange={TP_ON_CHANGE_STRING} value={TP_VALUE}/>'
                ];
            case 'text':
                return [
                    "import" => 'import { Textarea } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<Textarea onChange={TP_ON_CHANGE_STRING} value={TP_VALUE}/>'
                ];
            case 'float':
                return [
                    "import" => 'import { InputFloat } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<InputFloat onChangeFloat={TP_ON_CHANGE} value={TP_VALUE} className={"field-float"}/>'
                ];
            case 'float4':
                return [
                    "import" => 'import { InputFloat4 } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<InputFloat4 onChangeFloat={TP_ON_CHANGE} value={TP_VALUE} className={"field-float"}/>'
                ];
            case 'number':
                return [
                    "import" => 'import { InputInt } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<InputInt onChangeInt={TP_ON_CHANGE} value={TP_VALUE} className={"field-number"}/>'
                ];
            case 'bool':
                return [
                    "import" => 'import { Checkbox } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<Checkbox onChange={TP_ON_CHANGE} value={TP_VALUE}/>'
                ];
            case 'date':
                return [
                    "import" => 'import { Datepicker } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<Datepicker value={TP_VALUE} onChange={TP_ON_CHANGE}  className={"field-date"}/>'
                ];
            case 'enum_text':
            case 'enum_number':
                $compName = Utils::fixComponentName(ucfirst($property['schema']) . 'EnumsOptions');
                $compFileName = Utils::fixComponentName(ucfirst($property['schema']) . 'Enums');
                return [
                    "import" => [
                        'import { ' . $compName . ' } from "../../enums/view/' . $compFileName . '";',
                        'import { SelectAdvId } from "@newageerp/ui.form.base.form-pack";'
                    ],
                    "template" => '<SelectAdvId withIcon={true} options={' . $compName . '[\'TP_KEY\']} selectedId={TP_VALUE} onSelectId={TP_ON_CHANGE} />'
                ];
            case 'object':
                return [
                    "import" => '',
                    "template" => '<CUSTOM_NAME 
                        selectedId={TP_VALUE}
                        onSelectId={TP_ON_CHANGE}
                        parentElement={element}
                        />'
                ];
            case 'file':
                return [
                    "import" => 'import { FilePicker } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<FilePicker width="tw3-w-full" val={TP_VALUE} onChange={TP_ON_CHANGE}  folder={"TP_SCHEMA/TP_KEY"}/>'
                ];
            case 'fileMultiple':
                return [
                    "import" => 'import { FilePickerMultiple } from "@newageerp/ui.form.base.form-pack";',
                    "template" => '<FilePickerMultiple width="tw3-w-full" val={TP_VALUE} onChange={TP_ON_CHANGE}  folder={"TP_SCHEMA/TP_KEY"}/>'
                ];
            case 'array':
                [$tabSchema, $tabType] = explode(':', $column['arrayRelTab']);

                return [
                    'import' => '',
                    'template' => '<UI.Form.Array schema={"' . $tabSchema . '"} title="" value={TP_VALUE} onChange={TP_ON_CHANGE} tab={functions.tabs.getTabFromSchemaAndType("' . $tabSchema . '", "' . $tabType . '")} parentElement={element} />'
                ];
            default:
                return [
                    "import" => '',
                    "template" => '<Fragment/>'
                ];
        }
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

    public function getDefaultPropertySearchOptions(?array $property, ?array $column): array
    {
        if (!$property) {
            return [];
        }

        $naeType = $this->getPropertyNaeType($property, $column ?: []);

        switch ($naeType) {
            case 'bool':
                return [
                    ['label' => 'Taip', 'value' => 1],
                    ['label' => 'Ne', 'value' => 0]
                ];
                break;
            case 'enum_text':
            case 'enum_number':
            case 'enum_multi_number':
            case 'enum_multi_text':
                return $property['enum'];
            case 'status':
                $statusData = LocalConfigUtils::getCpConfigFileData('statuses');
                $statusSchema = array_filter(
                    $statusData,
                    function ($item) use ($property) {
                        return $item['config']['entity'] === $property['schema'];
                    }
                );

                $output = [];
                foreach ($statusSchema as $status) {
                    $output[] = [
                        'label' => $status['config']['text'],
                        'value' => $status['config']['status']
                    ];
                }
                return $output;
                break;
            case 'fileMultiple':
            case 'file':
            case 'object':
            case 'string_array':
            case 'array':
            case 'audio':
            case 'image':
            case 'color':
            case 'text':
            case 'string':
            case 'number':
            case 'float':
            case 'datetime':
            case 'date':
                return [];
            default:
                return [];
        }
    }
}
