<?php

namespace Newageerp\SfControlpanel\Console;

class PropertiesUtils
{
    protected array $properties = [];

    public function __construct()
    {
        $this->properties = json_decode(
            file_get_contents(LocalConfigUtils::getPhpCachePath() . '/properties.json'),
            true
        );
    }

    public function getPropertyForPath(string $_path)
    {
        $path = explode(".", $_path);
        $pathLen = count($path);
        if ($pathLen === 1) {
            return null;
        } else {
            $_schema = '';
            foreach ($path as $i => $pathPart) {
                if ($i === 0) {
                    $_schema = $pathPart;
                } else if ($i === $pathLen - 1) {
                    return $this->getPropertyForSchema($_schema, $pathPart);
                } else {
                    $_lastSchema = $_schema;
                    $_schema = '';
                    $prop = $this->getPropertyForSchema($_lastSchema, $pathPart);
                    if (
                        ($prop['type'] === 'array' || $prop['type'] === 'rel') &&
                        isset($prop['format']) &&
                        $prop['format']
                    ) {
                        $_schema = $prop['format'];
                    }
                }
            }
        }
        return null;
    }

    public function getPropertyForSchema(string $schema, string $key)
    {
        foreach ($this->properties as $property) {
            if ($property['key'] === $key && $property['schema'] === $schema) {
                return $property;
            }
        }
        return null;
    }
}