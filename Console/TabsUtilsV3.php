<?php

namespace Newageerp\SfControlpanel\Console;

class TabsUtilsV3
{
    protected array $tabs = [];

    protected EntitiesUtilsV3 $entitiesUtilsV3;

    public function __construct(EntitiesUtilsV3 $entitiesUtilsV3)
    {
        $this->entitiesUtilsV3 = $entitiesUtilsV3;
        $this->tabs = LocalConfigUtils::getCpConfigFileData('tabs');
    }

    public function getTabBySchemaAndType(string $schema, string $type): ?array
    {
        $tabsF = array_filter(
            $this->getTabs(),
            function ($item) use ($schema, $type) {
                return $item['config']['schema'] === $schema && $item['config']['type'] === $type;
            }
        );
        if (count($tabsF) > 0) {
            return reset($tabsF)['config'];
        }
        return null;
    }

    public function getTabQsFields(string $schema, string $type)
    {
        $tab = $this->getTabBySchemaAndType($schema, $type);
        if (!$tab) {
            return [];
        }
        if (isset($tab['quickSearchFilterKeys']) && $tab['quickSearchFilterKeys']) {
            return json_decode($tab['quickSearchFilterKeys'], true);
        }
        return $this->entitiesUtilsV3->getDefaultQsForSchema($schema);
    }

    public function getTabSort(string $schema, string $type)
    {
        $tab = $this->getTabBySchemaAndType($schema, $type);
        if (!$tab) {
            return [];
        }
        if (isset($tab['sort']) && $tab['sort']) {
            return json_decode($tab['sort'], true);
        }
        return $this->entitiesUtilsV3->getDefaultSortForSchema($schema);
    }

    public function getTabFilter(string $schema, string $type)
    {
        $tab = $this->getTabBySchemaAndType($schema, $type);
        if (!$tab) {
            return null;
        }
        if (isset($tab['predefinedFilter']) && $tab['predefinedFilter']) {
            return json_decode($tab['predefinedFilter'], true);
        }
        return null;
    }

    public function getTabsSwitchOptions(string $schema, string $type): array
    {
        $tab = $this->getTabBySchemaAndType($schema, $type);
        if (!$tab) {
            return [];
        }
        $otherTabs = [];
        if (isset($tabItem['config']['tabGroup']) && $tabItem['config']['tabGroup']) {
            $otherTabs =
                array_values(
                    array_map(
                        function ($t) {
                            return [
                                'value' => $t['config']['type'],
                                'label' => isset($t['config']['tabGroupTitle']) && $t['config']['tabGroupTitle'] ? $t['config']['tabGroupTitle'] : $t['config']['title']
                            ];
                        },
                        array_filter(
                            $this->getTabs(),
                            function ($t) use ($tabItem) {
                                return $t['config']['schema'] === $tabItem['config']['schema'] && $t['config']['tabGroup'] === $tabItem['config']['tabGroup'];
                            }
                        )
                    )
                );
        }
        return $otherTabs;
    }

    /**
     * Get the value of tabs
     *
     * @return array
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }
}
