<?php

namespace Newageerp\SfControlpanel\Service\Defaults;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;

class DefaultsService
{
    protected array $defaults = [];

    public function __construct()
    {
        $this->defaults = LocalConfigUtils::getCpConfigFileData('defaults');
    }


    public function getDefaultsForSchema(string $schema)
    {
        foreach ($this->defaults as $df) {
            if (
                $df['config']['schema'] === $schema
            ) {
                return $df;
            }
        }
        return null;
    }

    public function getQuickSearchForSchema(string $schema)
    {
        $df = $this->getDefaultsForSchema($schema);
        if (
            $df && isset($df['config']['defaultQuickSearch']) &&
            $df['config']['defaultQuickSearch']
        ) {
            return json_decode($df['config']['defaultQuickSearch'], true);
        }
        return [];
    }
}
