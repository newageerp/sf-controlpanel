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
        $db = LocalConfigUtils::getSqliteDb();

        $configJsonPath = LocalConfigUtils::getStrapiCachePath() . '/NaeSStatuses.json';
        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaeSStatuses.tsx';

        $fileContent = 'import { INaeStatus } from "@newageerp/nae-react-ui/dist/interfaces";
';

        $sql = 'select statuses.type, statuses.status, statuses.text, statuses.color, statuses.brightness, entities.slug from statuses
        left join entities on entities.id = statuses.entity ';

        $result = $db->query($sql);

        $statuses = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $statuses[] = [
                'type' => $data['type'],
                'status' => $data['status'],
                'text' => $data['text'],
                'bgColor' => $data['color'],
                'brightness' => (int)str_replace('b', '', $data['brightness']),
                'schema' => $data['slug'],
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

        $fileContent .= 'export const NaeSStatuses: INaeStatus[] = ' . json_encode($statuses, JSON_PRETTY_PRINT);

        file_put_contents(
            $configPath,
            $fileContent
        );
        file_put_contents(
            $configJsonPath,
            json_encode($statuses, JSON_PRETTY_PRINT)
        );

        return Command::SUCCESS;
    }
}
