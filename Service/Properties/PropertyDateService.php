<?php

namespace Newageerp\SfControlpanel\Service\Properties;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\Defaults\DefaultsService;
use Newageerp\SfControlpanel\Service\TemplateService;

class PropertyDateService
{
    protected array $properties = [];

    protected DefaultsService $defaultsService;

    public function __construct(DefaultsService $defaultsService)
    {
        $this->properties = LocalConfigUtils::getCpConfigFileData('properties');
        $this->defaultsService = $defaultsService;
    }

    public function generate()
    {
        $tService = new TemplateService('v2/properties/dates/dates.html.twig');

        $dates = [];
        foreach ($this->properties as $property) {
            if ($property['config']['type'] === 'string' && $property['config']['typeFormat'] === 'date') {
                $entity = $property['config']['entity'];
                if (!isset($dates[$entity])) {
                    $dates[$entity] = [];
                }
                $dates[$entity][] = $property['config'];
            }
        }

        foreach ($dates as $slug => $properties) {
            $slugUc = Utils::fixComponentName($slug);
            $slugPath = Utils::generatedV2Path('properties/' . $slugUc);
            $componentName = Utils::fixComponentName($slug . 'Totals');

            $parseProps = [];
            foreach ($properties as $property) {
                if ($this->defaultsService->isFieldExistsInDefaults($slug, $slug . '.' . $property['key'])) {
                    $parseProps[] = $property;
                }
            }

            if (count($parseProps) > 0) {
                $parseProps = array_map(
                    function ($item) use ($slug) {
                        $item['config']['compName'] = Utils::fixComponentName($slug . 'Date-' . $item['config']['key']);
                        return $item['config'];
                    },
                    $parseProps
                );

                $tService->writeToFileOnChanges(
                    $slugPath . '/' . $componentName . '.tsx',
                    [
                        'compName' => $componentName,
                        'properties' => $parseProps,
                        'slugUc' => $slugUc,
                    ]
                );
            }
        }
    }
}
