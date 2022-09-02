<?php

namespace Newageerp\SfControlpanel\Console\V3;

use Newageerp\SfControlpanel\Console\EntitiesUtilsV3;
use Newageerp\SfControlpanel\Console\PropertiesUtilsV3;
use Newageerp\SfControlpanel\Console\UtilsV3;
use Newageerp\SfControlpanel\Service\TemplateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PropertiesTemplatesConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:V3PropertiesTemplates';

    protected PropertiesUtilsV3 $propertiesUtils;

    protected EntitiesUtilsV3 $entitiesUtils;

    public function __construct(
        PropertiesUtilsV3 $propertiesUtils,
        EntitiesUtilsV3   $entitiesUtils,
    ) {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
        $this->entitiesUtils = $entitiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $propertiesMap = [];

        $entities = $this->entitiesUtils->getEntities();

        foreach ($entities as $key => $entity) {
            $slug = $entity['config']['slug'];
            $className = $entity['config']['className'];

            $properties = $this->propertiesUtils->getPropertiesForEntitySlug($slug);

            foreach ($properties as $property) {

                $components = [
                    'table-title',
                    'table-value',
                    'view-title',
                    'view-value',
                ];

                if (isset($property['config']['isDb']) && $property['config']['isDb']) {
                    $components[] = 'edit-title';
                    $components[] = 'edit-value';

                    $components[] = 'filter-title';
                    $components[] = 'filter-value';
                }

                $propertySlug = $property['config']['key'];

                $mainCompName = UtilsV3::fixComponentName(
                    [
                        $className,
                        $propertySlug,
                    ]
                );
                foreach ($components as $comp) {
                    $template = 'main';
                    [$compType, $compTypeValue] = explode("-", $comp);

                    $folder = UtilsV3::generatedV3Path(
                        [
                            $className,
                            'properties',
                            $propertySlug,
                            $compType
                        ]
                    );


                    $compName = UtilsV3::fixComponentName([$mainCompName, $comp, $template]);
                    $path = $folder . '/' . $compName . '.tsx';


                    $propertiesMap[] = [
                        'path' => UtilsV3::relPathForPath($folder),
                        'comp' => $compName
                    ];

                    (new TemplateService('v3/react/properties/' . $comp . '.html.twig'))->writeToFileOnChanges(
                        $path,
                        [
                            'compName' => $compName
                        ]
                    );
                }
            }

            // TODO TMP
            if ($key > 10) {
                break;
            }
        }


        (new TemplateService('v3/react/properties/properties.map.html.twig'))->writeToFileOnChanges(
            UtilsV3::generatedV3Path('') . 'PropertiesMap.tsx',
            [
                'propertiesMap' => $propertiesMap
            ]
        );

        return Command::SUCCESS;
    }
}
