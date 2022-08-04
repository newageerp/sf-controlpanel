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
        $statusCompJson = [];

        foreach ($statusData as $status) {
            $entity = $status['config']['entity'];
            $type = $status['config']['type'];
            $typeUc = Utils::fixComponentName($status['config']['type']);

            if (!isset($statuses[$entity])) {
                $statuses[$entity] = [];
            }
            $statuses[$entity][] = $status;

            if (!isset($statusJson[$entity])) {
                $statusJson[$entity] = [];
                $statusCompJson[$entity] = [];
            }
            if (!isset($statusJson[$entity][$type])) {
                $statusJson[$entity][$type] = [];
            }
            if (!isset($statusJson[$entity][$typeUc])) {
                $statusCompJson[$entity][$typeUc] = [
                    'type' => $type,
                ];
            }
        }

        foreach ($statuses as $slug => $entityStatuses) {
            $statusData = [];

            $compName = Utils::fixComponentName(ucfirst($slug) . 'Statuses');

            foreach ($entityStatuses as $status) {
                $typeUc = Utils::fixComponentName($status['config']['type']);

                $statusName = Utils::fixComponentName(
                    ucfirst($slug) .ucfirst($status['config']['type']) . 'Badge' . $status['config']['status']
                );
                $el = [
                    'statusName' => $statusName,
                    'color' => $status['config']['color'],
                    'brightness' => mb_substr($status['config']['brightness'], 1),
                    'text' => $status['config']['text'],
                    'status' => (int)$status['config']['status'],
                    'type' => $status['config']['type'],
                    'bgColor' => isset($status['config']['badgeVariant']) ? $status['config']['badgeVariant'] : ''
                ];
                $statusData[] = $el;
                $statusJson[$entity][$type][] = $el;
            }

            $fileName = $generatedPath . '/' . $compName . '.tsx';

            $generatedContent = $statusItemsTemplate->render(
                [
                    'TP_COMP_NAME' => $compName,
                    'statusData' => $statusData,
                    'statusJson' => json_encode($statusJson[$slug], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    'statusCompJson' => $statusCompJson[$slug],
                    'schema' => $slug,
                    'schemaUc' => Utils::fixComponentName($slug)
                ]
            );
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}
