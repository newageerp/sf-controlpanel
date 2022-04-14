<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InLocalConfigSyncEntitiesConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:InLocalConfigSyncEntities';

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

        $configJsonPath = LocalConfigUtils::getStrapiCachePath() . '/NaeSSchema.json';
        $phpPropertiesFile = LocalConfigUtils::getPhpCachePath() . '/NaeSSchema.json';
        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaeSSchema.tsx';

        $fileContent = 'import { INaeSchema } from "@newageerp/nae-react-ui/dist/interfaces";
';

        $sql = 'select 
            entities.className, 
            entities.slug, 
            entities.titleSingle, 
            entities.titlePlural, 
            entities.required, 
            entities.scopes 
            from entities ';

        $result = $db->query($sql);

        $entities = [];
        $entitiesMap = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $status = [
                'className' => $data['className'],
                'schema' => $data['slug'],
                'title' => $data['titleSingle'],
                'titlePlural' => $data['titlePlural'],
            ];

            if ($data['required']) {
                $status['required'] = json_decode(
                    $data['required'],
                    true
                );
            }
            if ($data['scopes']) {
                $status['scopes'] = json_decode(
                    $data['scopes'],
                    true
                );
            }

            $entities[] = $status;

            $entitiesMap[$data['className']] = [
                'className' => $data['className'],
                'schema' => $data['slug']
            ];
        }

        usort($entities, function ($pdfA, $pdfB) {
            if ($pdfA['schema'] < $pdfB['schema']) {
                return -1;
            }
            if ($pdfA['schema'] > $pdfB['schema']) {
                return 1;
            }
            return 0;
        });

        $fileContent .= 'export const NaeSSchema: INaeSchema[] = ' . json_encode($entities, JSON_PRETTY_PRINT);

        $fileContent .= '
        export const NaeSSchemaMap = ' . json_encode($entitiesMap, JSON_PRETTY_PRINT);

        file_put_contents(
            $configPath,
            $fileContent
        );
        file_put_contents(
            $configJsonPath,
            json_encode($entities, JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $phpPropertiesFile,
            json_encode($entities, JSON_PRETTY_PRINT)
        );

        return Command::SUCCESS;
    }
}
