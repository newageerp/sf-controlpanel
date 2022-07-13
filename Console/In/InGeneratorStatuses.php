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

        $db = new \SQLite3($_ENV['NAE_SFS_CP_DB_PATH']);

        $sql = "SELECT 
                    statuses.id, statuses.text, statuses.entity, statuses.status, statuses.type, statuses.color, statuses.brightness,
                    entities.slug as entity_slug,
                    entities.slug || ' (' || entities.titleSingle || ')' as entity_title
                FROM statuses 
                left join entities on statuses.entity = entities.id
                WHERE 1 = 1 
                ";
        $result = $db->query($sql);

        $statuses = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!isset($statuses[$data['entity_slug']])) {
                $statuses[$data['entity_slug']] = [];
            }
            $statuses[$data['entity_slug']][] = $data;
        }



        foreach ($statuses as $slug => $entityStatuses) {
            $statusData = [];

            $compName = Utils::fixComponentName(ucfirst($slug) . 'Statuses');

            foreach ($entityStatuses as $entityStatus) {
                $statusName = Utils::fixComponentName(
                    ucfirst($slug) .
                    ucfirst($entityStatus['type']) .
                    'Badge' .
                    $entityStatus['status']
                );
                $statusData[] = [
                    'statusName' => $statusName,
                    'color' => $entityStatus['color'],
                    'brightness' => mb_substr($entityStatus['brightness'], 1),
                    'text' => $entityStatus['text'],
                    'status' => $entityStatus['status'],
                    'type' => $entityStatus['type'],
                ];

            }

            $fileName = $generatedPath . '/' . $compName . '.tsx';

            $generatedContent = $statusItemsTemplate->render(
                [
                    'TP_COMP_NAME' => $compName,
                    'statusData' => $statusData
                ]
            );
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}