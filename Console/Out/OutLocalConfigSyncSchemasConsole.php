<?php

namespace Newageerp\SfControlpanel\Console\Out;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Ramsey\Uuid\Uuid;
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
        $entityData = LocalConfigUtils::getCpConfigFileData('entities');

        $schemasDb = [];
        foreach ($entityData as $entity) {
            $schemasDb[] = $entity['config']['slug'];
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

                $entityData[] = [
                    'id' => Uuid::uuid4()->toString(),
                    'tag' => '',
                    'title' => '',
                    'config' => [
                        'className' => $schemasClass,
                        'slug' => $normalizeSchemaClass,
                        'titleSingle' => $titleSingle,
                        'titlePlural' => $titlePlural,
                        'required' => '[]',
                        'scopes' => '[]',
                    ]
                ];

                $output->writeln("SCHEMA ADDED ". $schemasClass . ' - ' . $normalizeSchemaClass);
            }
        }

        file_put_contents(LocalConfigUtils::getCpConfigFile('entities'), json_encode($entityData, JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
