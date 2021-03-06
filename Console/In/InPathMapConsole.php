<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InPathMapConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:InPathMap';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $docJsonFile = LocalConfigUtils::getDocJsonPath();
        $docJsonData = json_decode(file_get_contents($docJsonFile), true);

        $configPath = LocalConfigUtils::getFrontendConfigPath() . '/NaePaths.tsx';

        $paths = $docJsonData['paths'];

        $map = [];
        foreach ($paths as $path => $data) {
            foreach ($data as $method => $methodData) {
                $map[] = [
                    'id' => $methodData['operationId'],
                    'method' => $method,
                    'path' => $path,
                    'parameters' => $methodData['parameters'] ?? []
                ];
            }
        }

        $fileContent = 'export const NaePaths = ' . json_encode($map, JSON_PRETTY_PRINT);

        $fileContent .= '

export const NaeApiFunctions = {';

        foreach ($paths as $path => $data) {
            foreach ($data as $method => $methodData) {
                $parameters = [];
                $replaces = [];

                if ($method === 'post') {
                    $parameters[] = 'data: any';
                }
                if (isset($methodData['parameters'])) {
                    foreach ($methodData['parameters'] as $param) {
                        $parameters[] = $param['name'] . ': ' . $param['schema']['type'];
                        $replaces[] = '.replace(\'{' . $param['name'] . '}\', ' . $param['name'] . ')';
                    }
                }
                $fileContent .= '
    \'' . $methodData['operationId'] . '\': (' . implode(',', $parameters) . ') => {
        const url = \'' . $path . '\''.implode('', $replaces).';
    },
';
            }
        }

        $fileContent .= '
        }';

        file_put_contents(
            $configPath,
            $fileContent
        );


        return Command::SUCCESS;
    }
}
