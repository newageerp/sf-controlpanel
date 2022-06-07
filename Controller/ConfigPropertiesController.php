<?php

namespace Newageerp\SfControlpanel\Controller;

use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(path="/app/nae-core/config-properties")
 */
class ConfigPropertiesController extends ConfigBaseController
{
    /**
     * @Route(path="/for-filter", methods={"POST"})
     */
    public function getPropertiesForFilter(Request $request, PropertiesUtils $propertiesUtils)
    {
        $request = $this->transformJsonBody($request);

        $schema = $request->get('schema');

        $schemaProperties = $this->schemaPropetiesForFilter($schema, $propertiesUtils);

        $rels = $this->relPropertiesForFilter($schema, $propertiesUtils);
        foreach ($rels as $relProperty) {
            $relSchemaProperties = $this->schemaPropetiesForSort($relProperty['format'], $propertiesUtils);
            foreach ($relSchemaProperties as $relSchemaProperty) {
                $key = explode(".", $relSchemaProperty['value']);
                $title = $relSchemaProperty['label'];
                $schemaProperties[] = [
                    'value' => 'i.' . $relProperty['key'] . '.' . $key[1],
                    'label' => $title,
                    'group' => $relProperty['title'],
                ];
            }
        }


        return $this->json(['data' => array_values($schemaProperties)]);
    }

    /**
     * @Route(path="/for-sort", methods={"POST"})
     */
    public function getPropertiesForSort(Request $request, PropertiesUtils $propertiesUtils)
    {
        $request = $this->transformJsonBody($request);

        $schema = $request->get('schema');

        $schemaProperties = $this->schemaPropetiesForSort($schema, $propertiesUtils);

        $rels = $this->relPropertiesForSort($schema, $propertiesUtils);
        foreach ($rels as $relProperty) {
            $relSchemaProperties = $this->schemaPropetiesForSort($relProperty['format'], $propertiesUtils);
            foreach ($relSchemaProperties as $relSchemaProperty) {
                $key = explode(".", $relSchemaProperty['value']);
                $title = $relProperty['title'] . ' -> ' . $relSchemaProperty['label'];
                $schemaProperties[] = [
                    'value' => 'i.' . $relProperty['key'] . '.' . $key[1],
                    'label' => $title,
                ];
            }
        }


        return $this->json(['data' => array_values($schemaProperties)]);
    }

    protected function schemaPropetiesForSort(string $schema, PropertiesUtils $propertiesUtils)
    {
        $schemaProperties = array_filter(
            $propertiesUtils->getProperties(),
            function ($property) use ($schema) {
                return ($property['schema'] === $schema &&
                    $property['key'] !== 'id' &&
                    isset($property['isDb']) &&
                    $property['isDb'] &&
                    isset($property['available']) &&
                    $property['available']['sort'] &&
                    $property['type'] !== 'rel' &&
                    $property['type'] !== 'array'
                );
            }
        );
        $hasId = count(array_filter(
                $schemaProperties,
                function ($property) {
                    return $property['key'] === 'id';
                }
            )) > 0;
        if (!$hasId) {
            array_unshift(
                $schemaProperties,
                [
                    'key' => 'id',
                    'title' => 'ID'
                ]
            );
        }
        $schemaProperties = array_map(
            function ($property) {
                return [
                    'value' => 'i.' . $property['key'],
                    'label' => $property['title'],
                ];
            },
            $schemaProperties
        );
        return $schemaProperties;
    }

    protected function schemaPropetiesForFilter(string $schema, PropertiesUtils $propertiesUtils)
    {
        $schemaProperties = array_filter(
            $propertiesUtils->getProperties(),
            function ($property) use ($schema) {
                return ($property['schema'] === $schema &&
                    $property['key'] !== 'id' &&
                    isset($property['isDb']) &&
                    $property['isDb'] &&
                    isset($property['available']) &&
                    $property['available']['filter'] &&
                    $property['type'] !== 'rel' &&
                    $property['type'] !== 'array'
                );
            }
        );

        $schemaProperties = array_map(
            function ($property) {
                return [
                    'value' => 'i.' . $property['key'],
                    'label' => $property['title'],
                ];
            },
            $schemaProperties
        );
        return $schemaProperties;
    }

    protected function relPropertiesForSort(string $schema, PropertiesUtils $propertiesUtils)
    {
        $schemaProperties = array_filter(
            $propertiesUtils->getProperties(),
            function ($property) use ($schema) {
                return ($property['schema'] === $schema &&
                    isset($property['available']) &&
                    $property['available']['sort'] &&
                    $property['type'] == 'rel'
                );
            }
        );

        return $schemaProperties;
    }

    protected function relPropertiesForFilter(string $schema, PropertiesUtils $propertiesUtils)
    {
        $schemaProperties = array_filter(
            $propertiesUtils->getProperties(),
            function ($property) use ($schema) {
                return ($property['schema'] === $schema &&
                    isset($property['available']) &&
                    $property['available']['filter'] &&
                    $property['type'] == 'rel'
                );
            }
        );

        return $schemaProperties;
    }
}
