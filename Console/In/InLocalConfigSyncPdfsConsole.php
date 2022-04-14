<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InLocalConfigSyncPdfsConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:InLocalConfigSyncPdfs';

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

        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaeSPdfs.tsx';

        $fileContent = 'import { INaePdf } from "@newageerp/nae-react-ui/dist/interfaces";
';

        $sql = 'select pdfs.template, pdfs.title, pdfs.skipList, pdfs.sort, pdfs.skipWithoutSign, entities.slug from pdfs
        left join entities on entities.id = pdfs.entity ';

        $result = $db->query($sql);

        $pdfs = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $pdfs[] = [
                'sort' => (int)$data['sort'],
                'schema' => $data['slug'],
                'template' => $data['template'],
                'title' => $data['title'],
                'skipList' => $data['skipList'] === 1,
                'skipWithoutSign' => isset($data['skipWithoutSign']) && $data['skipWithoutSign'] === 1,
            ];
        }

        usort($pdfs, function ($pdfA, $pdfB) {
            if ($pdfA['sort'] < $pdfB['sort']) {
                return -1;
            }
            if ($pdfA['sort'] > $pdfB['sort']) {
                return 1;
            }
            if ($pdfA['schema'] < $pdfB['schema']) {
                return -1;
            }
            if ($pdfA['schema'] > $pdfB['schema']) {
                return 1;
            }
            return 0;
        });

        $fileContent .= 'export const NaeSPdfs: INaePdf[] = ' . json_encode($pdfs, JSON_PRETTY_PRINT);

        file_put_contents(
            $configPath,
            $fileContent
        );

        return Command::SUCCESS;
    }
}
