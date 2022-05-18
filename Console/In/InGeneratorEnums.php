<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorEnums extends Command
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
        $statusItemsTemplate = file_get_contents(
            __DIR__ . '/templates/enums/EnumItems.txt'
        );

        $generatedPath = Utils::generatedPath('enums/view');

        $db = new \SQLite3($_ENV['NAE_SFS_CP_DB_PATH']);

        $sql = "SELECT 
                    enums.id, enums.title, enums.value, enums.entity, enums.property, enums.sort,
       entities.slug as entity_slug,
                    entities.slug || ' (' || entities.titleSingle || ')' as entity_title,
       properties.key as property_key,
       
                    properties.key || ' (' || properties.title || ')' as property_title
                FROM enums 
                left join entities on enums.entity = entities.id
                left join properties on enums.property = properties.id
                WHERE 1 = 1 ";
        $result = $db->query($sql);

        $enums = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!isset($enums[$data['entity_slug']])) {
                $enums[$data['entity_slug']] = [];
            }
            if (!isset($enums[$data['entity_slug']][$data['property_key']])) {
                $enums[$data['entity_slug']][$data['property_key']] = [];
            }
            $enums[$data['entity_slug']][$data['property_key']][$data['value']] = $data['title'];
        }

        foreach ($enums as $slug => $propertyKeys) {
            $compName = Utils::fixComponentName(ucfirst($slug) . 'Enums');

            $tpEnumStr = json_encode($propertyKeys, JSON_PRETTY_PRINT);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_ENUMS',
                    'TP_COMP_NAME',
                ],
                [
                    $tpEnumStr,
                    $compName,
                ],
                $statusItemsTemplate
            );
            Utils::writeOnChanges($fileName, $generatedContent);

        }

        return Command::SUCCESS;
    }
}