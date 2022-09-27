<?php

namespace Newageerp\SfControlpanel\Service\Entities;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\TemplateService;

class EntitiesService {
    protected $entities = [];

    public function __construct()
    {
        $this->entities = LocalConfigUtils::getCpConfigFileData('entities');
    }

    public function generate() {
        // $tService = new TemplateService('v2/entities/entities/titles.html.twig');

        // foreach ($this->entities as $entity) {
        //     $slug = $entity['config']['slug'];
        //     $slugUc = Utils::fixComponentName($slug);

        //     $slugPath = Utils::generatedV2Path('entities/' . $slugUc);
        //     $componentName = Utils::fixComponentName($slug . '-titles');

        //     $tService->writeToFileOnChanges(
        //         $slugPath . '/' . $componentName . '.tsx',
        //         [
        //             'compName' => $componentName,
        //             'titleSingle' => $entity['config']['titleSingle'],
        //             'titlePlural' => $entity['config']['titlePlural'],
        //         ]
        //     );
        // }
    }
}