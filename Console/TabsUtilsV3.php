<?php

namespace Newageerp\SfControlpanel\Console;

class TabsUtilsV3
{
    protected array $tabs = [];

    public function __construct()
    {
        $tabsFile = LocalConfigUtilsV3::getConfigCpPath() . '/tabs.json';
        $this->tabs = [];
        if (file_exists($tabsFile)) {
            $this->tabs = json_decode(
                file_get_contents($tabsFile),
                true
            );
        }
    }

    public function getTabBySchemaAndType(string $schema, string $type): ?array
    {
        $tabsF = array_filter(
            $this->tabs,
            function ($item) use ($schema, $type) {
                return $item['config']['schema'] === $schema && $item['config']['type'] === $type;
            }
        );
        if (count($tabsF) > 0) {
            return reset($tabsF)['config'];
        }
        return null;
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
