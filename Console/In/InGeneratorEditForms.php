<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Service\Tabs\TabsService;
use Newageerp\SfControlpanel\Service\TemplateService;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorEditForms extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorEditForms';

    protected PropertiesUtils $propertiesUtils;

    protected EntitiesUtils $entitiesUtils;

    protected TabsService $tabsService;

    public function __construct(
        PropertiesUtils $propertiesUtils,
        EntitiesUtils   $entitiesUtils,
        TabsService $tabsService,
    ) {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
        $this->entitiesUtils = $entitiesUtils;
        $this->tabsService = $tabsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/smarty',
        ]);

        $editFormTemplate = $twig->load('edit-forms/EditForm.html.twig');
        $editFormDataSourceTemplate = $twig->load('edit-forms/EditFormDataSource.html.twig');


        $tabs = LocalConfigUtils::getCpConfigFileData('tabs');

        $editsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/edit.json';
        $editItems = [];
        if (file_exists($editsFile)) {
            $editItems = json_decode(
                file_get_contents($editsFile),
                true
            );
        }

        $generatedPath = Utils::generatedPath('editforms/forms');
        $generatedPathDataSource = Utils::generatedPath('editforms/forms-data-source');

        foreach ($editItems as $editItem) {
            $requiredFields = $this->entitiesUtils->getRequiredBySlug($editItem['config']['schema']);

            $tpImports = [];

            $compName = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                    ucfirst($editItem['config']['type']) . 'Form'
            );
            $compNameDataSource = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                    ucfirst($editItem['config']['type']) . 'FormDataSource'
            );

            $fieldsToReturn = [];

            $rows = [];

            foreach ($editItem['config']['fields'] as $fieldIndex => $field) {
                $labelClassName = isset($field['labelClassName']) && $field['labelClassName'] ? $field['labelClassName'] : 'tw3-w-56';
                $inputClassName = isset($field['inputClassName']) && $field['inputClassName'] ? $field['inputClassName'] : '';

                $stepGroup = isset($field['stepGroup']) && $field['stepGroup'] ? $field['stepGroup'] : '-';
                $lineGroup = isset($field['lineGroup']) && $field['lineGroup'] ? $field['lineGroup'] : 'line-group-' . $fieldIndex;

                if (!isset($rows[$stepGroup])) {
                    $rows[$stepGroup] = [];
                }
                if (!isset($rows[$stepGroup][$lineGroup])) {
                    $rows[$stepGroup][$lineGroup] = [];
                }

                if (isset($field['type']) && $field['type'] === 'tagCloud') {
                    // $tpImports[] = "import { UI } from '@newageerp/nae-react-ui';";
                    $content = '<div>
                        <UI.Content.TagCloud
                            updateElement={onChange}
                            field={"' . $field['tagCloudField'] . '"}
                            action={"' . $field['tagCloudAction'] . '"}
                            val={
                            element["' . $field['tagCloudField'] . '"] ? element["' . $field['tagCloudField'] . '"] : ""
                            }
                        />
                    </div>';
                    $rows[$stepGroup][$lineGroup][] = ['w' => $content, 'c' => $content, 'needCheck' => false];
                } else if (isset($field['type']) && ($field['type'] === 'separator' || $field['type'] === 'horizontal-separator' || $field['type'] === 'tagCloud')) {
                    $content = '<div className="h-6"></div>';

                    $rows[$stepGroup][$lineGroup][] = ['w' => $content, 'c' => $content, 'needCheck' => false];
                } else if (isset($field['type']) && $field['type'] === 'label') {
                    $labelInner = ' label={<Label>{t(\'' . $field['text'] . '\')}</Label>}';

                    $contentW = '<WideRow autoWidth={true} labelClassName="' . $labelClassName . '" ' . $labelInner . ' control={<Fragment/>}/>';
                    $contentC = '<CompactRow' . $labelInner . ' control={<Fragment/>}/>';

                    $rows[$stepGroup][$lineGroup][] = ['w' => $contentW, 'c' => $contentC, 'needCheck' => false];
                } else if (isset($field['path']) && $field['path']) {


                    $pathA = explode(".", $field['path']);
                    $path = $pathA[0] . '.' . $pathA[1];
                    $fieldProperty = $this->propertiesUtils->getPropertyForPath($path);
                    $fieldObjectProperty = null;
                    $objectSort = [];
                    $fieldPropertyNaeType = '';
                    $isRequired = in_array($fieldProperty['key'], $requiredFields);

                    if ($fieldProperty) {



                        $fieldPropertyNaeType = $this->propertiesUtils->getPropertyNaeType($fieldProperty, $field);

                        $pathArray = explode(".", $field['path']);
                        array_shift($pathArray);
                        $objectPath = implode(".", $pathArray);
                        if ($fieldProperty['type'] !== 'array') {
                            $fieldsToReturn[] = $objectPath;
                        }
                        if (count($pathArray) >= 2) {
                            $fieldsToReturn[] = $pathA[1] . '.id';


                            $relPathArray = explode(".", $field['path']);
                            array_shift($relPathArray);
                            array_shift($relPathArray);
                            array_unshift($relPathArray, $fieldProperty['format']);
                            $objectRelPath = implode(".", $relPathArray);

                            $fieldObjectProperty = $this->propertiesUtils->getPropertyForPath($objectRelPath);
                            if ($fieldObjectProperty) {
                                $objectSort = $this->entitiesUtils->getDefaultSortForSchema($fieldObjectProperty['schema']);
                            }
                        }

                        if (isset($field['arrayRelTab']) && $field['arrayRelTab']) {
                            [$tabSchema, $tabType] = explode(':', $field['arrayRelTab']);
                            $tab = $this->tabsService->getTabForSchemaAndType($tabSchema, $tabType);
                            if ($tab) {
                                $tabFieldsToReturn = $this->tabsService->getFieldsToReturnForTab($tab);
                                $fieldsToReturn[] = $objectPath . ':' . implode(',' . $tabFieldsToReturn);
                            }
                        }
                    }

                    $fieldTemplateData = $this->propertiesUtils->getDefaultPropertyEditValueTemplate($fieldProperty, $field);
                    $importTmp = $fieldTemplateData['import'];
                    if (!is_array($importTmp)) {
                        $importTmp = [$importTmp];
                    }
                    foreach ($importTmp as $import) {
                        $tpImports[] = $import;
                    }

                    $tpValue = 'element.' . $fieldProperty['key'];
                    $tpOnChange = '(e: any) => onChange(\'' . $fieldProperty['key'] . '\', e)';
                    $tpOnChangeString = '(e: any) => onChange(\'' . $fieldProperty['key'] . '\', e.target.value)';

                    $tpValueObj = 'element.' . $fieldProperty['key'] . '?.id';
                    $tpOnChangeObj = '(e: any) => onChange(\'' . $fieldProperty['key'] . '\', {id: e})';

                    $tpObjectSortStr = json_encode($objectSort, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $fieldTemplate = str_replace(
                        [
                            'TP_VALUE_OBJ',
                            'TP_VALUE',
                            'TP_ON_CHANGE_OBJ',
                            'TP_ON_CHANGE_STRING',
                            'TP_ON_CHANGE',
                            'TP_SCHEMA',
                            'TP_KEY',
                            'TP_OBJECT_SCHEMA',
                            'TP_OBJECT_KEY',
                            'TP_OBJECT_SORT'
                        ],
                        [
                            $tpValueObj,
                            $tpValue,
                            $tpOnChangeObj,
                            $tpOnChangeString,
                            $tpOnChange,
                            $fieldProperty['schema'],
                            $fieldProperty['key'],
                            $fieldObjectProperty ? $fieldObjectProperty['schema'] : '',
                            $fieldObjectProperty ? $fieldObjectProperty['key'] : '',
                            $tpObjectSortStr
                        ],
                        $fieldTemplateData['template']
                    );

                    $labelInner = '';
                    if (!$field['hideLabel']) {
                        $labelInner = ' label={<Label required={' . ($isRequired ? 'true' : 'false') . '}>{t(\'' . $fieldProperty['title'] . '\')}</Label>}';
                    }


                    $contentW = '<WideRow autoWidth={true} controlClassName="' . $inputClassName . '" labelClassName="' . $labelClassName . '" ' . $labelInner . ' control={' . $fieldTemplate . '}/>';
                    // $tpWideRows[] = $content;

                    $contentC = '<CompactRow' . $labelInner . ' control={' . $fieldTemplate . '}/>';
                    // $tpCompactRows[] = $content;

                    $rows[$stepGroup][$lineGroup][] = ['w' => $contentW, 'c' => $contentC, 'needCheck' => true, 'propertyKey' => $fieldProperty['key']];
                }
            }
            $maxCols = 1;
            foreach ($rows as $row) {
                $maxCols = max($maxCols, count($row));
            }

            $fieldsToReturn = array_values(array_unique($fieldsToReturn));

            // $tpWideRowsStr = implode("\n", $tpWideRows);
            // $tpCompactRowsStr = implode("\n", $tpCompactRows);
            $tpImportsStr = implode("\n", array_unique($tpImports));

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = $editFormTemplate->render(
                [
                    'TP_SCHEMA' => $editItem['config']['schema'],
                    'TP_COMP_NAME' => $compName,
                    'TP_IMPORT' => $tpImportsStr,
                    'rows' => $rows,
                    'maxCols' => $maxCols,
                    'editType' => $editItem['config']['type'],
                ]
            );

            Utils::writeOnChanges($fileName, $generatedContent);

            // DATA SOURCE
            $fileName = $generatedPathDataSource . '/' . $compNameDataSource . '.tsx';

            $generatedContent = $editFormDataSourceTemplate->render([
                'TP_COMP_NAME_DATA_SOURCE' => $compNameDataSource,
                'TP_COMP_NAME' => $compName,
                'TP_SCHEMA' => $editItem['config']['schema'],
                'TP_FIELDS_TO_RETURN' => json_encode($fieldsToReturn, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'toolbarTitle' => $this->entitiesUtils->getTitleBySlug($editItem['config']['schema'])
            ]);

            Utils::writeOnChanges($fileName, $generatedContent);
        }

        // EDIT POPUP
        $editPopupT = new TemplateService('edit-forms/EditPopup.html.twig');
        $editPopupMapT = new TemplateService('edit-forms/EditPopupMap.html.twig');

        $folder = Utils::generatedPath('editforms/popups');

        $components = [];

        foreach ($editItems as $editItem) {
            $compName = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                    ucfirst($editItem['config']['type']) . 'EditPopup'
            );

            $components[] = [
                'compName' => $compName,
                'schema' => $editItem['config']['schema'],
                'type' => $editItem['config']['type']
            ];

            $fileName = $folder . '/' . $compName . '.tsx';
            $editPopupT->writeToFileOnChanges(
                $fileName,

                [
                    'compName' => $compName
                ]
            );
        }

        $editPopupMapT->writeToFileOnChanges(
            Utils::generatedPath('editforms') . '/EditPopup.tsx',
            [
                'components' => $components
            ]
        );

        return Command::SUCCESS;
    }
}
