<?php

namespace Newageerp\SfControlpanel\Console\Maker;

use Newageerp\SfControlpanel\Service\TemplateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakePluginWithServiceAndMessageHandler extends Command
{
    protected static $defaultName = 'nae:maker:PluginWithServiceAndMessageHandler';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // ...
            ->addArgument('name', InputArgument::REQUIRED, 'Plugin name')
            ->addArgument('entity_name', InputArgument::OPTIONAL, 'Entity name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = [];
        $params['name'] = $input->getArgument('name');
        $params['pluginName'] = $input->getArgument('name');
        $params['entityName'] = $input->getArgument('entity_name');
        $params['serviceName'] = $params['pluginName'] . 'Service';
        $params['messageName'] = $params['pluginName'] . 'Message';
        $params['messageHandlerName'] = $params['pluginName'] . 'MessageHandler';

        $tService = new TemplateService('maker/PluginWithServiceAndMessageHandler/service.html.twig');
        $tMessage = new TemplateService('maker/PluginWithServiceAndMessageHandler/message.html.twig');
        $tMessageHandler = new TemplateService('maker/PluginWithServiceAndMessageHandler/message-handler.html.twig');

        $dir = '/var/www/symfony/src/Plugins/' . $params['pluginName'];

        if (!is_dir($dir)) {
            mkdir($dir);

            $tService->writeIfNotExists($dir . '/' . $params['serviceName'] . '.php', $params);
            $tMessage->writeIfNotExists($dir . '/' . $params['messageName'] . '.php', $params);
            $tMessageHandler->writeIfNotExists($dir . '/' . $params['messageHandlerName'] . '.php', $params);

        } else {
            $output->writeln('Directory ' . $params['pluginName'] . ' already exists');
        }

        return Command::SUCCESS;
    }
}
