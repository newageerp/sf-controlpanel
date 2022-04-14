<?php

namespace Newageerp\SfControlpanel\Console\Out;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutRunAll extends Command
{
    protected static $defaultName = 'nae:localconfig:OutRunAll';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commands = [
            'cache:clear',
            'nae:localconfig:OutCacheDocJson',
            'nae:localconfig:OutLocalConfigBuildMock',
            'nae:localconfig:OutLocalConfigSyncSchemas',
            'nae:localconfig:OutLocalConfigSyncProperties',
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
