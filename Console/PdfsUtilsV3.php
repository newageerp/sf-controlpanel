<?php

namespace Newageerp\SfControlpanel\Console;

class PdfsUtilsV3
{
    protected array $pdfs = [];

    public function __construct()
    {
        $this->pdfs = LocalConfigUtils::getCpConfigFileData('pdfs');
    }

    public function getPdfItemsForSchema(string $schema) {
        $pdfs = array_filter(
            $this->pdfs,
            function ($item) use ($schema) {
                return $item['config']['entity'] === $schema;
            }
        );
        return array_map(
            function ($item) {
                return $item['config'];
            },
            $pdfs
        );
    }
}
