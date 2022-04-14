<?php

namespace Newageerp\SfControlpanel\Console\Out;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutCacheDocJsonConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:OutCacheDocJson';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $docJsonFile = LocalConfigUtils::getDocJsonPath();
        $url = $_ENV['FRONT_URL'] . '/app/doc.json';

        $data = file_get_contents($url);

        file_put_contents(
            $docJsonFile,
            $data
        );

        return Command::SUCCESS;
    }
}
