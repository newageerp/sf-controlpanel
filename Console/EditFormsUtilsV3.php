<?php

namespace Newageerp\SfControlpanel\Console;

class EditFormsUtilsV3
{
    protected array $editForms = [];

    public function __construct()
    {
        $this->editForms = LocalConfigUtils::getCpConfigFileData('edit');
    }

    /**
     * Get the value of editForms
     *
     * @return array
     */
    public function getEditForms(): array
    {
        return $this->editForms;
    }

    public function getEditFormBySchemaAndType(string $schema, string $type): ?array
    {
        $formsF = array_filter(
            $this->getEditForms(),
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
