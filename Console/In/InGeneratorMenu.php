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
            if (isset($menuItem['customLink']) && $menuItem['customLink']) {
                $menuLink = $menuItem['customLink'];
                $compName = implode(
                    "",
                    array_map(
                        function ($p) {
                            return ucfirst($p);
                        },
                        explode(
                            "/",
                            $menuItem['customLink']
                        )
                    )
                );
            } else if ($menuItem['schema'] and $menuItem['type']) {
                $compName = ucfirst($menuItem['schema']) . ucfirst($menuItem['type']);
                $menuLink = '/u/' . $menuItem['schema'] . '/' . $menuItem['type'] . '/list';
            }

            $menuTitle = '';
            if (isset($menuItem['customTitle']) && $menuItem['customTitle']) {
                $menuTitle = $menuItem['customTitle'];
            } else {
                foreach ($entities as $entity) {
                    if ($entity['schema'] === $menuItem['schema']) {
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