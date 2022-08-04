<?php

namespace Newageerp\SfControlpanel\Console\Out;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutLocalConfigSyncPropertiesConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:OutLocalConfigSyncProperties';

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
        $conn = $this->em->getConnection();
        $sql = 'select TABLE_NAME, COLUMN_NAME, DATA_TYPE from information_schema.columns
        where table_schema = DATABASE()
        order by table_name,ordinal_position';
        $stmt = $conn->query($sql);
        $dbFields = $stmt->fetchAll();

        $dbFieldsByTableColumn = [];
        foreach ($dbFields as $dbField) {
            $dbFieldsByTableColumn[$dbField['TABLE_NAME'] . "-" . $dbField['COLUMN_NAME']] = $dbField;
        }

        $propertiesData = LocalConfigUtils::getCpConfigFileData('properties');

        $dbPropertiesSlug = [];

        foreach ($propertiesData as $property) {
            $dbPropertiesSlug[$property['config']['key'] . "-" . $property['config']['entity']] = $property['id'];
        }


        $docJsonFile = LocalConfigUtils::getDocJsonPath();
        $docJsonData = json_decode(file_get_contents($docJsonFile), true);

        $schemas = $docJsonData['components']['schemas'];

        foreach ($schemas as $schemasClass => $schemaData) {
            $normalizeSchemaClass = LocalConfigUtils::transformCamelCaseToKey($schemasClass);
            $dbName = str_replace('-', '_', $normalizeSchemaClass);

            if (isset($schemaData['properties'])) {
                foreach ($schemaData['properties'] as $propKey => $prop) {
                    $normalizePropKey = LocalConfigUtils::transformCamelCaseToKey($propKey);
                    $dbPropKey = str_replace('-', '_', $normalizePropKey);

                    $_schemaId = $normalizeSchemaClass;

                    $type = isset($prop['type']) ? $prop['type'] : '';
                    if (isset($prop['allOf'])) {
                        $type = array_map(
                            function ($t) {
                                return (LocalConfigUtils::transformCamelCaseToKey(
                                    str_replace('#/components/schemas/', '', $t['$ref'])
                                )
                                );
                            },
                            $prop['allOf']
                        );
                    }

                    $format = isset($prop['format']) ? $prop['format'] : '';
                    if ($type === 'array' && !$format) {
                        if (isset($prop['items']['type']['type'])) {
                            $format = LocalConfigUtils::transformCamelCaseToKey(
                                str_replace("App\\Entity\\", "", $prop['items']['type']['type'])
                            );
                        } else if (isset($prop['items']['type'])) {
                            $format = $prop['items']['type'];
                        }
                    }

                    if (is_array($type)) {
                        $format = $type[0];
                        $type = 'rel';
                    }

                    if ($type === 'rel') {
                        $dbPropKey = $dbPropKey . "_id";
                    }

                    $isDb = false;
                    $dbType = '';

                    if (isset($dbFieldsByTableColumn[$dbName . "-" . $dbPropKey])) {
                        $isDb = true;
                        $dbType = $dbFieldsByTableColumn[$dbName . "-" . $dbPropKey]['DATA_TYPE'];
                    }

                    $propAdditionalProperties = isset($prop['additionalProperties']) ? $prop['additionalProperties'] : [];

                    $as = '';
                    foreach ($propAdditionalProperties as $el) {
                        if (isset($el['as'])) {
                            $as = $el['as'];
                        }
                    }


                    $propTitle = isset($prop['title']) ? $prop['title'] : '';
                    $propDescription = isset($prop['description']) ? $prop['description'] : '';


                    if (isset($dbPropertiesSlug[$propKey . '-' . $_schemaId])) {
                        foreach ($propertiesData as &$propToChange) {
                            if ($propToChange['id'] === $dbPropertiesSlug[$propKey . '-' . $_schemaId]) {
                                $propToChange['config']['type'] = $type;
                                $propToChange['config']['typeFormat'] = $format;
                                $propToChange['config']['isDb'] = $isDb;
                                $propToChange['config']['dbType'] = $dbType;
                                $propToChange['config']['as'] = $as;
                                $propToChange['config']['additionalProperties'] = json_encode($propAdditionalProperties);
                            }
                        }
                    } else {
                        $propertiesData[] = [
                            'id' => Uuid::uuid4()->toString(),
                            'tag' => '',
                            'title' => '',
                            'config' => [
                                'additionalProperties' => json_encode($propAdditionalProperties),
                                'as' => $as,
                                'dbType' => $dbType,
                                'description' => $propDescription,
                                'entity' => $_schemaId,

                                'isDb' => $isDb,
                                'key' => $propKey,
                                'title' => $propTitle,
                                'type' => $type,
                                'typeFormat' => $format,

                                'available_sort' => 0,
                                'available_filter' => 0,
                                'available_group' => 0,
                                'available_total' => 0,
                            ]
                        ];

                        $output->writeln("PROPERTY ADDED " . $schemasClass . ' - ' . $propKey);
                    }
                }
            }
        }

        file_put_contents(LocalConfigUtils::getCpConfigFile('properties'), json_encode($propertiesData, JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
