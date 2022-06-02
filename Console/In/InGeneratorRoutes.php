<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InGeneratorRoutes extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorRoutes';

    protected PropertiesUtils $propertiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routesTemplate = file_get_contents(
            __DIR__ . '/templates/routes/Routes.txt'
        );

        $tabsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/tabs.json';
        $tabItems = json_decode(
            file_get_contents($tabsFile),
            true
        );
        $editsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/edit.json';
        $editItems = json_decode(
            file_get_contents($editsFile),
            true
        );

        $generatedPath = Utils::generatedPath('routes');

        $routes = [];

        $imports = [];

        foreach ($tabItems as $tabItem) {
            $dataSourceCompName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                ucfirst($tabItem['config']['type']) . 'TableDataSource'
            );
            $imports[] = 'import ' . $dataSourceCompName . ' from "../tabs/tables-data-source/' . $dataSourceCompName . '"';

            $routes[] = '
                <Route path={"/u/'.$tabItem['config']['schema'].'/'.$tabItem['config']['type'].'/list/"}>
                    <' . $dataSourceCompName . ' />
                </Route>';
        }

        foreach ($editItems as $editItem) {
            $compNameDataSource = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                ucfirst($editItem['config']['type']) . 'FormDataSource'
            );
            $imports[] = 'import ' . $compNameDataSource . ' from "../editforms/forms-data-source/' . $compNameDataSource . '"';

            $routes[] = '
                <Route path={"/u/'.$tabItem['config']['schema'].'/'.$tabItem['config']['type'].'/edit/:id"}>
                    <' . $compNameDataSource . ' />
                </Route>';
        }

        $fileName = $generatedPath . '/AppRoutes.tsx';
        $generatedContent = str_replace(
            [
                'TP_ROUTES',
                'TP_IMPORTS',
            ],
            [
                implode("\n", $routes),
                implode("\n", $imports),
            ],
            $routesTemplate
        );
        Utils::writeOnChanges($fileName, $generatedContent);

        return Command::SUCCESS;
    }
}