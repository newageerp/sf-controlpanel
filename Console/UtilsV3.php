<?php

namespace Newageerp\SfControlpanel\Console;

use Symfony\Component\Filesystem\Filesystem;

class UtilsV3
{
    public static function generatedV3Path(string | array $folder)
    {
        if (is_array($folder)) {
            $folder = implode("/", $folder);
        }

        $fs = new Filesystem();
        $generatedPath = LocalConfigUtils::getFrontendGeneratedPath() . '/v3/' . $folder;
        if (!$fs->exists($generatedPath)) {
            $fs->mkdir($generatedPath);
        }
        return $generatedPath;
    }

    public static function fixComponentName(string | array $compName)
    {
        if (is_array($compName)) {
            $compName = implode('-', $compName);
        }
        
        return implode(
            "",
            array_map(
                function ($p) {
                    return ucfirst($p);
                },
                explode(
                    "/",
                    str_replace(
                        '-',
                        '/',
                        $compName
                    )
                )
            )
        );
    }
}
