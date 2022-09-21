<?php

namespace Newageerp\SfControlpanel\Console;

class ViewFormsUtilsV3
{
    protected array $viewForms = [];

    public function __construct()
    {
        $this->viewForms = LocalConfigUtils::getCpConfigFileData('view');
    }

    /**
     * Get the value of viewForms
     *
     * @return array
     */
    public function getViewForms(): array
    {
        return $this->viewForms;
    }

    public function getViewFormBySchemaAndType(string $schema, string $type): ?array
    {
        $formsF = array_filter(
            $this->getViewForms(),
            function ($item) use ($schema, $type) {
                return $item['config']['schema'] === $schema && $item['config']['type'] === $type;
            }
        );
        if (count($formsF) > 0) {
            return reset($formsF)['config'];
        }
        return null;
    }
}
