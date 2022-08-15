<?php

namespace Newageerp\SfControlpanel\Service\Tabs;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\Defaults\DefaultsService;
use Newageerp\SfControlpanel\Service\TemplateService;

class TabsQuickSearchService
{
    protected array $tabs = [];

    protected DefaultsService $defaultsService;

    public function __construct(
        DefaultsService $defaultsService
    ) {
        $this->tabs = LocalConfigUtils::getCpConfigFileData('tabs');
        $this->defaultsService = $defaultsService;
    }

    public function generate()
    {
        $tService = new TemplateService('v2/tabs/qs/qs.html.twig');
        
        $tabsBySlug = [];
        foreach ($this->tabs as $tabItem) {
            $entity = $tabItem['config']['schema'];

            $quickSearch = [];
            if (isset($tabItem['config']['quickSearchFilterKeys']) && $tabItem['config']['quickSearchFilterKeys']) {
                $quickSearch = json_decode($tabItem['config']['quickSearchFilterKeys'], true);
            } else {
                $quickSearch = $this->defaultsService->getQuickSearchForSchema($tabItem['config']['schema']);
            }

            if (!isset($tabsBySlug[$entity])) {
                $tabsBySlug[$entity] = [];
            }

            $tabsBySlug[$entity][] = [
                'type' => $tabItem['config']['type'],
                'qs' => $quickSearch,
                'qsJs' => json_encode($quickSearch),
            ];
        }

        foreach ($tabsBySlug as $slug => $tabs) {
            $slugUc = Utils::fixComponentName($slug);
            $slugPath = Utils::generatedV2Path('tabs/' . $slugUc);
            $componentName = Utils::fixComponentName($slug . 'Qs');

            $tService->writeToFileOnChanges(
                $slugPath . '/' . $componentName . '.tsx',
                [
                    'compName' => $componentName,
                    'slugUc' => $slugUc,
                    'tabs' => $tabs,
                ]
            );
        }
    }
}
