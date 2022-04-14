<?php

namespace Newageerp\SfControlpanel\Console\Out;

use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class OutLocalConfigBuildMockConsole extends Command
{
    protected static $defaultName = 'nae:localconfig:OutLocalConfigBuildMock';

    protected EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $entityDir = LocalConfigUtils::getPhpEntitiesPath();
        $mockControllerPath = LocalConfigUtils::getPhpControllerPath() . '/MockController.php';

        $entities = [];

        foreach ($finder->in($entityDir)->files() as $entity) {
            if (mb_strpos($entity->getRelativePathname(), 'php') !== false && mb_strpos($entity->getRelativePathname(), '/') === false) {
                $entities[] = str_replace('.php', '', $entity->getRelativePathname());
            }
        }

        $appPropertyT = implode(
            "\n",
            array_map(
                function ($e) {
                    return '     *         @OA\\Property(property="' . $e . '", ref=@Model(type=' . $e . '::class)),';
                },
                $entities
            )
        );
        $appEntityT = implode(
            "\n",
            array_map(
                function ($e) {
                    return 'use App\\Entity\\' . $e . ';';
                },
                $entities
            )
        );


        $template = '<?php
namespace App\\Controller;

use Symfony\\Bundle\\FrameworkBundle\\Controller\\AbstractController;
use OpenApi\\Annotations as OA;
use Symfony\\Component\\Routing\\Annotation\\Route;
use Symfony\\Component\\HttpFoundation\\JsonResponse;
use Nelmio\\ApiDocBundle\\Annotation\\Model;
' . $appEntityT . '
        
/**
 * Class MockController
 * @Route(path="/app/tmp")
 */
class MockController extends AbstractController
{
    /**
     * @Route ("/ping", methods={"GET"})
     * @return JsonResponse
     * @OA\\Get (operationId="pingtest")
     * @OA\\Response(
     *     response="200",
     *     description="ping",
     *     @OA\\Schema(type="object", 
' . $appPropertyT . '
     *     )
     * )
     */
     public function ping()
     {
         return $this->json([\'success\' => 1]);
     }
}
';

        file_put_contents(
            $mockControllerPath,
            $template,
        );

        return Command::SUCCESS;
    }
}
