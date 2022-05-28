<?php

namespace Newageerp\SfControlpanel\Console\In;

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

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $editFormTemplate = file_get_contents(
            __DIR__ . '/templates/edit-forms/EditForm.txt'
        );
        $editFormDataSourceTemplate = file_get_contents(
            __DIR__ . '/templates/edit-forms/EditFormDataSource.txt'
        );

        $editsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/edit.json';
        $editItems = json_decode(
            file_get_contents($editsFile),
            true
        );

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
                $pathA = explode(".", $field['path']);
                $path = $pathA[0] . '.' . $pathA[1];
                $fieldProperty = $this->propertiesUtils->getPropertyForPath($path);

                $fieldPropertyNaeType = '';
                if ($fieldProperty) {
                    $fieldPropertyNaeType = $this->propertiesUtils->getPropertyNaeType($fieldProperty, $field);

                    $pathArray = explode(".", $field['path']);
                    array_shift($pathArray);
                    $fieldsToReturn[] = implode(".", $pathArray);
                    if (count($pathArray) > 2) {
                        $fieldsToReturn[] = $path . '.id';
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

                $fieldTemplate = str_replace(
                    ['TP_VALUE', 'TP_ON_CHANGE_STRING', 'TP_ON_CHANGE'],
                    [$tpValue, $tpOnChangeString, $tpOnChange],
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

            $fieldsToReturn[] = array_values(array_unique($fieldsToReturn));

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
            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME_DATA_SOURCE',
                    'TP_COMP_NAME',
                    'TP_SCHEMA',
                    'TP_FIELDS_TO_RETURN'
                ],
                [
                    $compNameDataSource,
                    $compName,
                    $editItem['config']['schema'],
                    json_encode($fieldsToReturn, JSON_PRETTY_PRINT),
                ],
                $editFormDataSourceTemplate
            );
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}