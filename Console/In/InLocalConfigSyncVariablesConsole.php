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
        $db = LocalConfigUtils::getSqliteDb();

        $configPhpPath = LocalConfigUtils::getPhpVariablesPath() . '/NaeSVariables.php';

        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaeSVariables.tsx';
        $fileContent = '';

        $sql = 'select variables.slug, variables.text from variables';

        $result = $db->query($sql);

        $phpFileContent = '<?php
namespace App\\Config;

class NaeSVariables {
';

        $dbData = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $dbData[$data['slug']] = $data['text'];

            $phpFileContent .= '
    public static function get' . ucfirst($data['slug']) . '(): string {
        return "' . $data['text'] . '";
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
