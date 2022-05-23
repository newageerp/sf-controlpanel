<?php

namespace Newageerp\SfControlpanel\Console;

class EntitiesUtils
{
    public static function elementHook(string $slug)
    {
        return 'use' . implode("", array_map('ucfirst', explode("-", $slug))) . 'HookNae';
    }
}