<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InGeneratorRoutes extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorRoutes';

    protected PropertiesUtils $propertiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp',
        ]);

        $editRoutesWrapperTemplate = $twig->load('routes/edit-route-wrapper.html.twig');
        $editRoutesTemplate = $twig->load('routes/app-routes.html.twig');

        $appRouterTemplate = $twig->load('routes/app-router.html.twig');
        $routesWrapperTemplate = $twig->load('routes/routes-wrapper.html.twig');
        $userSpaceWrapperTemplate = $twig->load('routes/user-space-wrapper.html.twig');
        $customLeftMenuTemplate = $twig->load('routes/custom-left-menu.html.twig');

        $customEmptyTemplate = $twig->load('common/empty.html.twig');

        $generatedRoutesWrappersPath = Utils::generatedPath('routes/wrappers');
        $generatedRoutesPath = Utils::generatedPath('routes');

        $customMenuPath = Utils::customFolderPath('menu');

        $tabsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/tabs.json';
        $tabItems = [];
        if (file_exists($tabsFile)) {
            $tabItems = json_decode(
                file_get_contents($tabsFile),
                true
            );
        }
        $editsFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/edit.json';
        $editItems = [];
        if (file_exists($editsFile)) {
            $editItems = json_decode(
                file_get_contents($editsFile),
                true
            );
        }

        $listComponents = [];

        $imports = [];

        foreach ($tabItems as $tabItem) {
            if (isset($tabItem['config']['generateForRel']) && $tabItem['config']['generateForRel']) {
                continue;
            }
            $dataSourceCompName = Utils::fixComponentName(
                ucfirst($tabItem['config']['schema']) .
                ucfirst($tabItem['config']['type']) . 'TableDataSource'
            );
            $imports[] = 'import ' . $dataSourceCompName . ' from "../tabs/tables-data-source/' . $dataSourceCompName . '"';

            $listComponents[] = [
                'schema' => $tabItem['config']['schema'],
                'type' => $tabItem['config']['type'],
                'compName' => $dataSourceCompName
            ];
        }

        $editComponents = [];
        foreach ($editItems as $editItem) {
            $compNameDataSource = Utils::fixComponentName(
                ucfirst($editItem['config']['schema']) .
                ucfirst($editItem['config']['type']) . 'FormDataSource'
            );
            $imports[] = 'import ' . $compNameDataSource . ' from "../editforms/forms-data-source/' . $compNameDataSource . '"';

            $editComponents[] = [
                'schema' => $editItem['config']['schema'],
                'type' => $editItem['config']['type'],
                'compName' => $compNameDataSource
            ];
        }

        $appsComponents = [];
        if (class_exists('App\Entity\Bookmark')) {
            $imports[] = 'import BookmarksPage from "../apps/bookmarks/BookmarksPage";';
            $appsComponents[] = [
                'name' => 'bookmarks',
                'compName' => 'BookmarksPage'
            ];
        }
        if (class_exists('App\Entity\Task')) {
            $imports[] = 'import TasksPage from "../apps/tasks/TasksPage";';
            $appsComponents[] = [
                'name' => 'tasks',
                'compName' => 'TasksPage'
            ];
        }
        if (class_exists('App\Entity\Note')) {
            $imports[] = 'import NotesPage from "../apps/notes/NotesPage";';
            $appsComponents[] = [
                'name' => 'notes',
                'compName' => 'NotesPage'
            ];
        }
         if (class_exists('App\Entity\FollowUp')) {
            $imports[] = 'import FollowUpPage from "../apps/follow-up/FollowUpPage";';
            $appsComponents[] = [
                'name' => 'follow-up',
                'compName' => 'FollowUpPage'
            ];
        }

        $generatedContent = $editRoutesTemplate->render(
            [
                'imports' => $imports,
                'listComponents' => $listComponents,
                'editComponents' => $editComponents,
                'appsComponents' => $appsComponents,
            ]
        );
        $fileName = $generatedRoutesPath . '/AppRoutes.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // EDIT ROW WRAPPER
        $generatedContent = $editRoutesWrapperTemplate->render();
        $fileName = $generatedRoutesWrappersPath . '/DefaultEditRouteWrapper.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // AppRouter
        $generatedContent = $appRouterTemplate->render();
        $fileName = $generatedRoutesWrappersPath . '/AppRouter.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // RoutesWrapper
        $generatedContent = $routesWrapperTemplate->render();
        $fileName = $generatedRoutesWrappersPath . '/RoutesWrapper.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // UserSpaceWrapper
        $generatedContent = $userSpaceWrapperTemplate->render();
        $fileName = $generatedRoutesWrappersPath . '/UserSpaceWrapper.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // Custom left menu
        $customMenuFileName = $customMenuPath . '/LeftMenu.tsx';
        if (!file_exists($customMenuFileName)) {
            $generatedContent = $customLeftMenuTemplate->render();
            Utils::writeOnChanges($customMenuFileName, $generatedContent);
        }

        // UserSpaceWrapper TOOLBAR

        $userSpaceWrapperToolbarTemplate = $twig->load('layout/user-space-wrapper-toolbar.html.twig');
        $generatedContent = $userSpaceWrapperToolbarTemplate->render();
        $fileName = Utils::generatedPath('layout/toolbar') . '/UserSpaceWrapperToolbar.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // Custom toolbar
        $customLayoutPath = Utils::customFolderPath('layout');

        $customFileName = $customLayoutPath . '/CustomToolbarBefore.tsx';
        if (!file_exists($customFileName)) {
            $generatedContent = $customEmptyTemplate->render(['compName' => 'CustomToolbarBefore']);
            Utils::writeOnChanges($customFileName, $generatedContent);
        }
        $customFileName = $customLayoutPath . '/CustomToolbarAfter.tsx';
        if (!file_exists($customFileName)) {
            $generatedContent = $customEmptyTemplate->render(['compName' => 'CustomToolbarAfter']);
            Utils::writeOnChanges($customFileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}