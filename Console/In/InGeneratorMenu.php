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
        $menuFolderTemplate = file_get_contents(
            __DIR__ . '/templates/MenuFolder.txt'
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

        // MENU TITLE
        $menuTitleFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/menu-title.json';
        $menuTitleItems = json_decode(
            file_get_contents($menuTitleFile),
            true
        );

        $generatedPath = LocalConfigUtils::getFrontendGeneratedPath() . '/menu/titles';
        if (!$fs->exists($generatedPath)) {
            $fs->mkdir($generatedPath);
        }

        foreach ($menuTitleItems as $menuItem) {
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
                    $menuItem['config']['title']
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

        // MENU FOLDER
        $menuFolderFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/menu-folder.json';
        $menuFolderItems = json_decode(
            file_get_contents($menuFolderFile),
            true
        );

        $generatedPath = LocalConfigUtils::getFrontendGeneratedPath() . '/menu/folders';
        if (!$fs->exists($generatedPath)) {
            $fs->mkdir($generatedPath);
        }

        foreach ($menuFolderItems as $menuItem) {
            $compName = $this->menuService->componentNameForMenuFolder($menuItem);

            $fileName = $generatedPath . '/' . $compName . '.tsx';

            $tpImports = '';
            $tpChilds = '';

            foreach ($menuItem['config']['items'] as $itemId) {
                if (mb_strpos($itemId, 'separator') === 0) {
                    $tpChilds .= "<MenuSpacer />" . PHP_EOL;
                } else {
                    foreach ($menuItems as $m) {
                        if ($m['id'] === $itemId) {
                            $menuCompName = $this->menuService->componentNameForMenu($m);

                            $tpChilds .= "
                            <" . $menuCompName . " forceSkipIcon={true}/>" . PHP_EOL;

                            $tpImports .= "import " .$menuCompName." from \"../items/" . $menuCompName . "\" " . PHP_EOL;
                        }
                    }
                }
            }

            $localContents = '';
            if ($fs->exists($fileName)) {
                $localContents = file_get_contents($fileName);
            }

            $generatedContent = str_replace(
                [
                    'TP_COMP_NAME',
                    'TP_TITLE',
                    'TP_ICON',
                    'TP_IMPORTS',
                    'TP_CHILDS'
                ],
                [
                    $compName,
                    $menuItem['config']['title'],
                    $menuItem['config']['icon'],
                    $tpImports,
                    $tpChilds
                ],
                $menuFolderTemplate
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