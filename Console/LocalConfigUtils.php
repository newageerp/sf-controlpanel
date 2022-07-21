<?php

namespace Newageerp\SfControlpanel\Console;

class LocalConfigUtils
{
    public static function getSqliteDb()
    {
        if (!file_exists($_ENV['NAE_SFS_CP_DB_PATH'])) {
            return false;
        }
        $configDbFile = $_ENV['NAE_SFS_CP_DB_PATH'];
        return new \SQLite3($configDbFile);
    }

    public static function getCpConfigFile(string $file)
    {
        return $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/' . $file . '.json';
    }
    public static function getCpConfigFileData(string $file)
    {
        $file = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/' . $file . '.json';
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([]));
        }
        return json_decode(file_get_contents($file), true);
    }

    public static function getDocJsonPath()
    {
        return $_ENV['NAE_SFS_FRONT_URL'] . '/app/doc.json';
    }

    public static function getFrontendHooksPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/front-hooks';
    }

    public static function getFrontendModelsPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/front-models';
    }

    public static function getFrontendModelsCachePath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/front-models-cache';
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
