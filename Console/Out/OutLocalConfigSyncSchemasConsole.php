<?php

namespace Newageerp\SfControlpanel\Console\Out;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutLocalConfigSyncSchemasConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:OutLocalConfigSyncSchemas';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = LocalConfigUtils::getSqliteDb();

        $sql = "SELECT 
                    entities.id,
                    entities.slug
                FROM entities ";
        $result = $db->query($sql);

        $schemasDb = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $schemasDb[] = $data['slug'];
        }

        $docJsonFile = LocalConfigUtils::getDocJsonPath();
        $docJsonData = json_decode(file_get_contents($docJsonFile), true);

        $schemas = $docJsonData['components']['schemas'];

        foreach ($schemas as $schemasClass => $schemaData) {
            $normalizeSchemaClass = LocalConfigUtils::transformCamelCaseToKey($schemasClass);

            if (!in_array($normalizeSchemaClass, $schemasDb)) {
                $titleProp = "";
                if (isset($schemaData['title'])) {
                    $titleProp = $schemaData['title'];
                }
                $titleA = explode("|", $titleProp);
                $titleSingle = $titleA[0];
                $titlePlural = $titleA[0];
                if (count($titleA) > 1) {
                    $titlePlural = $titleA[1];
                }

                $sql = "INSERT INTO entities 
                (slug, className, titleSingle, titlePlural) 
                VALUES (:slug, :className, :titleSingle, :titlePlural)";
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':slug', $normalizeSchemaClass);
                $stmt->bindValue(':className', $schemasClass);
                $stmt->bindValue(':titleSingle', $titleSingle);
                $stmt->bindValue(':titlePlural', $titlePlural);
                $stmt->execute();

                $output->writeln("SCHEMA ADDED ". $schemasClass . ' - ' . $normalizeSchemaClass);
            }
        }

        return Command::SUCCESS;
    }
}
