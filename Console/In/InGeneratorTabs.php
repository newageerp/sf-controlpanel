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
        $fs = new Filesystem();

        $tabTableTemplate = file_get_contents(
            __DIR__ . '/templates/tabs/TabTable.txt'
        );

        $tabsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/tabs.json';
        $tabItems = json_decode(
            file_get_contents($tabsFile),
            true
        );

        $generatedPath = LocalConfigUtils::getFrontendGeneratedPath() . '/tabs/tables';
        if (!$fs->exists($generatedPath)) {
            $fs->mkdir($generatedPath);
        }

        foreach ($tabItems as $tabItem) {
            $tpHead = [];
            $compName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                ucfirst($tabItem['config']['type']) . 'Table'
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
            }
            $tpHeadStr = implode("\n", $tpHead);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $localContents = '';
            if ($fs->exists($fileName)) {
                $localContents = file_get_contents($fileName);
            }

            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_THEAD'
                ],
                [
                    $compName,
                    $tpHeadStr
                ],
                $tabTableTemplate
            );

            if ($localContents !== $generatedContent) {
                file_put_contents(
                    $fileName,
                    $generatedContent
                );
            }
        }

        return Command::SUCCESS;
    }
}