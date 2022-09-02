<?php

namespace Newageerp\SfControlpanel\Console;

class EntitiesUtilsV3
{
    protected array $entities = [];

    public function __construct()
    {
        $entitiesFile = LocalConfigUtilsV3::getConfigCpPath() . '/entities.json';
        $this->entities = [];
        if (file_exists($entitiesFile)) {
            $this->entities = json_decode(
                file_get_contents($entitiesFile),
                true
            );
        }
    }

    /**
     * Get the value of entities
     *
     * @return array
     */
    public function getEntities(): array
    {
        return $this->entities;
    }
}
