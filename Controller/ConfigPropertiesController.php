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
     * @Route(path="/for-sort", methods={"POST"})
     */
    public function getPropertiesForSort(Request $request, PropertiesUtils $propertiesUtils)
    {
        $request = $this->transformJsonBody($request);

        $schema = $request->get('schema');

        $schemaProperties = array_filter(
            $propertiesUtils->getProperties(),
            function ($property) use ($schema) {
                return $property['schema'] === $schema && $property['isDb'] && !!$property['title'];
            }
        );
        $hasId = count(array_filter(
                $schemaProperties,
                function ($property) {
                    return $property['key'] === 'id';
                }
            )) > 0;
        if (!$hasId) {
            $schemaProperties[] = [
                'key' => 'id',
                'label' => 'ID'
            ];
        }
        $schemaProperties = array_map(
            function ($property) {
                return [
                    'key' => 'i.' . $property['key'],
                    'label' => $property['title'],
                ];
            },
            $schemaProperties
        );

        return $this->json(['data' => $schemaProperties]);
    }
}