<?php

namespace Newageerp\SfControlpanel\Console\In;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorMenu extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorMenu';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();

        $entities = json_decode(file_get_contents(LocalConfigUtils::getPhpCachePath() . '/NaeSSchema.json'), true);

        $menuTemplate = file_get_contents(
            __DIR__ . '/templates/MenuItem.txt'
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
            $compName = '';
            $menuLink = '';
            if (isset($menuItem['config']['customLink']) && $menuItem['config']['customLink']) {
                $menuLink = $menuItem['config']['customLink'];
                $compName = implode(
                    "",
                    array_map(
                        function ($p) {
                            return ucfirst($p);
                        },
                        explode(
                            "/",
                            $menuItem['config']['customLink']
                        )
                    )
                );
            } else if ($menuItem['config']['schema'] and $menuItem['config']['type']) {
                $compName = ucfirst($menuItem['config']['schema']) . ucfirst($menuItem['config']['type']);
                $menuLink = '/u/' . $menuItem['config']['schema'] . '/' . $menuItem['config']['type'] . '/list';
            }

            $menuTitle = '';
            if (isset($menuItem['config']['customTitle']) && $menuItem['config']['customTitle']) {
                $menuTitle = $menuItem['config']['customTitle'];
            } else {
                foreach ($entities as $entity) {
                    if ($entity['schema'] === $menuItem['config']['schema']) {
                        $menuTitle = $entity['titlePlural'];
                    }
                }
            }

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
                    $menuItem['icon'],
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

        return Command::SUCCESS;
    }
}