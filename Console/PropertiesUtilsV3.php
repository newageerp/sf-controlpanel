<?php

namespace Newageerp\SfControlpanel\Console;

class PropertiesUtilsV3
{
    protected array $properties = [];

    public function __construct()
    {
        $propertiesFile = LocalConfigUtilsV3::getConfigCpPath() . '/properties.json';
        $this->properties = [];
        if (file_exists($propertiesFile)) {
            $this->properties = json_decode(
                file_get_contents($propertiesFile),
                true
            );
        }
    }

    public function getPropertiesForEntitySlug(string $slug)
    {
        return array_filter(
            $this->properties,
            function ($item) use ($slug) {
                return $item['config']['entity'] === $slug;
            }
        );
    }
}
