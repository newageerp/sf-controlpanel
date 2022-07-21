<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class InGeneratorPdfs extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorPdfs';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/smarty',
        ]);

        $widgetsTemplate = $twig->load('pdfs/generated-widgets.html.twig');
        $pdfSchemaTemplate = $twig->load('pdfs/schema-pdf.html.twig');
        $generatedPath = Utils::generatedPath('pdfs/buttons');

        $pdfsData = LocalConfigUtils::getCpConfigFileData('pdfs');

        $components = [];
        $pdfs = [];
        foreach ($pdfsData as $pdf) {
            if (!isset($pdfs[$pdf['config']['entity']])) {
                $pdfs[$pdf['config']['entity']] = [];
            }

            $compName = 'Pdf' . Utils::fixComponentName($pdf['config']['entity']) . Utils::fixComponentName($pdf['config']['template']);

            $pdfs[$pdf['config']['entity']][] = [
                'sort' => (int)$pdf['config']['sort'],
                'template' => $pdf['config']['template'],
                'title' => $pdf['config']['title'],
                'skipList' => $pdf['config']['skipList'] === 1,
                'skipWithoutSign' => isset($pdf['config']['skipWithoutSign']) && $pdf['config']['skipWithoutSign'] === 1,
                'compName' => $compName
            ];
        }

        foreach ($pdfs as $slug => $list) {
            usort($list, function ($pdfA, $pdfB) {
                if ($pdfA['sort'] < $pdfB['sort']) {
                    return -1;
                }
                if ($pdfA['sort'] > $pdfB['sort']) {
                    return 1;
                }
                return 0;
            });


            $compName = 'Pdf' . Utils::fixComponentName($slug);
            $components[$slug] = $compName;

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = $pdfSchemaTemplate->render(
                [
                    'compName' => $compName,
                    'pdfs' => $list,
                    'schema' => $slug
                ]
            );
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        $fileName = Utils::generatedPath('pdfs') . '/GeneratedPdfWidgets.tsx';
        $generatedContent = $widgetsTemplate->render(
            [
                'components' => $components
            ]
        );
        Utils::writeOnChanges($fileName, $generatedContent);

        return Command::SUCCESS;
    }
}