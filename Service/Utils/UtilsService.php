<?php

namespace Newageerp\SfControlpanel\Service\Utils;

use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\TemplateService;

class UtilsService
{
    public function generate()
    {
        $tService = new TemplateService('v2/utils/events.html.twig');
        $utilsPath = Utils::generatedV2Path('utils');

        $tService->writeToFileOnChanges(
            $utilsPath . '/utils.ts',
            [
                
            ]
        );
    }
}
