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
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorEditForms extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorEditForms';

    protected PropertiesUtils $propertiesUtils;

    protected EntitiesUtils $entitiesUtils;

    public function __construct(
        PropertiesUtils $propertiesUtils,
        EntitiesUtils   $entitiesUtils,
    )
    {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
        $this->entitiesUtils = $entitiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/smarty',
        ]);

        $editFormTemplate = file_get_contents(
            __DIR__ . '/templates/edit-forms/EditForm.txt'
        );
        $editFormDataSourceTemplate = $twig->load('edit-forms/EditFormDataSource.html.twig');


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
            $tpCompactRows = [];
            $tpWideRows = [];
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

            foreach ($editItem['config']['fields'] as $fieldIndex => $field) {
                if (isset($field['type']) && ($field['type'] === 'separator' || $field['type'] === 'horizontal-separator' || $field['type'] === 'tagCloud')) {
                    $content = '<div className="h-6"></div>';
                    $tpWideRows[] = $content;
                    $tpCompactRows[] = $content;
                } else if (isset($field['type']) && $field['type'] === 'label') {
                    $labelInner = ' label={<Label>{t(\'' . $field['text'] . '\')}</Label>}';

                    $content = '<WideRow' . $labelInner . ' control={<Fragment/>}/>';
                    $tpWideRows[] = $content;

                    $content = '<CompactRow' . $labelInner . ' control={<Fragment/>}/>';
                    $tpCompactRows[] = $content;
                } else if (isset($field['path']) && $field['path']) {
                    $pathA = explode(".", $field['path']);
                    $path = $pathA[0] . '.' . $pathA[1];
                    $fieldProperty = $this->propertiesUtils->getPropertyForPath($path);
                    $fieldObjectProperty = null;
                    $objectSort = [];
                    $fieldPropertyNaeType = '';
                    if ($fieldProperty) {
                        $fieldPropertyNaeType = $this->propertiesUtils->getPropertyNaeType($fieldProperty, $field);

                        $pathArray = explode(".", $field['path']);
                        array_shift($pathArray);
                        $objectPath = implode(".", $pathArray);
                        $fieldsToReturn[] = $objectPath;
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

                    $tpValueObj = 'element.' . $fieldProperty['key'].'?.id';
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
                            $tpObjectSortStr,
                        ],
                        $fieldTemplateData['template']
                    );

                    $labelInner = '';
                    if (!$field['hideLabel']) {
                        $labelInner = ' label={<Label>{t(\'' . $fieldProperty['title'] . '\')}</Label>}';
                    }

                    $content = '<WideRow' . $labelInner . ' control={' . $fieldTemplate . '}/>';
                    $tpWideRows[] = $content;

                    $content = '<CompactRow' . $labelInner . ' control={' . $fieldTemplate . '}/>';
                    $tpCompactRows[] = $content;
                }
            }

            $fieldsToReturn = array_values(array_unique($fieldsToReturn));

            $tpWideRowsStr = implode("\n", $tpWideRows);
            $tpCompactRowsStr = implode("\n", $tpCompactRows);
            $tpImportsStr = implode("\n", array_unique($tpImports));

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_COMPACT_ROWS',
                    'TP_WIDE_ROWS',
                    'TP_IMPORT'
                ],
                [
                    $compName,
                    $tpCompactRowsStr,
                    $tpWideRowsStr,
                    $tpImportsStr,
                ],
                $editFormTemplate
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

        return Command::SUCCESS;
    }
}