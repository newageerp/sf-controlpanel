<?php

namespace Newageerp\SfControlpanel\Service;

use Newageerp\SfControlpanel\Console\Utils;
use Twig\Environment;
use Twig\TemplateWrapper;

class TemplateService
{
    protected string $templateName = '';

    protected Environment $twig;

    protected TemplateWrapper $template;

    protected array $params = [];

    public function __construct(string $templateName)
    {
        $this->templateName = $templateName;

        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/templates');
        $this->twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/smarty',
        ]);
        $this->template = $this->twig->load($templateName);
    }

    public function writeToFileOnChanges(string $fileName, $params = [])
    {
        $generatedContent = $this->render($params);
        Utils::writeOnChanges($fileName, $generatedContent);
    }

    public function writeIfNotExists(string $fileName, $params = [])
    {
        if (!file_exists($fileName)) {
            $this->writeToFileOnChanges($fileName, $params);
        }
    }

    public function render($params = [])
    {
        return $this->template->render($params ? $params : $this->params);
    }

    /**
     * Get the value of params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set the value of params
     *
     * @param array $params
     *
     * @return self
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }
}
