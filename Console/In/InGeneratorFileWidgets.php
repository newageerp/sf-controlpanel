<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InGeneratorFileWidgets extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorFileWidgets';

    protected PropertiesUtils $propertiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/smarty',
        ]);
        $fileWidgetTemplate = $twig->load('element/widgets/files-widget.html.twig');

        $widgetsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/files-widget.json';
        $widgetItems = [];
        if (file_exists($widgetsFile)) {
            $widgetItems = json_decode(
                file_get_contents($widgetsFile),
                true
            );
        }
        $widgetItemsBySchema = [];
        foreach ($widgetItems as $widgetItem) {
            if (!isset($widgetItemsBySchema[$widgetItem['config']['schema']])) {
                $widgetItemsBySchema[$widgetItem['config']['schema']] = [];
            }
            $widgetItemsBySchema[$widgetItem['config']['schema']][] = $widgetItem;
        }

        $generatedPath = Utils::generatedPath('files/widgets');

        foreach ($widgetItemsBySchema as $schema => $items) {
            $compName = Utils::fixComponentName($schema) . 'FilesWidget';
            usort($items, function ($i1, $i2) {
                return $i1['config']['sort'] <=> $i2['config']['sort'];
            });
            $widgetItems = [];
            foreach ($items as $item) {
                $widgetItems[] = [
                    'title' => $item['config']['title'],
                    'type' => $item['config']['type'],
                    'hint' => isset($item['config']['hint'])?$item['config']['hint']:'',
                ];
            }

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $content = $fileWidgetTemplate->render([
                'compName' => $compName,
                'files' => $widgetItems,
                'schema' => $schema
            ]);
            Utils::writeOnChanges($fileName, $content);
        }

        return Command::SUCCESS;
    }
}