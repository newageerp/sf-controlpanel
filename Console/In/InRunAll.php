<?php

namespace Newageerp\SfControlpanel\Console\In;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InRunAll extends Command
{
    protected static $defaultName = 'nae:localconfig:InRunAll';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commands = [
            'nae:localconfig:InLocalConfigSyncPdfs',
            'nae:localconfig:InLocalConfigSyncStatuses',
            'nae:localconfig:InLocalConfigSyncVariables',
            'nae:localconfig:InLocalConfigSyncFields',
            'nae:localconfig:InLocalConfigSyncEntities',
            'nae:localconfig:InLocalConfigSyncUsersPermissions',
            'nae:localconfig:InPathMap',
            'nae:localconfig:InGeneratorMenu',
            'nae:localconfig:InGeneratorTabs',
            'nae:localconfig:InGeneratorStatuses',
            'nae:localconfig:InGeneratorEnums',
            'nae:localconfig:InGeneratorBadges',
            'nae:localconfig:InGeneratorEditForms',
            'nae:localconfig:InFillModels',
            'nae:localconfig:InGeneratorRoutes',
            'nae:localconfig:InGeneratorFileWidgets',
            'nae:localconfig:InGeneratorPdfs',
            'nae:localconfig:InGeneratorLayout'
        ];

        foreach ($commands as $commandName) {
            $output->writeln('Running command ' . $commandName);

            $greetInput = new ArrayInput([]);

            $command = $this->getApplication()->find($commandName);
            $returnCode = $command->run($greetInput, $output);

            $output->writeln('Finnish command ' . $commandName . ' with status code ' . $returnCode);
        }

        return Command::SUCCESS;
    }
}
