<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InGeneratorBadges extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorBadges';

    public function __construct(MenuService $menuService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $statusItemsTemplate = file_get_contents(
            __DIR__ . '/templates/badges/Badge.txt'
        );

        $badgeFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/badge.json';
        $badgeItems = json_decode(
            file_get_contents($badgeFile),
            true
        );

        foreach ($badgeItems as $badgeItem) {
            $generatedPath = Utils::generatedPath('badges/' . $badgeItem['config']['schema']);

            $compName = Utils::fixComponentName(
                ucfirst($badgeItem['config']['schema']) .
                ucfirst($badgeItem['config']['slug']) . 'Badge'
            );

            $fileName = $generatedPath . '/' . $compName . '.tsx';

            $hookName = EntitiesUtils::elementHook($badgeItem['config']['schema']);

            $badgeContent = '';
            $path = $badgeItem['config']['path'] ?? '';
            if (isset($badgeItem['config']['path']) && $badgeItem['config']['path']) {
                $badgeContent = 'getFieldNaeViewByPath("' . $badgeItem['config']['path'] . '", element.id)';
            }
            if (isset($badgeItem['config']['text'])) {
                $badgeContent = '"' . $badgeItem['config']['text'] . '"';
            }

            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_HOOK_NAME',
                    'TP_SLUG',
                    'TP_VARIANT',
                    'TP_BADGE_CONTENT',
                    'TP_PATH'
                ],
                [
                    $compName,
                    $hookName,
                    $badgeItem['config']['slug'],
                    $badgeItem['config']['bgColor'] ?? '',
                    $badgeContent,
                    $path,
                ],
                $statusItemsTemplate
            );

            Utils::writeOnChanges($fileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}