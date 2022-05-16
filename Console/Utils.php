<?php

namespace Newageerp\SfControlpanel\Console;
use Symfony\Component\Filesystem\Filesystem;
class Utils
{
    public static function generatedPath(string $folder) {
        $fs = new Filesystem();
        $generatedPath = LocalConfigUtils::getFrontendGeneratedPath() . '/'.$folder;
        if (!$fs->exists($generatedPath)) {
            $fs->mkdir($generatedPath);
        }
        return $generatedPath;
    }

    public static function writeOnChanges(string $fileName, string $content) {
        $fs = new Filesystem();

        $localContents = '';
        if ($fs->exists($fileName)) {
            $localContents = file_get_contents($fileName);
        }

        if ($localContents !== $content) {
            file_put_contents(
                $fileName,
                $content
            );
        }
    }

    public static function fixComponentName(string $compName)
    {
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