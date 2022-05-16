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
            $compName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                ucfirst($tabItem['config']['type']) . 'Table'
            );
            $dataSourceCompName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                ucfirst($tabItem['config']['type']) . 'TableDataSource'
            );

            foreach ($tabItem['config']['columns'] as $column) {
                $thTemplate = '<Th></Th>';
                if ($column['customTitle']) {
                    $thTemplate = '<Th>{t("' . $column['customTitle'] . '")}</Th>';
                } else if ($column['titlePath']) {
                    $prop = $this->propertiesUtils->getPropertyForPath($column['titlePath']);
                    if ($prop) {
                        $thTemplate = '<Th>{t("' . $prop['title'] . '")}</Th>';
                    }
                } else if ($column['path']) {
                    $prop = $this->propertiesUtils->getPropertyForPath($column['path']);
                    if ($prop) {
                        $thTemplate = '<Th>{t("' . $prop['title'] . '")}</Th>';
                    }
                }
                $tpHead[] = $thTemplate;

                $tdTemplate = '<Td></Td>';

                $tpBody[] = $tdTemplate;
            }
            $tpHeadStr = implode("\n", $tpHead);
            $tpBodyStr = implode("\n", $tpBody);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_THEAD',
                    'TP_TBODY',
                ],
                [
                    $compName,
                    $tpHeadStr,
                    $tpBodyStr,
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
                ],
                [
                    $dataSourceCompName,
                    $compName,
                    $tabItem['config']['schema'],
                    $tabItem['config']['type'],
                    $pageSize,
                    json_encode($sort),
                ],
                $tabTableDataSourceTemplate
            );
            Utils::writeOnChanges($dataSourceFileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}