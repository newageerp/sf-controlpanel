<?php

namespace Newageerp\SfControlpanel\Console;

class EntitiesUtils
{
    protected array $entities = [];

    public function __construct()
    {
        $this->entities = json_decode(
            file_get_contents(LocalConfigUtils::getPhpCachePath() . '/NaeSSchema.json'),
            true
        );
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
}