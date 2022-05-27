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

        $generatedPath = Utils::generatedPath('editforms/forms-wide');
        $generatedPathDataSource = Utils::generatedPath('editforms/forms-wide-data-source');

        foreach ($editItems as $editItem) {
            $tpRows = [];
            $tpImports = [];

            $compName = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                ucfirst($editItem['config']['type']) . 'WideForm'
            );
            $compNameDataSource = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                ucfirst($editItem['config']['type']) . 'WideFormDataSource'
            );

            $fieldsToReturn = [];

            foreach ($editItem['config']['fields'] as $fieldIndex => $field) {
                $fieldProperty = $this->propertiesUtils->getPropertyForPath($field['path']);

                $fieldPropertyNaeType = '';
                if ($fieldProperty) {
                    $fieldPropertyNaeType = $this->propertiesUtils->getPropertyNaeType($fieldProperty, $field);

                    $pathArray = explode(".", $field['path']);
                    array_shift($pathArray);
                    $fieldsToReturn[] = implode(".", $pathArray);
                }

                $fieldTemplateData = $this->propertiesUtils->getDefaultPropertyEditValueTemplate($fieldProperty, $field);
                $tpImports[] = $fieldTemplateData['import'];

                $tpValue = 'element.' . $fieldProperty['key'];
                $tpOnChange = '(e: any) => onChange(\'' . $fieldProperty['key'] . '\', e.target.value)';

                $fieldTemplate = str_replace(
                    ['TP_VALUE', 'TP_ON_CHANGE'],
                    [$tpValue, $tpOnChange],
                    $fieldTemplateData['template']
                );

                $labelInner = '';
                if ($field['hideLabel']) {
                    $labelInner = ' label={<Label>{t(\'' . $fieldProperty['title'] . '\')}</Label>}';
                }

                $content = '<WideRow' . $labelInner . ' control={' . $fieldTemplate . '}/>';
                $tpRows[] = $content;
            }

            $tpRowsStr = implode("\n", $tpRows);
            $tpImportsStr = implode("\n", array_unique($tpImports));

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_ROWS',
                    'TP_IMPORT'
                ],
                [
                    $compName,
                    $tpRowsStr,
                    $tpImportsStr,
                ],
                $editFormTemplate
            );
            Utils::writeOnChanges($fileName, $generatedContent);

            // DATA SOURCE
            $fileName = $generatedPathDataSource . '/' . $compNameDataSource . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_COMP_NAME_DATA_SOURCE',
                    'TP_SCHEMA',
                    'TP_FIELDS_TO_RETURN'
                ],
                [
                    $compName,
                    $compNameDataSource,
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