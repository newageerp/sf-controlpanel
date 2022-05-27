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

        $editsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/edit.json';
        $editItems = json_decode(
            file_get_contents($editsFile),
            true
        );

        $generatedPath = Utils::generatedPath('editforms/forms');

        foreach ($editItems as $editItem) {
            $tpRows = [];
            $tpImports = [];

            $compName = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                ucfirst($editItem['config']['type']) . 'Form'
            );

            foreach ($editItem['config']['fields'] as $fieldIndex => $field) {
                $fieldProperty = $this->propertiesUtils->getPropertyForPath($field['path']);

                $fieldPropertyNaeType = '';
                if ($fieldProperty) {
                    $fieldPropertyNaeType = $this->propertiesUtils->getPropertyNaeType($fieldProperty, $field);
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

                $content = '<WideRow>'.$fieldTemplate.'</WideRow>';
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

        }

        return Command::SUCCESS;
    }
}