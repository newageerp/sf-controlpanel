<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorEnums extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorEnums';

    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        parent::__construct();
        $this->menuService = $menuService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $statusItemsTemplate = file_get_contents(
            __DIR__ . '/templates/enums/EnumItems.txt'
        );

        $generatedPath = Utils::generatedPath('enums/view');

        $enumsList = LocalConfigUtils::getCpConfigFileData('enums');

        $enums = [];
        $enumsColors = [];
        $enumsOptions = [];
        foreach ($enumsList as $enum) {
            if (!isset($enums[$enum['config']['entity']])) {
                $enums[$enum['config']['entity']] = [];
                $enumsColors[$enum['config']['entity']] = [];
                $enumsOptions[$enum['config']['entity']] = [];
            }
            if (!isset($enums[$enum['config']['entity']][$enum['config']['key']])) {
                $enums[$enum['config']['entity']][$enum['config']['key']] = [];
                $enumsColors[$enum['config']['entity']][$enum['config']['key']] = [];
                $enumsOptions[$enum['config']['entity']][$enum['config']['key']] = [];
            }
            $enumsOptions[$enum['config']['entity']][$enum['config']['key']][] = [
                'value' => $enum['config']['value'],
                'label' => $enum['config']['title'],
            ];
            $enums[$enum['config']['entity']][$enum['config']['key']][$enum['config']['value']] = $enum['config']['title'];
            $enumsColors[$enum['config']['entity']][$enum['config']['key']][$enum['config']['value']] = $enum['config']['badgeVariant'];
        }

        foreach ($enums as $slug => $propertyKeys) {
            $compName = Utils::fixComponentName(ucfirst($slug) . 'Enums');

            $tpEnumStr = json_encode($propertyKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $tpEnumColorsStr = json_encode($enumsColors[$slug], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $tpEnumOptionsStr = json_encode($enumsOptions[$slug], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_ENUMS_COLORS',
                    'TP_ENUMS_OPTIONS',
                    'TP_ENUMS',
                    'TP_COMP_NAME',
                ],
                [
                    $tpEnumColorsStr,
                    $tpEnumOptionsStr,
                    $tpEnumStr,
                    $compName,
                ],
                $statusItemsTemplate
            );
            Utils::writeOnChanges($fileName, $generatedContent);

        }

        return Command::SUCCESS;
    }
}