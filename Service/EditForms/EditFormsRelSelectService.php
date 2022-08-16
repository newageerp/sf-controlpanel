<?php

namespace Newageerp\SfControlpanel\Service\EditForms;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\TemplateService;

class EditFormsRelSelectService
{
    protected PropertiesUtils $propertiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        $this->propertiesUtils = $propertiesUtils;
    }

    public function generate()
    {
        $tService = new TemplateService('v2/edit-forms/rels/edit-forms-rel-select.html.twig');

        $editItems = LocalConfigUtils::getCpConfigFileData('edit');

        foreach ($editItems as $editItem) {
            foreach ($editItem['config']['fields'] as $fieldIndex => $field) {
                $pathA = explode(".", $field['path']);
                $path = $pathA[0] . '.' . $pathA[1];

                $fieldProperty = $this->propertiesUtils->getPropertyForPath($path);
                if (!$fieldProperty) {
                    continue;
                }
                $type = $this->propertiesUtils->getPropertyNaeType($fieldProperty, $field);

                if ($type === 'object') {
                    $relPathArray = explode(".", $field['path']);
                    array_shift($relPathArray);
                    array_shift($relPathArray);
                    array_unshift($relPathArray, $fieldProperty['format']);
                    $objectRelPath = implode(".", $relPathArray);

                    $fieldObjectProperty = $this->propertiesUtils->getPropertyForPath($objectRelPath);
                    $objectSort = [];
                    if ($fieldObjectProperty) {
                        $objectSort = $this->entitiesUtils->getDefaultSortForSchema($fieldObjectProperty['schema']);
                    }

                    $extraFilter = '';
                    if (isset($field['fieldDependency']) && $field['fieldDependency']) {
                        [$filterKey, $filterValue] = explode(":", $field['fieldDependency']);
                        $extraFilter =  'filters={[
                                    {"and": [
                                        ["' . $filterKey . '", "=", element.' . $filterValue . ', true]
                                    ]}
                                ]}
                ';
                    }

                    $slug = $editItem['config']['schema'];
                    $slugUc = Utils::fixComponentName($slug);
                    $folderPath = Utils::generatedV2Path('edit-forms/' . $slugUc . '/rel-select');

                    $compName = Utils::fixComponentName(
                        $editItem['config']['type'] . '-' . $pathA[1] . '-' . $pathA[2]
                    );

                    $tService->writeToFileOnChanges(
                        $folderPath . '/' . $compName . '.tsx',
                        [
                            'compName' => $compName,
                            'objectSchema' => $fieldObjectProperty ? $fieldObjectProperty['schema'] : '',
                            'schema' => $fieldProperty ? $fieldProperty['schema'] : '',
                            'sort' => json_encode($objectSort),
                            'extraFilter' => $extraFilter,
                        ]
                    );
                }
            }
        }
    }
}
