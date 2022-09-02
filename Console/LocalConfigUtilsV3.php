<?php

namespace Newageerp\SfControlpanel\Console;

class LocalConfigUtilsV3
{
    public static function getConfigCpPath()
    {
        return $_ENV['NAE_SFS_CP_STORAGE_PATH'];
    }
    
    public static function getFrontendGeneratedPath()
    {
        return $_ENV['NAE_SFS_ROOT_PATH'] . '/front-generated';
    }
}
