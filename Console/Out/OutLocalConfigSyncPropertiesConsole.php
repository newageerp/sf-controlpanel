<?php

namespace Newageerp\SfControlpanel\Console\Out;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutLocalConfigSyncPropertiesConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:OutLocalConfigSyncProperties';

    protected EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    ) {
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

        

        $db = LocalConfigUtils::getSqliteDb();

        $sql = "SELECT 
                entities.id,
                entities.slug
            FROM entities ";
        $result = $db->query($sql);

        $dbSchemasSlug = [];
        $dbSchemas = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $dbSchemas[] = $data;
            $dbSchemasSlug[$data['slug']] = $data['id'];
        }

        $dbPropertiesSlug = [];

        $sql = "SELECT 
                properties.id,
                properties.key,
                properties.entity
            FROM properties ";
        $result = $db->query($sql);

        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $dbPropertiesSlug[$data['key'] . "-" . $data['entity']] = $data['id'];
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

                    $_schemaId = isset($dbSchemasSlug[$normalizeSchemaClass]) ? $dbSchemasSlug[$normalizeSchemaClass] : 0;

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

                        $sql = "UPDATE properties 
                                    set 
                                    type = :type,
                                    typeFormat = :typeFormat,
                                    isDb = :isDb,
                                    dbType = :dbType,
                                    `as` = :as,
                                    additionalProperties = :additionalProperties
                                    where id = :id";

                        $stmt = $db->prepare($sql);

                        $stmt->bindValue(':id', $dbPropertiesSlug[$propKey . '-' . $_schemaId]);

                        $stmt->bindValue(':type', $type);
                        $stmt->bindValue(':typeFormat', $format);
                        $stmt->bindValue(':isDb', $isDb);
                        $stmt->bindValue(':dbType', $dbType);
                        $stmt->bindValue(':as', $as);
                        $stmt->bindValue(':additionalProperties', json_encode($propAdditionalProperties));

                        $stmt->execute();
                    } else {

                        $sql = "INSERT INTO properties 
                        (`key`, title, `type`, typeFormat, `description`, entity, isDb, dbType, `as`, additionalProperties) 
                        VALUES (:key, :title, :type, :typeFormat, :description, :entity, :isDb, :dbType, :as, :additionalProperties)";

                        $stmt = $db->prepare($sql);

                        $stmt->bindValue(':key', $propKey);
                        $stmt->bindValue(':title', $propTitle);
                        $stmt->bindValue(':type', $type);
                        $stmt->bindValue(':typeFormat', $format);
                        $stmt->bindValue(':description', $propDescription);
                        $stmt->bindValue(':entity', $_schemaId);
                        $stmt->bindValue(':isDb', $isDb);
                        $stmt->bindValue(':dbType', $dbType);
                        $stmt->bindValue(':as', $as);
                        $stmt->bindValue(':additionalProperties', json_encode($propAdditionalProperties));

                        $stmt->execute();

                        $output->writeln("PROPERTY ADDED " . $schemasClass . ' - ' . $propKey);
                    }
                }
            }
        }

        

        return Command::SUCCESS;
    }
}
