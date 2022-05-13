<?php

namespace Newageerp\SfControlpanel\Service;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;

class MenuService
{
    protected array $entities;

    public function __construct()
    {
        $this->entities = json_decode(file_get_contents(LocalConfigUtils::getPhpCachePath() . '/NaeSSchema.json'), true);
    }

    public function menuTitleForMenu(array $menuItem) : string
    {
        $menuTitle = '';
        if (isset($menuItem['config']['customTitle']) && $menuItem['config']['customTitle']) {
            $menuTitle = $menuItem['config']['customTitle'];
        } else {
            foreach ($this->entities as $entity) {
                if ($entity['schema'] === $menuItem['config']['schema']) {
                    $menuTitle = $entity['titlePlural'];
                }
            }
        }
        return $menuTitle;
    }

    public function menuLinkForMenu(array $menuItem): string
    {
        $menuLink = '';
        if (isset($menuItem['config']['customLink']) && $menuItem['config']['customLink']) {
            $menuLink = $menuItem['config']['customLink'];
        } else if ($menuItem['config']['schema'] and $menuItem['config']['type']) {
            $menuLink = '/u/' . $menuItem['config']['schema'] . '/' . $menuItem['config']['type'] . '/list';
        }
        return $menuLink;
    }

    public function componentNameForMenu(array $menuItem): string
    {
        $compName = '';
        if (isset($menuItem['config']['customLink']) && $menuItem['config']['customLink']) {
            $compName = implode(
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
                            $menuItem['config']['customLink']
                        )
                    )
                )
            );
        } else if ($menuItem['config']['schema'] and $menuItem['config']['type']) {
            $compName = ucfirst($menuItem['config']['schema']) . ucfirst($menuItem['config']['type']);
        }
        return 'MenuItem' . $compName;
    }
}