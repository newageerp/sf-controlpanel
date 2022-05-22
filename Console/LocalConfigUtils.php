<?php

namespace Newageerp\SfControlpanel\Console;

class LocalConfigUtils
{
    public static function getSqliteDb()
    {
        $configDbFile = $_ENV['NAE_SFS_CP_DB_PATH'];
        return new \SQLite3($configDbFile);
    }

    public static function getDocJsonPath()
    {
        return $_ENV['NAE_SFS_FRONT_URL'] . '/app/doc.json';
    }

    public static function getFrontendConfigPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/front-end-config';
    }

    public static function getFrontendGeneratedPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/front-generated';
    }

    public static function getStrapiCachePath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/strapi';
    }

    public static function getCpDbPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/config-storage';
    }

    public static function getPhpCachePath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/assets/properties';
    }

    public static function getPhpVariablesPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/src/Config';
    }

    public static function getPhpEntitiesPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/src/Entity';
    }

    public static function getPhpControllerPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/src/Controller';
    }

    public static function transformCamelCaseToKey(string $key)
    {
        $output = [];

        for ($i = 0; $i < mb_strlen($key); $i++) {
            $l = $key[$i];

            if ($l === mb_strtoupper($l) && $i !== 0) {
                $output[] = '-';
            }
            $output[] = mb_strtolower($l);
        }

        return implode("", $output);
    }

    public static function transformKeyToCamelCase(string $key)
    {
        $upper = false;
        $output = [];

        for ($i = 0; $i < mb_strlen($key); $i++) {
            $l = $key[$i];

            if ($l === '-') {
                $upper = true;
            } else {
                if ($upper) {
                    $upper = false;
                    $output[] = mb_strtoupper($l);
                } else {
                    $output[] = mb_strtolower($l);
                }
            }

        }

        return implode("", $output);
    }
}
