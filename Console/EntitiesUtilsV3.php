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

    public function getEntityBySlug(string $slug)
    {
        foreach ($this->entities as $entity) {
            if ($entity['config']['slug'] === $slug) {
                return $entity;
            }
        }
        return null;
    }

    public function getTitleBySlug(string $slug)
    {
        $entity = $this->getEntityBySlug($slug);
        if ($entity) {
            return $entity['titleSingle'];
        }

        return '';
    }

    public function getTitlePluralBySlug(string $slug)
    {
        $entity = $this->getEntityBySlug($slug);
        if ($entity) {
            return $entity['titlePlural'];
        }

        return '';
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
