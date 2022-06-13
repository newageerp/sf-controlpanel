<?php

namespace Newageerp\SfControlpanel\Console;

class EntitiesUtils
{
    protected array $entities = [];

    protected array $defaultItems = [];

    public function __construct()
    {
        $this->entities = json_decode(
            file_get_contents(LocalConfigUtils::getPhpCachePath() . '/NaeSSchema.json'),
            true
        );
        $this->defaultItems = json_decode(
            file_get_contents($_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/defaults.json'),
            true
        );
    }

    public function getTitleBySlug(string $slug)
    {
        foreach ($this->entities as $entity) {
            if ($entity['schema'] === $slug) {
                return $entity['title'];
            }
        }
        return '';
    }

    public function getTitlePluralBySlug(string $slug)
    {
        foreach ($this->entities as $entity) {
            if ($entity['schema'] === $slug) {
                return $entity['titlePlural'];
            }
        }
        return '';
    }

    public function getClassNameBySlug(string $slug)
    {
        foreach ($this->entities as $entity) {
            if ($entity['schema'] === $slug) {
                return $entity['className'];
            }
        }
        return '';
    }

    public function getSlugByClassName(string $className)
    {
        foreach ($this->entities as $entity) {
            if ($entity['className'] === $className) {
                return $entity['schema'];
            }
        }
        return '';
    }

    public static function elementHook(string $slug)
    {
        return 'use' . implode("", array_map('ucfirst', explode("-", $slug))) . 'HookNae';
    }

    public function getDefaultSortForSchema(string $schema)
    {
        $sort = [
            ['key' => 'i.id', 'value' => 'DESC']
        ];

        foreach ($this->defaultItems as $df) {
            if ($df['config']['schema'] === $schema &&
                isset($df['config']['defaultSort']) &&
                $df['config']['defaultSort']
            ) {
                $sort = json_decode($df['config']['defaultSort'], true);
            }
        }
        return $sort;
    }
}