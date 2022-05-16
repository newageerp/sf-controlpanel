<?php

namespace Newageerp\SfControlpanel\Console;

class Utils
{
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