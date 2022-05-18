<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InLocalConfigSyncFieldsConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:InLocalConfigSyncFields';

    protected EntityManagerInterface $em;

    protected PropertiesUtils $propertiesUtils;

    public function __construct(
        EntityManagerInterface $em,
        PropertiesUtils        $propertiesUtils
    )
    {
        parent::__construct();
        $this->em = $em;
        $this->propertiesUtils = $propertiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = LocalConfigUtils::getSqliteDb();

        $configJsonPath = LocalConfigUtils::getStrapiCachePath() . '/NaeSProperties.json';
        $configJsonPathDbKeys = LocalConfigUtils::getStrapiCachePath() . '/NaeSDbKeys.json';

        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaeSProperties.tsx';
        $configPathDbKeys = LocalConfigUtils::getFrontendConfigPath() . '/NaeSDbKeys.tsx';
        $configPathKeys = LocalConfigUtils::getFrontendConfigPath() . '/NaeSPropertiesKeys.tsx';

        $phpPropertiesFile = LocalConfigUtils::getPhpCachePath() . '/properties.json';

        $fileContent = 'import { INaeProperty, INaePropertyEnum } from "@newageerp/nae-react-ui/dist/interfaces";
';
        $fileDbKeysContent = '';


        $conn = $this->em->getConnection();
        $sql = 'select TABLE_NAME, COLUMN_NAME, DATA_TYPE from information_schema.columns
        where table_schema = DATABASE()
        order by table_name,ordinal_position';
        $stmt = $conn->query($sql);
        $dbFields = $stmt->fetchAll();

        $dbFieldsAll = [];
        foreach ($dbFields as $dbField) {
            $dbFieldsAll[] = [
                $dbField['TABLE_NAME'],
                $dbField['COLUMN_NAME'],
                $dbField['DATA_TYPE'],
            ];
        }


        $sql = 'select 
        properties.id,
        properties.key, 
        properties.description, 
        properties.type, 
        properties.typeFormat, 
        properties.title, 
        properties.additionalProperties,  
        properties.`as`,
        properties.isDb,
        properties.dbType,
        entities.slug as entity_slug, 
        entities.className as entity_className,
        properties.entity
        
        from properties
        left join entities on entities.id = properties.entity ';

        $result = $db->query($sql);

        $properties = [];
        $propertiesKeys = [];
        $dbDataKeys = [];
        $phpProperties = [];

        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!isset($dbDataKeys[$data['entity_slug']])) {
                $dbDataKeys[$data['entity_slug']] = [];
            }
            $dbDataKeys[$data['entity_slug']][$data['key']] = $data['key'];

            $description = str_replace(
                ['|||', '<b>', '</b>', '<hr/>'],
                ["\n\n", '**', '**', '___'],
                $data['description']
            );

            if (!isset($propertiesKeys[$data['entity_slug']])) {
                $propertiesKeys[$data['entity_slug']] = [];
            }

            $propertiesKeys[$data['entity_slug']][$data['key']] = $data['key'];

            $prop = [
                'schema' => $data['entity_slug'],
                'key' => $data['key'],
                'type' => $data['type'],
                'format' => $data['typeFormat'],
                'title' => $data['title'],
                'additionalProperties' => json_decode($data['additionalProperties'], true),
                'description' => $description,
                'className' => $data['entity_className'],
                'isDb' => $data['isDb'] === 1,
                'dbType' => $data['dbType']
            ];
            if ($data['as']) {
                $prop['as'] = $data['as'];
            }

            $sql = 'select 
        enums.sort,
        enums.title,
        enums.value
        
        from enums
        where enums.entity = ' . $data['entity'] . ' 
        and enums.property = ' . $data['id'] . '
         ';
            $resultEnums = $db->query($sql);

            $enumsData = [];
            while ($enum = $resultEnums->fetchArray(SQLITE3_ASSOC)) {
                $enumsData[] = [
                    'sort' => $enum['sort'],
                    'title' => $enum['title'],
                    'value' => $enum['value'],
                ];
            }


            if ($enumsData) {
                usort($enumsData, function ($pdfA, $pdfB) {
                    if ($pdfA['sort'] < $pdfB['sort']) {
                        return -1;
                    }
                    if ($pdfA['sort'] > $pdfB['sort']) {
                        return 1;
                    }
                    if ($pdfA['title'] < $pdfB['title']) {
                        return -1;
                    }
                    if ($pdfA['title'] > $pdfB['title']) {
                        return 1;
                    }
                    return 0;
                });

                $prop['enum'] = array_map(
                    function ($en) use ($data) {
                        $isInt = $data['type'] === 'integer' || $data['type'] === 'int' || $data['type'] === 'number';
                        return [
                            'label' => $en['title'],
                            'value' => $isInt ? ((int)$en['value']) : $en['value']
                        ];
                    },
                    $enumsData
                );
            }

            $properties[] = $prop;


            $propPhp = [
                'key' => $prop['key'],
                'title' => $prop['title'],
                'type' => $prop['type'],
                'format' => $prop['format'],
                'description' => $prop['description'],
                'schema' => $prop['schema'],
                'isDb' => $prop['isDb'],
                'dbType' => $prop['dbType'],
                'as' => isset($prop['as']) ? $prop['as'] : '',
                'additionalProperties' => isset($prop['additionalProperties']) ? $prop['additionalProperties'] : [],
                'enum' => isset($prop['enum']) ? $prop['enum'] : [],
            ];
            $propPhp['naeType'] = $this->propertiesUtils->getPropertyNaeType($propPhp, []);
            $phpProperties[] = $propPhp;
        }

        usort($properties, function ($pdfA, $pdfB) {
            if ($pdfA['schema'] < $pdfB['schema']) {
                return -1;
            }
            if ($pdfA['schema'] > $pdfB['schema']) {
                return 1;
            }
            if ($pdfA['key'] < $pdfB['key']) {
                return -1;
            }
            if ($pdfA['key'] > $pdfB['key']) {
                return 1;
            }
            if ($pdfA['title'] < $pdfB['title']) {
                return -1;
            }
            if ($pdfA['title'] > $pdfB['title']) {
                return 1;
            }
            return 0;
        });

        $fileContent .= 'export const NaeSProperties: INaeProperty[] = ' . json_encode($properties, JSON_PRETTY_PRINT);

        $fileKeysContent = 'export const NaeSPropertiesKeys = ' . json_encode($propertiesKeys, JSON_PRETTY_PRINT);

        $fileDbKeysContent .= '
export const NaeSDbKeys = ' . json_encode($dbFieldsAll, JSON_PRETTY_PRINT);

        file_put_contents(
            $configPath,
            $fileContent
        );
        file_put_contents(
            $configPathKeys,
            $fileKeysContent
        );
        file_put_contents(
            $configJsonPath,
            json_encode($properties, JSON_PRETTY_PRINT)
        );
        // file_put_contents(
        //     $configPathDbKeys,
        //     $fileDbKeysContent
        // );
        file_put_contents(
            $configJsonPathDbKeys,
            json_encode($dbFieldsAll, JSON_PRETTY_PRINT)
        );

        file_put_contents(
            $phpPropertiesFile,
            json_encode($phpProperties, JSON_UNESCAPED_UNICODE)
        );

        return Command::SUCCESS;
    }
}
