<?php

namespace Newageerp\SfControlpanel\Service\Tabs;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\Defaults\DefaultsService;
use Newageerp\SfControlpanel\Service\TemplateService;

class TabsService
{
    protected array $tabs = [];

    protected PropertiesUtils $propertiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        $this->tabs = LocalConfigUtils::getCpConfigFileData('tabs');
        $this->propertiesUtils = $propertiesUtils;
    }

    public function getTabForSchemaAndType(string $schema, string $type)
    {
        foreach ($this->tabs as $tabItem) {
            if ($tabItem['config']['schema'] === $schema && $tabItem['config']['type'] === $type) {
                return $tabItem;
            }
        }
        return null;
    }

    public function getFieldsToReturnForTab(array $tab)
    {
        $fieldsToReturn = [];
        foreach ($tab['config']['columns'] as $field) {
            if (isset($field['path']) && $field['path']) {
                $pathA = explode(".", $field['path']);
                $path = $pathA[0] . '.' . $pathA[1];
                $fieldProperty = $this->propertiesUtils->getPropertyForPath($path);
                $fieldObjectProperty = null;

                if ($fieldProperty) {
                    $pathArray = explode(".", $field['path']);
                    array_shift($pathArray);
                    $objectPath = implode(".", $pathArray);
                    if ($fieldProperty['type'] !== 'array') {
                        $fieldsToReturn[] = $objectPath;
                    }
                    if (count($pathArray) >= 2) {
                        $fieldsToReturn[] = $pathA[1] . '.id';
                    }
                }
            }
        }
        $fieldsToReturn = array_values(array_unique($fieldsToReturn));
        return $fieldsToReturn;
    }
}
