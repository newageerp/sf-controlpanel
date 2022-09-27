<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Service\Defaults\DefaultsService;
use Newageerp\SfControlpanel\Service\TemplateService;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorTabs extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorTabs';

    protected PropertiesUtils $propertiesUtils;

    protected EntitiesUtils $entitiesUtils;

    protected DefaultsService $defaultsService;

    public function __construct(
        PropertiesUtils $propertiesUtils,
        EntitiesUtils $entitiesUtils,
        DefaultsService $defaultsService,
    ) {
        parent::__construct();
        $this->propertiesUtils = $propertiesUtils;
        $this->entitiesUtils = $entitiesUtils;
        $this->defaultsService = $defaultsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/smarty',
        ]);

        $defaultSearchToolbarTemplate = $twig->load('tabs/DefaultSearchToolbar.html.twig');
        $tableDataSourceTemplate = $twig->load('tabs/TableDataSource.html.twig');
        $tableDataSourceRelTemplate = $twig->load('tabs/TableDataSourceRel.html.twig');
        $customColumnFunctionTemplate = $twig->load('tabs/CustomColumnFunction.html.twig');

        $customEfFunctionTemplateMap = new TemplateService('v3/list/CustomListComponentsMap.html.twig');

        $tablesDataSourceTemplate = $twig->load('tabs/TablesDataSource.html.twig');
        $tablesDataSourceComponents = [];

        $tabTableT = new TemplateService('tabs/TabTable.html.twig');


        $defaultItems = LocalConfigUtils::getCpConfigFileData('defaults');

        $tabItems = LocalConfigUtils::getCpConfigFileData('tabs');

        $generatedPath = Utils::generatedPath('tabs/tables');
        $dataSourceGeneratedPath = Utils::generatedPath('tabs/tables-data-source');
        // $dataSourceRelGeneratedPath = Utils::generatedPath('tabs/tables-rel-data-source');

        $dataSourceCustomGeneratedPath = Utils::customFolderPath('tabs/tables-data-source');
        $tabCustomComponentsGeneratedPath = Utils::customFolderPath('tabs/components');

        $customComponents = [];

        foreach ($tabItems as $tabItem) {
            $tpHead = [];
            $tpBody = [];
            $tpRowData = [];
            $tpImports = [];

            $compName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                    ucfirst($tabItem['config']['type']) . 'Table'
            );
            $dataSourceCompName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                    ucfirst($tabItem['config']['type']) . 'TableDataSource'
            );

            if (isset($tabItem['config']['allowMultipleSelection']) && $tabItem['config']['allowMultipleSelection']) {
                $tpHead[] = '<Th><input checked={isCheckedAll} onChange={toggleSelectAll} type="checkbox" /></Th>';
                $tpBody[] = '<Td><input type="checkbox" checked={isChecked} onClick={() => toggleSelect(item?.id)} /></Td>';
                $tpRowData[] = 'const isChecked = selectedIds.indexOf(item?.id) >= 0;';
            }

            $totals = isset($tabItem['config']['totals']) ? $tabItem['config']['totals'] : [];

            foreach ($tabItem['config']['columns'] as $columnIndex => $column) {


                $tdClassName = [];

                $colProperty = $this->propertiesUtils->getPropertyForPath($column['path']);

                $colPropertyNaeType = '';
                if ($colProperty) {
                    $colPropertyNaeType = $this->propertiesUtils->getPropertyNaeType($colProperty, $column);
                }

                $tdTemplateData = $this->propertiesUtils->getDefaultPropertyTableValueTemplate($colProperty, $column);
                $tpImports[] = $tdTemplateData['import'];

                $textAlignment = 'textAlignment="' . $this->propertiesUtils->getPropertyTableAlignment($colProperty, $column) . '"';
                $openTagTh = '<Th ' . $textAlignment . '>';


                $thTemplate = $openTagTh . '</Th>';
                if ($column['customTitle']) {
                    $thTemplate = $openTagTh . '{t("' . $column['customTitle'] . '")}</Th>';
                } else if ($column['titlePath']) {
                    $prop = $this->propertiesUtils->getPropertyForPath($column['titlePath']);
                    if ($prop) {
                        $thTemplate = $openTagTh . '{t("' . $prop['title'] . '")}</Th>';
                    }
                } else if ($column['path']) {
                    if ($colProperty) {
                        $thTemplate = $openTagTh . '{t("' . $colProperty['title'] . '")}</Th>';
                    }
                }
                $tpHead[] = $thTemplate;

                // TD
                if ($colPropertyNaeType === 'date' || $colPropertyNaeType === 'datetime') {
                    $tdClassName[] = 'whitespace-nowrap';
                }

                $pathArray = explode(".", $column['path']);
                $pathArray[0] = 'item';

                $varName = lcfirst(implode(array_map('ucfirst', $pathArray)) . $columnIndex);
                $varValue = implode("?.", $pathArray);
                $tpRowData[] = 'const ' . $varName . ' = ' . $varValue . ';';
                $varNameId = null;
                if (count($pathArray) > 2) {
                    $pathArray[count($pathArray) - 1] = 'id';
                    $varNameId = lcfirst(implode(array_map('ucfirst', $pathArray)) . $columnIndex . 'Id');
                    $varValueId = implode("?.", $pathArray);
                    $tpRowData[] = 'const ' . $varNameId . ' = ' . $varValueId . ';';
                }

                $wrapStart = $column['link'] > 0 ? "
<UI.Buttons.SchemaMultiLink
    id={" . ($varNameId ?: 'item.id') . "}
    schema={'" . $colProperty['schema'] . "'}
    className={'text-left'}
    onClick={navigate?() => {
        navigate('" . $colProperty['schema'] . "', " . ($varNameId ?: 'item.id') . ", item);
    }:undefined}
    buttonsNl={false}
    onClickDef={'" . ($column['link'] === 10 ? 'main' : 'popup') . "'}
>
" : '';

                $wrapFinish = $column['link'] > 0 ? '
</UI.Buttons.SchemaMultiLink>
' : '';

                $openTagTd = '<Td ' . $textAlignment . ' className="' . implode(" ", $tdClassName) . '">';
                $tdTemplate = $openTagTd .
                    $wrapStart .
                    str_replace(
                        [
                            'TP_VALUE',
                            'TP_KEY',
                            'TP_SCHEMA'
                        ],
                        [
                            $varName,
                            $colProperty ? $colProperty['key'] : '',
                            $colProperty ? $colProperty['schema'] : '',
                        ],
                        $tdTemplateData['template']
                    ) .
                    $wrapFinish .
                    '</Td>';

                if (isset($column['componentName']) && $column['componentName']) {
                    if (mb_strpos($column['componentName'], 'pdf:') === 0) {
                        $pdfTemplateA = explode(":", $column['componentName']);
                        $pdfTemplateName = end($pdfTemplateA);

                        $pdfComponentBase = 'Pdf' . Utils::fixComponentName($tabItem['config']['schema']);
                        $pdfComponentName = $pdfComponentBase . Utils::fixComponentName($pdfTemplateName);

                        $tpImports[] = 'import { ' . $pdfComponentName . ' } from "../../pdfs/buttons/' . $pdfComponentBase . '";';

                        $tdTemplate = $openTagTd .
                            $wrapStart .
                            '<' . $pdfComponentName . ' id={' . $varName . '} />'
                            .
                            $wrapFinish .
                            '</Td>';
                    } else {

                        $componentNameA = explode("/", $column['componentName']);
                        $customComponentName = end($componentNameA);
                        $componentNamePath = $tabCustomComponentsGeneratedPath . '/' . $column['componentName'] . '.tsx';
                        if (!file_exists($componentNamePath)) {
                            Utils::customFolderPath('tabs/components/' . implode("/", array_slice($componentNameA, 0, -1)));

                            $generatedContent = $customColumnFunctionTemplate->render(['compName' => $customComponentName]);
                            Utils::writeOnChanges($componentNamePath, $generatedContent);
                        }

                        $tpImports[] = 'import ' . $customComponentName . ' from "../../_custom/tabs/components/' . $column['componentName'] . '"';

                        $tdTemplate = $openTagTd .
                            $wrapStart .
                            '<' . $customComponentName . ' item={item} value={' . $varName . '} {...props.customComponentOptions} />'
                            .
                            $wrapFinish .
                            '</Td>';

                        $customComponents[$column['componentName']] = [
                            'componentName' => $column['componentName'],
                            'name' => $customComponentName.mb_substr(md5($column['componentName']), 0, 5),
                        ];
                    }

                    
                }

                $tpBody[] = $tdTemplate;
            }
            $tpHeadStr = implode("\n", $tpHead);
            $tpBodyStr = implode("\n", $tpBody);
            $tpRowDataStr = implode("\n", $tpRowData);
            $tpImportsStr = implode("\n", array_unique($tpImports));


            $tabTableT->writeToFileOnChanges(
                $generatedPath . '/' . $compName . '.tsx',
                [
                    'TP_COMP_NAME' => $compName,
                    'TP_THEAD' => $tpHeadStr,
                    'TP_TBODY' => $tpBodyStr,
                    'TP_ROW_DATA' => $tpRowDataStr,
                    'TP_IMPORT' => $tpImportsStr,
                    'TP_SCHEMA' => $tabItem['config']['schema'],
                    'totals' => $totals,
                ]
            );


            // data sort
            $sort = [
                ['key' => 'i.id', 'value' => 'DESC']
            ];

            if (isset($tabItem['config']['sort']) && $tabItem['config']['sort']) {
                $sort = json_decode($tabItem['config']['sort'], true);
            } else {
                foreach ($defaultItems as $df) {
                    if (
                        $df['config']['schema'] === $tabItem['config']['schema'] &&
                        isset($df['config']['defaultSort']) &&
                        $df['config']['defaultSort']
                    ) {
                        $sort = json_decode($df['config']['defaultSort'], true);
                    }
                }
            }

            $quickSearch = [];
            if (isset($tabItem['config']['quickSearchFilterKeys']) && $tabItem['config']['quickSearchFilterKeys']) {
                $quickSearch = json_decode($tabItem['config']['quickSearchFilterKeys'], true);
            } else {
                foreach ($defaultItems as $df) {
                    if (
                        $df['config']['schema'] === $tabItem['config']['schema'] &&
                        isset($df['config']['defaultQuickSearch']) &&
                        $df['config']['defaultQuickSearch']
                    ) {
                        $quickSearch = json_decode($df['config']['defaultQuickSearch'], true);
                    }
                }
            }

            $filter = null;
            if (isset($tabItem['config']['predefinedFilter'])) {
                $filter = json_decode($tabItem['config']['predefinedFilter'], true);
            }
            $otherTabs = null;
            if (isset($tabItem['config']['tabGroup']) && $tabItem['config']['tabGroup']) {
                $otherTabs =
                    array_values(
                        array_map(
                            function ($t) {
                                return [
                                    'value' => $t['config']['type'],
                                    'label' => isset($t['config']['tabGroupTitle']) && $t['config']['tabGroupTitle'] ? $t['config']['tabGroupTitle'] : $t['config']['title']
                                ];
                            },
                            array_filter(
                                $tabItems,
                                function ($t) use ($tabItem) {
                                    return $t['config']['schema'] === $tabItem['config']['schema'] && $t['config']['tabGroup'] === $tabItem['config']['tabGroup'];
                                }
                            )
                        )
                    );
            }
            $exports = isset($tabItem['config']['exports']) ? $tabItem['config']['exports'] : [];



            $pageSize = isset($tabItem['config']['pageSize']) && $tabItem['config']['pageSize'] ? $tabItem['config']['pageSize'] : 20;

            $hasStatusFilter = false;
            $quickFilters = isset($tabItem['config']['quickFilters']) ? array_map(
                function ($item) use (&$hasStatusFilter) {
                    $item['property'] = $this->propertiesUtils->getPropertyForPath($item['path']);
                    $item['type'] = $this->propertiesUtils->getPropertyNaeType($item['property'], []);

                    $pathA = explode(".", $item['path']);
                    $pathA[0] = 'i';
                    $item['path'] = implode(".", $pathA);

                    if ($item['type'] === 'status') {
                        $hasStatusFilter = true;
                    }

                    if ($item['type'] === 'object') {
                        $item['sort'] = $this->defaultsService->getSortForSchema($item['property']['format']);
                        $item['sortStr'] = json_encode($item['sort']);
                    }
                    if (!isset($item['sortStr']) || !$item['sortStr']) {
                        $item['sortStr'] = json_encode([]);
                    }

                    return $item;
                },
                $tabItem['config']['quickFilters']
            ) : null;


            if (isset($tabItem['config']['generateForRel']) && $tabItem['config']['generateForRel']) {
                $relProperties = $this->propertiesUtils->getArraySchemasForTarget(
                    $tabItem['config']['schema']
                );
                foreach ($relProperties as $relProperty) {
                    $mapped = null;
                    if (isset($relProperty['additionalProperties'])) {
                        foreach ($relProperty['additionalProperties'] as $adProp) {
                            if (isset($adProp['mapped'])) {
                                $mapped = $adProp['mapped'];
                            }
                        }
                    }
                    if (!$mapped) {
                        continue;
                    }

                    $dataSourceRelCompName = Utils::fixComponentName(
                        ucfirst($tabItem['config']['schema']) .
                            ucfirst($tabItem['config']['type']) .
                            'TableDataSourceBy' .
                            ucfirst($relProperty['schema'])
                    );

                    $relFilter = [
                        'i.' . $mapped,
                        '=',
                        'props.relId',
                        true
                    ];

                    // $dataSourceFileName = $dataSourceRelGeneratedPath . '/' . $dataSourceRelCompName . '.tsx';
                    // $generatedContent = $tableDataSourceRelTemplate->render(
                    //     [
                    //         'tpCompName' => $dataSourceRelCompName,
                    //         'tpTableCompName' => $compName,
                    //         'schema' => $tabItem['config']['schema'],
                    //         'schemaUC' => Utils::fixComponentName($tabItem['config']['schema']),
                    //         'schemaTitle' => $this->entitiesUtils->getTitlePluralBySlug($tabItem['config']['schema']),
                    //         'type' => $tabItem['config']['type'],
                    //         'pageSize' => $pageSize,
                    //         'sort' => json_encode($sort),
                    //         'filter' => $filter ? json_encode($filter) : 'null',
                    //         'quickSearch' => json_encode($quickSearch),
                    //         'creatable' => isset($tabItem['config']['disableCreate']) && $tabItem['config']['disableCreate'] ? 'false' : 'true',
                    //         'otherTabs' => $otherTabs && count($otherTabs) > 0 ? json_encode($otherTabs, JSON_UNESCAPED_UNICODE) : 'null',
                    //         'exports' => $exports && count($exports) > 0 ? json_encode($exports, JSON_UNESCAPED_UNICODE) : 'null',
                    //         'relFilter' => str_replace('"props.relId"', 'props.relId', json_encode($relFilter)),
                    //         'mappedField' => $mapped,
                    //         'relSchema' => $relProperty['schema'],

                    //         'quickFilters' => $quickFilters && count($quickFilters) > 0 ? $quickFilters : 'null',

                    //         'totals' => $totals && count($totals) > 0 ? json_encode($totals, JSON_UNESCAPED_UNICODE) : 'null',

                    //         'customComponentOptions' => str_replace('"props.relId"', 'props.relId', json_encode(
                    //             [
                    //                 'relSchema' => $relProperty['schema'],
                    //                 'relId' => "props.relId"
                    //             ]
                    //         ))
                    //     ]
                    // );

                    // Utils::writeOnChanges($dataSourceFileName, $generatedContent);
                }
            } else {
                $customToolbarStart = file_exists(
                    $dataSourceCustomGeneratedPath . '/' . $dataSourceCompName . 'ToolbarStartContent.tsx'
                );
                $customToolbarEnd = file_exists(
                    $dataSourceCustomGeneratedPath . '/' . $dataSourceCompName . 'ToolbarEndContent.tsx'
                );
                $customToolbarMiddle = file_exists(
                    $dataSourceCustomGeneratedPath . '/' . $dataSourceCompName . 'ToolbarMiddleContent.tsx'
                );

                $tabTitle = $this->entitiesUtils->getTitlePluralBySlug($tabItem['config']['schema']);
                if (isset($tabItem['config']['title']) && $tabItem['config']['title']) {
                    $tabTitle = $tabItem['config']['title'];
                }

                $dataSourceFileName = $dataSourceGeneratedPath . '/' . $dataSourceCompName . '.tsx';
                $generatedContent = $tableDataSourceTemplate->render(
                    [
                        'tpCompName' => $dataSourceCompName,
                        'tpTableCompName' => $compName,
                        'schema' => $tabItem['config']['schema'],
                        'schemaUC' => Utils::fixComponentName($tabItem['config']['schema']),
                        'type' => $tabItem['config']['type'],
                        'pageSize' => $pageSize,
                        'sort' => json_encode($sort),
                        'filter' => $filter ? json_encode($filter) : 'null',
                        'quickSearch' => json_encode($quickSearch),
                        'creatable' => isset($tabItem['config']['disableCreate']) && $tabItem['config']['disableCreate'] ? 'false' : 'true',
                        'otherTabs' => $otherTabs && count($otherTabs) > 0 ? json_encode($otherTabs, JSON_UNESCAPED_UNICODE) : 'null',

                        'toolbarTitle' => $tabTitle,

                        'customToolbarStart' => $customToolbarStart,
                        'customToolbarEnd' => $customToolbarEnd,
                        'customToolbarMiddle' => $customToolbarMiddle,

                        'exports' => $exports && count($exports) > 0 ? json_encode($exports, JSON_UNESCAPED_UNICODE) : 'null',

                        'totals' => $totals && count($totals) > 0 ? json_encode($totals, JSON_UNESCAPED_UNICODE) : 'null',

                        'quickFilters' => $quickFilters && count($quickFilters) > 0 ? $quickFilters : 'null',
                        'hasStatusFilter' => $hasStatusFilter,
                    ]
                );
                Utils::writeOnChanges($dataSourceFileName, $generatedContent);

                $tablesDataSourceComponents[] = [
                    'compName' => $dataSourceCompName,
                    'type' => $tabItem['config']['type'],
                    'schema' => $tabItem['config']['schema'],
                ];
            }
        }

        $customEfFunctionTemplateMap->writeToFileOnChanges(
            Utils::customFolderPath('tabs').'/CustomListComponentsMap.ts',
            ['templates' => array_values($customComponents),]
        );

        // DefaultSearchToolbar
        $generatedPath = Utils::generatedPath('tabs');
        $defaultSearchToolbarFile = $generatedPath . '/DefaultSearchToolbar.tsx';
        $contents = $defaultSearchToolbarTemplate->render([]);
        Utils::writeOnChanges($defaultSearchToolbarFile, $contents);

        // TABLES DATA SOURCE
        $generatedPath = Utils::generatedPath('tabs');
        $defaultSearchToolbarFile = $generatedPath . '/TablesDataSource.tsx';
        $contents = $tablesDataSourceTemplate->render(['components' => $tablesDataSourceComponents]);
        Utils::writeOnChanges($defaultSearchToolbarFile, $contents);

        return Command::SUCCESS;
    }
}
