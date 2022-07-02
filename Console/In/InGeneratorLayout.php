<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InGeneratorLayout extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorLayout';

    protected PropertiesUtils $propertiesUtils;
    protected EntitiesUtils $entitiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils, EntitiesUtils $entitiesUtils)
    {
        parent::__construct();

        $this->propertiesUtils = $propertiesUtils;
        $this->entitiesUtils = $entitiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp',
        ]);

        // view top
        $viewTopTemplate = $twig->load('layout/view-top.html.twig');
        $generatedContent = $viewTopTemplate->render();
        $fileName = Utils::generatedPath('layout/view') . '/ViewTop.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // toolbar layout rels create
        $relsCreateFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/rels-create.json';
        if (file_exists($relsCreateFile)) {
            $relsList = json_decode(file_get_contents($relsCreateFile));
            $rels = [];
            foreach ($relsList as $relItem) {
                if (!isset($rels[$relItem['source']])) {
                    $rels[$relItem['source']] = [];
                }
                if (!isset($relItem['targetTitle'])) {
                    $relItem['targetTitle'] = $this->entitiesUtils->getTitleBySlug($relItem['target']);
                }
                if (!isset($relItem['type'])) {
                    $relItem['type'] = 'main';
                }
                $rels[$relItem['source']][] = $relItem;
            }

            $toolbarItemTemplate = $twig->load('layout/toolbar-items-rel-create.html.twig');
            foreach ($rels as $source => $items) {
                $compName = Utils::fixComponentName($source) . 'RelCreate';
                $fileName = Utils::generatedPath('layout/view/toolbar-items') . '/' . $compName . '.tsx';

                $generatedContent = $toolbarItemTemplate->render(['compName' => $compName, 'items' => $items, 'schema' => $source]);
                Utils::writeOnChanges($fileName, $generatedContent);
            }
        }

        return Command::SUCCESS;
    }
}