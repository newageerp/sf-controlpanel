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

class InGeneratorTabs extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorTabs';

    protected PropertiesUtils $propertiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tabTableTemplate = file_get_contents(
            __DIR__ . '/templates/tabs/TabTable.txt'
        );
        $tabTableDataSourceTemplate = file_get_contents(
            __DIR__ . '/templates/tabs/TabTableDataSource.txt'
        );

        $defaultsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/defaults.json';
        $defaultItems = json_decode(
            file_get_contents($defaultsFile),
            true
        );

        $tabsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/tabs.json';
        $tabItems = json_decode(
            file_get_contents($tabsFile),
            true
        );

        $generatedPath = Utils::generatedPath('tabs/tables');
        $dataSourceGeneratedPath = Utils::generatedPath('tabs/tables-data-source');

        foreach ($tabItems as $tabItem) {
            $tpHead = [];
            $tpBody = [];
            $tpRowData = [];
            $tpImports = [];

            $compName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                ucfirst($tabItem['config']['type']) . 'Table'
            );
            $dataSourceCompName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                ucfirst($tabItem['config']['type']) . 'TableDataSource'
            );


            foreach ($tabItem['config']['columns'] as $columnIndex => $column) {
                $colProperty = $this->propertiesUtils->getPropertyForPath($column['path']);
                $tdTemplateData = $this->propertiesUtils->getDefaultPropertyTableValueTemplate($colProperty);
                $tpImports[] = $tdTemplateData['import'];

                $textAlignment = 'textAlignment="' . $this->propertiesUtils->getPropertyTableAlignment($colProperty) . '"';
                $openTagTh = '<Th ' . $textAlignment . '>';
                $openTagTd = '<Td ' . $textAlignment . '>';

                $thTemplate = $openTagTh . '</Th>';
                if ($column['customTitle']) {
                    $thTemplate = $openTagTh . '{t("' . $column['customTitle'] . '")}</Th>';
                } else if ($column['titlePath']) {
                    $prop = $this->propertiesUtils->getPropertyForPath($column['titlePath']);
                    if ($prop) {
                        $thTemplate = $openTagTh . '{t("' . $prop['title'] . '")}</Th>';
                    }
                } else if ($column['path']) {
                    if ($colProperty) {
                        $thTemplate = $openTagTh . '{t("' . $colProperty['title'] . '")}</Th>';
                    }
                }
                $tpHead[] = $thTemplate;

                $tpBody[] = $tdTemplate;

                $pathArray = explode(".", $column['path']);
                $pathArray[0] = 'item';

                $varName = lcfirst(implode(array_map('ucfirst', $pathArray)) . $columnIndex);
                $varValue = implode("?.", $pathArray);
                $tpRowData[] = 'const ' . $varName . ' = ' . $varValue . ';';
                if (count($pathArray) > 2) {
                    $pathArray[count($pathArray) - 1] = 'id';
                    $varNameId = lcfirst(implode(array_map('ucfirst', $pathArray)) . $columnIndex . 'Id');
                    $varValueId = implode("?.", $pathArray);
                    $tpRowData[] = 'const ' . $varNameId . ' = ' . $varValueId . ';';
                }


                $tdTemplate = $openTagTd .
                    str_replace(
                        ['TP_VALUE'],
                        [$varName],
                        $tdTemplateData['template']
                    )
                    . '</Td>';
            }
            $tpHeadStr = implode("\n", $tpHead);
            $tpBodyStr = implode("\n", $tpBody);
            $tpRowDataStr = implode("\n", $tpRowData);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_THEAD',
                    'TP_TBODY',
                    'TP_ROW_DATA',
                    'TP_IMPORT'
                ],
                [
                    $compName,
                    $tpHeadStr,
                    $tpBodyStr,
                    $tpRowDataStr,
                ],
                $tabTableTemplate
            );
            Utils::writeOnChanges($fileName, $generatedContent);

            $sort = [
                ['key' => 'i.id', 'value' => 'DESC']
            ];
            if (isset($tabItem['sort'])) {
                $sort = json_decode($tabItem['sort'], true);
            } else {
                foreach ($defaultItems as $df) {
                    if ($df['config']['schema'] === $tabItem['config']['schema'] &&
                        isset($df['config']['defaultSort']) &&
                        $df['config']['defaultSort']
                    ) {
                        $sort = json_decode($df['config']['defaultSort'], true);
                    }
                }
            }

            $filter = null;
            if (isset($tabItem['predefinedFilter'])) {
                $filter = json_decode($tabItem['predefinedFilter'], true);
            }

            $pageSize = isset($tabItem['config']['pageSize']) && $tabItem['config']['pageSize'] ? $tabItem['config']['pageSize'] : 20;
            $dataSourceFileName = $dataSourceGeneratedPath . '/' . $dataSourceCompName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_TABLE_COMP_NAME',
                    'TP_SCHEMA',
                    'TP_TYPE',
                    'TP_PAGE_SIZE',
                    'TP_SORT',
                    'TP_FILTER',
                ],
                [
                    $dataSourceCompName,
                    $compName,
                    $tabItem['config']['schema'],
                    $tabItem['config']['type'],
                    $pageSize,
                    json_encode($sort),
                    $filter ? json_encode($filter) : 'null',
                ],
                $tabTableDataSourceTemplate
            );
            Utils::writeOnChanges($dataSourceFileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}