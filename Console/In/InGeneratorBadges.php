<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InGeneratorBadges extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorBadges';

    protected PropertiesUtils $propertiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $statusItemsTemplate = file_get_contents(
            __DIR__ . '/templates/badges/Badge.txt'
        );

        $badgeFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/badge.json';
        $badgeItems = [];
        if (file_exists($badgeFile)) {
            $badgeItems = json_decode(
                file_get_contents($badgeFile),
                true
            );
        }

        foreach ($badgeItems as $badgeItem) {
            $imports = [];

            $generatedPath = Utils::generatedPath('badges/' . $badgeItem['config']['schema']);

            $compName = Utils::fixComponentName(
                ucfirst($badgeItem['config']['schema']) .
                ucfirst($badgeItem['config']['slug']) . 'Badge'
            );

            $fileName = $generatedPath . '/' . $compName . '.tsx';

            $hookName = EntitiesUtils::elementHook($badgeItem['config']['schema']);

            $badgeContent = '';
            $badgeVariant = '';
            $property = null;

            if (isset($badgeItem['config']['bgColor'])) {
                $badgeVariant = '"' . $badgeItem['config']['bgColor'] . '"';
            }

            if (isset($badgeItem['config']['path']) && $badgeItem['config']['path']) {
                $badgeContent = 'getFieldNaeViewByPath("' . $badgeItem['config']['path'] . '", element.id)';
                $property = $this->propertiesUtils->getPropertyForPath($badgeItem['config']['path']);
                if (isset($property['enum']) && $property['enum']) {
                    $pathA = explode(".", $badgeItem['config']['path']);
                    $lastPath = $pathA[count($pathA) - 1];

                    $enumCompName = Utils::fixComponentName(ucfirst($property['schema']) . 'Enums');
                    $funcName = 'get' . $enumCompName;
                    $funcNameColors = 'get' . $enumCompName . 'Colors';

                    $badgeContent = $funcName . '("' . $lastPath . '", element["' . $lastPath . '"])';
                    $badgeVariant = $funcNameColors . '("' . $lastPath . '", element["' . $lastPath . '"])';

                    $imports[] = 'import { ' . $funcName . ', ' . $funcNameColors . ' } from "../../enums/view/' . $enumCompName . '";';
                }
            }
            if (isset($badgeItem['config']['text'])) {
                $badgeContent = '"' . $badgeItem['config']['text'] . '"';
            }

            $importsStr = implode("\n", $imports);

            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_HOOK_NAME',
                    'TP_SLUG',
                    'TP_VARIANT',
                    'TP_BADGE_CONTENT',
                    'TP_IMPORT',
                ],
                [
                    $compName,
                    $hookName,
                    $badgeItem['config']['slug'],
                    $badgeVariant,
                    $badgeContent,
                    $importsStr,
                ],
                $statusItemsTemplate
            );

            Utils::writeOnChanges($fileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}