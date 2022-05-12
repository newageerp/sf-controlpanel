<?php

namespace Newageerp\SfControlpanel\Console\In;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;

class InGeneratorMenu  extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorMenu';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        $tabsFile =

        return Command::SUCCESS;
    }
}