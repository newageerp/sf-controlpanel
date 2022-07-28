<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\EntitiesUtils;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Newageerp\SfControlpanel\Console\PropertiesUtils;
use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InGeneratorLayout extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorLayout';

    protected PropertiesUtils $propertiesUtils;
    protected EntitiesUtils $entitiesUtils;

    public function __construct(PropertiesUtils $propertiesUtils, EntitiesUtils $entitiesUtils)
    {
        parent::__construct();

        $this->propertiesUtils = $propertiesUtils;
        $this->entitiesUtils = $entitiesUtils;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => '/tmp/smarty',
        ]);

        $widgetsTemplate = $twig->load('layout/generated-widgets.html.twig');
        $widgetComponents = [];

        // view top
        $viewTopTemplate = $twig->load('layout/view-top.html.twig');
        $generatedContent = $viewTopTemplate->render();
        $fileName = Utils::generatedPath('layout/view') . '/ViewTop.tsx';
        Utils::writeOnChanges($fileName, $generatedContent);

        // toolbar layout rels create
        $relsCreateFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/rels-create.json';
        if (file_exists($relsCreateFile)) {
            $relsList = json_decode(file_get_contents($relsCreateFile), true);
            $rels = [];
            foreach ($relsList as $relItem) {
                if (!isset($rels[$relItem['source']])) {
                    $rels[$relItem['source']] = [];
                }
                if (!isset($relItem['targetTitle'])) {
                    $relItem['targetTitle'] = $this->entitiesUtils->getTitleBySlug($relItem['target']);
                }
                if (!isset($relItem['type'])) {
                    $relItem['type'] = 'main';
                }
                if (!isset($relItem['forcePopup'])) {
                    $relItem['forcePopup'] = false;
                }
                $rels[$relItem['source']][] = $relItem;
            }

            $toolbarItemTemplate = $twig->load('layout/toolbar-items-rel-create.html.twig');
            foreach ($rels as $source => $items) {
                $compName = Utils::fixComponentName($source) . 'RelCreate';
                $fileName = Utils::generatedPath('layout/view/toolbar-items') . '/' . $compName . '.tsx';

                $widgetComponents[$source] = $compName;

                $generatedContent = $toolbarItemTemplate->render(['compName' => $compName, 'items' => $items, 'schema' => $source]);
                Utils::writeOnChanges($fileName, $generatedContent);
            }
        }

        // toolbar layout rels copy
        $relsCreateFile = $_ENV['NAE_SFS_CP_STORAGE_PATH'] . '/rels-copy.json';
        if (file_exists($relsCreateFile)) {
            $relsList = json_decode(file_get_contents($relsCreateFile), true);
            $rels = [];
            foreach ($relsList as $relItem) {
                if (!isset($rels[$relItem['source']])) {
                    $rels[$relItem['source']] = [];
                }
                if (!isset($relItem['targetTitle'])) {
                    $relItem['targetTitle'] = $this->entitiesUtils->getTitleBySlug($relItem['target']);
                }
                if (!isset($relItem['type'])) {
                    $relItem['type'] = 'main';
                }
                if (!isset($relItem['forcePopup'])) {
                    $relItem['forcePopup'] = false;
                }
                $rels[$relItem['source']][] = $relItem;
            }

            $toolbarItemTemplate = $twig->load('layout/toolbar-items-rel-copy.html.twig');
            foreach ($rels as $source => $items) {
                $compName = Utils::fixComponentName($source) . 'RelCopy';
                $fileName = Utils::generatedPath('layout/view/toolbar-items') . '/' . $compName . '.tsx';

                $widgetComponents[$source] = $compName;

                $generatedContent = $toolbarItemTemplate->render(['compName' => $compName, 'items' => $items, 'schema' => $source]);
                Utils::writeOnChanges($fileName, $generatedContent);
            }
        }


        // WIDGETS

        $fileName = Utils::generatedPath('layout') . '/GeneratedLayoutWidgets.tsx';
        $generatedContent = $widgetsTemplate->render(
            [
                'components' => $widgetComponents
            ]
        );
        Utils::writeOnChanges($fileName, $generatedContent);

        $templates = [
            'layout/apps/bookmarks/BookmarksPage.html.twig' => ['apps/bookmarks', 'BookmarksPage'],
            'layout/apps/eventshistory/EventsHistoryWidget.html.twig' => ['apps/eventshistory', 'EventsHistoryWidget'],
            'layout/apps/follow-up/FollowUpPage.html.twig' => ['apps/follow-up', 'FollowUpPage'],
            'layout/apps/mails/MailsContent.html.twig' => ['apps/mails', 'MailsContent'],

            'layout/apps/notes/NoteContentForm.html.twig' => ['apps/notes', 'NoteContentForm'],
            'layout/apps/notes/NoteEditForm.html.twig' => ['apps/notes', 'NoteEditForm'],
            'layout/apps/notes/NoteLine.html.twig' => ['apps/notes', 'NoteLine'],
            'layout/apps/notes/NotesContent.html.twig' => ['apps/notes', 'NotesContent'],
            'layout/apps/notes/NotesContentMembers.html.twig' => ['apps/notes', 'NotesContentMembers'],
            'layout/apps/notes/NotesPage.html.twig' => ['apps/notes', 'NotesPage'],

            'layout/apps/tasks/TasksPage.html.twig' => ['apps/tasks', 'TasksPage'],
            'layout/apps/tasks/TasksWidget.html.twig' => ['apps/tasks', 'TasksWidget'],

            'layout/auth/AuthLogin.html.twig' => ['auth', 'AuthLogin'],

            'layout/main/App.html.twig' => ['main', 'App'],
//            'layout/main/AppRouting.html.twig' => ['main', 'AppRouting'],
            'layout/main/InitComponent.html.twig' => ['main', 'InitComponent'],
        ];

        $hasTasksApp = class_exists('App\Entity\Task');

        foreach ($templates as $template => $target) {
            $fileName = Utils::generatedPath($target[0]) . '/'.$target[1].'.tsx';
            $generatedContent = $twig->load($template)->render(['hasTasksApp' => $hasTasksApp]);
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        $templates = [
            'config/fields/edit.html.twig' => ['fields', 'edit'],
            'config/fields/fieldDependencies.html.twig' => ['fields', 'fieldDependencies'],
            'config/fields/onEditElementUpdate.html.twig' => ['fields', 'onEditElementUpdate'],

            'config/fields/view.html.twig' => ['fields', 'view'],

            'config/tabs/index.html.twig' => ['tabs', 'index'],

            'config/widgets/widgets/base-entity.widgets.html.twig' => ['widgets/widgets', 'base-entity.widgets'],
            'config/widgets/index.html.twig' => ['widgets', 'index'],

            'config/lang/i18.html.twig' => ['lang', 'i18'],
        ];

        foreach ($templates as $template => $target) {
            $ext = 'tsx';
            $fileName = Utils::generatedConfigPath($target[0]) . '/'.$target[1].'.'.$ext;
            if (!file_exists($fileName)) {
                $generatedContent = $twig->load($template)->render();
                Utils::writeOnChanges($fileName, $generatedContent);
            }
        }

        // getFrontendModelsCachePath
        $fileName = LocalConfigUtils::getFrontendModelsCachePath().'/NotesNameResolver.tsx';
        if (!file_exists($fileName)) {
            $generatedContent = $twig->load('user-components/notes/NotesNameResolver.html.twig')->render();
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        $fileName = LocalConfigUtils::getFrontendModelsCachePath().'/types.ts';
        if (!file_exists($fileName)) {
            $generatedContent = $twig->load('user-components/types.html.twig')->render();
            Utils::writeOnChanges($fileName, $generatedContent);
        }


        return Command::SUCCESS;
    }
}