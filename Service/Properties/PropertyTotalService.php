<?php

namespace Newageerp\SfControlpanel\Service\Properties;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\TemplateService;

class PropertyTotalService
{
    protected array $properties = [];

    public function __construct()
    {
        $this->properties = LocalConfigUtils::getCpConfigFileData('properties');
    }

    public function generate()
    {
        // $tService = new TemplateService('v2/properties/totals/totals.html.twig');

        // $totals = array_filter($this->properties, function ($item) {
        //     return $item['config']['available_total'] === 1 || $item['config']['available_total'] === true;
        // });

        // $totalsByEntity = [];
        // foreach ($totals as $total) {
        //     $entity = $total['config']['entity'];
        //     if (!isset($totalsByEntity[$entity])) {
        //         $totalsByEntity[$entity] = [];
        //     }
        //     $totalsByEntity[$entity][] = $total;
        // }

        // foreach ($totalsByEntity as $slug => $totals) {
        //     $slugUc = Utils::fixComponentName($slug);
        //     $slugPath = Utils::generatedV2Path('properties/' . $slugUc);
        //     $componentName = Utils::fixComponentName($slug . 'Totals');

        //     $totals = array_map(
        //         function ($item) use ($slug) {
        //             $item['config']['compName'] = Utils::fixComponentName($slug . 'Totals-' . $item['config']['key']);
        //             return $item['config'];
        //         },
        //         $totals
        //     );

        //     $tService->writeToFileOnChanges(
        //         $slugPath . '/' . $componentName . '.tsx',
        //         [
        //             'compName' => $componentName,
        //             'totals' => $totals,
        //             'slugUc' => $slugUc,
        //         ]
        //     );
        // }
    }
}
