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

class InGeneratorViewForms extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorViewForms';

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

        $viewFormTemplate = $twig->load('view-forms/view-form.html.twig');
        $viewFormWidgetTemplate = $twig->load('view-forms/view-form-widget.html.twig');

        $viewsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/view.json';
        $viewItems = [];
        if (file_exists($viewsFile)) {
            $viewItems = json_decode(
                file_get_contents($viewsFile),
                true
            );
        }

        $generatedPath = Utils::generatedPath('viewforms/forms');
        $generatedPathWidget = Utils::generatedPath('viewforms/widgets');

        foreach ($viewItems as $viewItem) {
            $compact = isset($viewItem['config']['compact']) && $viewItem['config']['compact'];
            $compactPrefix = $compact ? 'Compact' : 'Wide';

            $generateForWidget = isset($viewItem['config']['generateForWidget']) && $viewItem['config']['generateForWidget'];
            if (!$generateForWidget) {
                continue;
            }
            $tpCompactRows = [];
            $tpImports = [];

            $compName = Utils::fixComponentName(
                ucfirst($viewItem['config']['schema']) .
                ucfirst($viewItem['config']['type']) . 'ViewForm'
            );
//            $compNameDataSource = Utils::fixComponentName(
//                ucfirst($editItem['config']['schema']) .
//                ucfirst($editItem['config']['type']) . 'FormDataSource'
//            );

            $fieldsToReturn = [];

            foreach ($viewItem['config']['fields'] as $fieldIndex => $field) {
                if (isset($field['type']) && ($field['type'] === 'separator' || $field['type'] === 'horizontal-separator')) {
                    $content = '<div className="h-4"></div>';
                    $tpCompactRows[] = $content;
                } else if (isset($field['type']) && $field['type'] === 'label') {
                    $labelInner = ' label={<Label>{t(\'' . $field['text'] . '\')}</Label>}';

                    $content = '<' . $compactPrefix . 'Row' . $labelInner . ' control={<Fragment/>}/>';
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
                            $fieldsToReturn[] = $path . '.id';
                        }

                        $fieldObjectProperty = $this->propertiesUtils->getPropertyForPath($objectPath);
                        if ($fieldObjectProperty) {
                            $objectSort = $this->entitiesUtils->getDefaultSortForSchema($fieldObjectProperty['schema']);
                        }
                    }

                    $fieldTemplateData = $this->propertiesUtils->getDefaultPropertyViewValueTemplate($fieldProperty, $field);
                    $importTmp = $fieldTemplateData['import'];
                    if (!is_array($importTmp)) {
                        $importTmp = [$importTmp];
                    }
                    foreach ($importTmp as $import) {
                        $tpImports[] = $import;
                    }

                    $tpValue = 'element.' . $fieldProperty['key'];

                    $tpObjectSortStr = json_encode($objectSort, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $fieldTemplate = str_replace(
                        [
                            'TP_VALUE',
                            'TP_SCHEMA',
                            'TP_KEY',
                            'TP_OBJECT_SCHEMA',
                            'TP_OBJECT_KEY',
                            'TP_OBJECT_SORT'
                        ],
                        [
                            $tpValue,
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

                    $content = '<' . $compactPrefix . 'Row' . $labelInner . ' control={' . $fieldTemplate . '}/>';
                    $tpCompactRows[] = $content;
                }
            }

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = $viewFormTemplate->render(
                [
                    'compName' => $compName,
                    'imports' => array_unique($tpImports),
                    'compactRows' => $tpCompactRows,
                    'compactPrefix' => $compactPrefix,
                ]
            );
            Utils::writeOnChanges($fileName, $generatedContent);

            $fileName = $generatedPathWidget . '/' . $compName . 'Widget.tsx';
            $generatedContent = $viewFormWidgetTemplate->render(
                [
                    'compName' => $compName,
                    'hookSchemaName' => Utils::fixComponentName($viewItem['config']['schema']),
                    'title' => $viewItem['config']['title'] ?? '',
                    'editForm' => $viewItem['config']['editForm'] ?? '',
                    'schema' => $viewItem['config']['schema'],
                ]
            );
            Utils::writeOnChanges($fileName, $generatedContent);

        }

        return Command::SUCCESS;
    }
}