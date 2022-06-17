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

        $pdfSchemaTemplate = $twig->load('pdfs/schema-pdf.html.twig');
        $generatedPath = Utils::generatedPath('pdfs/buttons');

        $db = LocalConfigUtils::getSqliteDb();

        $sql = 'select 
            pdfs.template, 
            pdfs.title, 
            pdfs.skipList, 
            pdfs.sort, 
            pdfs.skipWithoutSign, 
            entities.slug from pdfs
        left join entities on entities.id = pdfs.entity ';

        $result = $db->query($sql);

        $pdfs = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!isset($pdfs[$data['slug']])) {
                $pdfs[$data['slug']] = [];
            }

            $compName = 'Pdf' . Utils::fixComponentName($data['slug']) . Utils::fixComponentName($data['template']);

            $pdfs[$data['slug']][] = [
                'sort' => (int)$data['sort'],
                'template' => $data['template'],
                'title' => $data['title'],
                'skipList' => $data['skipList'] === 1,
                'skipWithoutSign' => isset($data['skipWithoutSign']) && $data['skipWithoutSign'] === 1,
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

        return Command::SUCCESS;
    }
}