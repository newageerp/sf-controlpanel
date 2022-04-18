<?php

namespace Newageerp\SfControlpanel\Controller;

use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(path="/app/nae-core/config-cache")
 */
class ConfigCacheController extends ConfigBaseController
{
    
    /**
     * @Route(path="/getLocalConfig", methods={"GET"})
     * @OA\Post (operationId="NaeConfigLocalConfigList")
     */
    public function getLocalConfig(Request $request) {
        $request = $this->transformJsonBody($request);

        $config = [
            'widgets' => 'widgets.json',
            'builder' => 'builder.json',
            'tabs' => 'tabs.json',
            'defaults' => 'defaults.json',
            'edit' => 'edit.json',
            'view' => 'view.json',
        ];

        $output = ['data' => []];

        try {
            foreach ($config as $key => $file) {
                $data = json_decode(
                    file_get_contents($this->getLocalStorage().'/'.$file),
                    true
                );
                $output['data'][$key] = $data;
            }

        } catch (\Exception $e) {
            $output['e'] = $e->getMessage();
        }
        return $this->json($output);
    }
}