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
        $entities = $this->entitiesUtils->getEntities();

        foreach ($entities as $entity) {
            $slug = $entity['config']['slug'];
            $className = $entity['config']['className'];

            $properties = $this->propertiesUtils->getPropertiesForEntitySlug($slug);

            foreach ($properties as $property) {
                
                $components = [
                    'edit-title',
                    'edit-value',
                    'filter-title',
                    'filter-value',
                    'table-title',
                    'table-value',
                    'view-title',
                    'view-value',
                ];

                $propertySlug = $property['config']['key'];
                $compName = UtilsV3::fixComponentName(
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


                    $compName = UtilsV3::fixComponentName($compName, $comp, $template);
                    $path = $folder .'/'.$compName.'.tsx';

                    (new TemplateService('v3/react/properties/'.$comp.'.html.twig'))->writeToFileOnChanges(
                        $path,
                        [
                            'compName' => $compName
                        ]
                    );
                }
            }
        }

        return Command::SUCCESS;
    }
}
