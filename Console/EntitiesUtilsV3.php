<?php

namespace Newageerp\SfControlpanel\Console;

class EntitiesUtilsV3
{
    protected array $entities = [];

    protected array $defaults = [];

    public function __construct()
    {
        $this->entities = LocalConfigUtils::getCpConfigFileData('entities');
        $this->defaults = LocalConfigUtils::getCpConfigFileData('defaults');
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
            return $entity['config']['titleSingle'];
        }

        return '';
    }

    public function getTitlePluralBySlug(string $slug)
    {
        $entity = $this->getEntityBySlug($slug);
        if ($entity) {
            return $entity['config']['titlePlural'];
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

    public function getDefaultSortForSchema(string $schema)
    {
        $sort = [
            ['key' => 'i.id', 'value' => 'DESC']
        ];

        foreach ($this->defaults as $df) {
            if (
                $df['config']['schema'] === $schema &&
                isset($df['config']['defaultSort']) &&
                $df['config']['defaultSort']
            ) {
                $sort = json_decode($df['config']['defaultSort'], true);
            }
        }
        return $sort;
    }

    public function getDefaultQsForSchema(string $schema)
    {
        $qs = [];

        foreach ($this->defaults as $df) {
            if (
                $df['config']['schema'] === $schema &&
                isset($df['config']['defaultQuickSearch']) &&
                $df['config']['defaultQuickSearch']
            ) {
                $qs = json_decode($df['config']['defaultQuickSearch'], true);
            }
        }
        return $qs;
    }

    public function checkIsCreatable(string $schema, string $permissionGroup)
    {
        $entity = $this->getEntityBySlug($schema);
        if ($entity) {
            $scopes = isset($entity['config']['scopes']) && $entity['config']['scopes'] ? json_decode($entity['config']['scopes'], true) : [];

            if (in_array('disable-create', $scopes)) {
                return false;
            }
            if (in_array('disable-' . $permissionGroup . '-create', $scopes)) {
                return false;
            }

            $isAllowScope = false;
            foreach ($scopes as $scope) {
                if (mb_strpos($scope, 'allow-create')) {
                    $isAllowScope = true;
                }
            }
            if ($isAllowScope) {
                if (in_array('allow-create-' . $permissionGroup, $scopes)) {
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    }
}
