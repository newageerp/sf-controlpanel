<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InLocalConfigSyncVariablesConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:InLocalConfigSyncVariables';

    protected EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TMP OLD SYNC
        $db = LocalConfigUtils::getSqliteDb();
        $sql = 'select variables.id, variables.slug, variables.text from variables';
        $result = $db->query($sql);

        $variables = LocalConfigUtils::getCpConfigFileData('variables');
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $newId = 'synced-' . $data['id'];

            $isExist = false;
            foreach ($variables as $var) {
                if ($var['id'] === $newId) {
                    $isExist = true;
                }
            }
            if (!$isExist) {
                $variables[] = [
                    'id' => $newId,
                    'tag' => '',
                    'title' => '',
                    'config' => [
                        'slug' => $data['slug'],
                        'text' => $data['text']
                    ]
                ];
            }
        }
        file_put_contents(LocalConfigUtils::getCpConfigFile('variables'), json_encode($variables));
        // TMP OLD SYNC OFF

        $configPhpPath = LocalConfigUtils::getPhpVariablesPath() . '/NaeSVariables.php';
        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaeSVariables.tsx';
        $fileContent = '';

        $variables = LocalConfigUtils::getCpConfigFileData('variables');

        $phpFileContent = '<?php
namespace App\\Config;

class NaeSVariables {
';

        $dbData = [];
        foreach ($variables as $variable) {
            $dbData[$variable['config']['slug']] = $variable['config']['text'];

            $phpFileContent .= '
    public static function get' . ucfirst($variable['config']['slug']) . '(): string {
        return "' . $variable['config']['text'] . '";
    }
';
        }

        $phpFileContent .= '}
';

        $fileContent .= 'export const NaeSVariables = ' . json_encode($dbData, JSON_PRETTY_PRINT);

        file_put_contents(
            $configPath,
            $fileContent
        );

        file_put_contents(
            $configPhpPath,
            $phpFileContent
        );

        return Command::SUCCESS;
    }
}
