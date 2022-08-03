<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorStatuses extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorStatuses';

    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        parent::__construct();
        $this->menuService = $menuService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp',
        ]);

        $statusItemsTemplate = $twig->load('statuses/StatusItems.html.twig');


        $generatedPath = Utils::generatedPath('statuses/badges');

        $statusData = LocalConfigUtils::getCpConfigFileData('statuses');

        $statuses = [];
        $statusJson = [];

        foreach ($statusData as $status) {
            $entity = $status['config']['entity'];
            $type = $status['config']['type'];

            if (!isset($statuses[$entity])) {
                $statuses[$entity] = [];
            }
            $statuses[$entity][] = $status;

            if (!isset($statusJson[$entity])) {
                $statusJson[$entity] = [];
            }
            if (!isset($statusJson[$entity][$type])) {
                $statusJson[$entity][$type] = [];
            }
            $statusJson[$entity][$type][] = [
                'status' => $status['config']['status'],
                'title' => $status['config']['text']
            ];
        }

        foreach ($statuses as $slug => $entityStatuses) {
            $statusData = [];

            $compName = Utils::fixComponentName(ucfirst($slug) . 'Statuses');

            foreach ($entityStatuses as $status) {
                $statusName = Utils::fixComponentName(
                    ucfirst($slug) .
                        ucfirst($status['config']['type']) .
                        'Badge' .
                        $status['config']['status']
                );
                $statusData[] = [
                    'statusName' => $statusName,
                    'color' => $status['config']['color'],
                    'brightness' => mb_substr($status['config']['brightness'], 1),
                    'text' => $status['config']['text'],
                    'status' => (int)$status['config']['status'],
                    'type' => $status['config']['type'],
                    'bgColor' => isset($status['config']['badgeVariant']) ? $status['config']['badgeVariant'] : ''
                ];
            }

            $fileName = $generatedPath . '/' . $compName . '.tsx';

            $generatedContent = $statusItemsTemplate->render(
                [
                    'TP_COMP_NAME' => $compName,
                    'statusData' => $statusData,
                    'statusJson' => json_encode($statusJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                ]
            );
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}
