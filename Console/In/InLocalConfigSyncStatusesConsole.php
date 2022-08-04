<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InLocalConfigSyncStatusesConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:InLocalConfigSyncStatuses';

    protected EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TMP OLD SYNC
        $db = LocalConfigUtils::getSqliteDb();
        if ($db) {
            $sql = "select statuses.id, statuses.type, statuses.status, statuses.text, statuses.color, statuses.brightness, entities.slug from statuses
        left join entities on entities.id = statuses.entity";
            $result = $db->query($sql);

            $variables = LocalConfigUtils::getCpConfigFileData('statuses');
            while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                $newId = 'synced-' . $data['id'];

                $isExist = false;
                foreach ($variables as $var) {
                    if ($var['id'] === $newId) {
                        $isExist = true;
                    }
                }
                if (!$isExist) {
                    $variables[] = [
                        'id' => $newId,
                        'tag' => '',
                        'title' => '',
                        'config' => [
                            'entity' => $data['slug'],
                            'text' => $data['text'],
                            'status' => $data['status'],
                            'type' => $data['type'],
                            'color' => $data['color'],
                            'brightness' => $data['brightness'],
                        ]
                    ];
                }
            }
            file_put_contents(LocalConfigUtils::getCpConfigFile('statuses'), json_encode($variables, JSON_UNESCAPED_UNICODE));
        }
        // TMP OLD SYNC OFF

//        $configJsonPath = LocalConfigUtils::getStrapiCachePath() . '/NaeSStatuses.json';
        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaeSStatuses.tsx';

        $fileContent = 'import { INaeStatus } from "@newageerp/nae-react-ui/dist/interfaces";
';

        $statusData = LocalConfigUtils::getCpConfigFileData('statuses');

        $statuses = [];
        foreach ($statusData as $status) {
            $statuses[] = [
                'type' => $status['config']['type'],
                'status' => (int)$status['config']['status'],
                'text' => $status['config']['text'],
                'bgColor' => $status['config']['color'],
                'brightness' => (int)str_replace('b', '', $status['config']['brightness']),
                'schema' => $status['config']['entity'],
            ];
        }

        usort($statuses, function ($pdfA, $pdfB) {
            if ($pdfA['schema'] < $pdfB['schema']) {
                return -1;
            }
            if ($pdfA['schema'] > $pdfB['schema']) {
                return 1;
            }
            if ($pdfA['status'] < $pdfB['status']) {
                return -1;
            }
            if ($pdfA['status'] > $pdfB['status']) {
                return 1;
            }
            return 0;
        });

        $fileContent .= 'export const NaeSStatuses: INaeStatus[] = ' . json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents(
            $configPath,
            $fileContent
        );
//        file_put_contents(
//            $configJsonPath,
//            json_encode($statuses, JSON_PRETTY_PRINT)
//        );

        return Command::SUCCESS;
    }
}
