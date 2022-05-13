<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorMenu extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorMenu';

    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        parent::__construct();
        $this->menuService = $menuService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();

        $menuTemplate = file_get_contents(
            __DIR__ . '/templates/MenuItem.txt'
        );
        $menuTitleTemplate = file_get_contents(
            __DIR__ . '/templates/MenuTitle.txt'
        );

        $menuFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/menu.json';
        $menuItems = json_decode(
            file_get_contents($menuFile),
            true
        );

        $generatedPath = LocalConfigUtils::getFrontendGeneratedPath() . '/menu/items';
        if (!$fs->exists($generatedPath)) {
            $fs->mkdir($generatedPath);
        }

        foreach ($menuItems as $menuItem) {
            $compName = $this->menuService->componentNameForMenu($menuItem);
            $menuLink = $this->menuService->menuLinkForMenu($menuItem);
            $menuTitle = $this->menuService->menuTitleForMenu($menuItem);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $localContents = '';
            if ($fs->exists($fileName)) {
                $localContents = file_get_contents($fileName);
            }

            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_PATH',
                    'TP_ICON',
                    'TP_TITLE'
                ],
                [
                    $compName,
                    $menuLink,
                    $menuItem['config']['icon'] ?? '',
                    $menuTitle
                ],
                $menuTemplate
            );

            if ($localContents !== $generatedContent) {
                file_put_contents(
                    $fileName,
                    $generatedContent
                );
            }
        }

        $menuTitleFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/menu-title.json';
        $menuTitleItems = json_decode(
            file_get_contents($menuTitleFile),
            true
        );

        $generatedPath = LocalConfigUtils::getFrontendGeneratedPath() . '/menu/titles';
        if (!$fs->exists($generatedPath)) {
            $fs->mkdir($generatedPath);
        }

        foreach ($menuItems as $menuItem) {
            $compName = $this->menuService->componentNameForMenuTitle($menuItem);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $localContents = '';
            if ($fs->exists($fileName)) {
                $localContents = file_get_contents($fileName);
            }

            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_TITLE'
                ],
                [
                    $compName,
                    $menuItem['config']['ttile']
                ],
                $menuTitleTemplate
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