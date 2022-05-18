<?php

namespace Newageerp\SfControlpanel\Console\In;

use Newageerp\SfControlpanel\Console\Utils;
use Newageerp\SfControlpanel\Service\MenuService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Newageerp\SfControlpanel\Console\LocalConfigUtils;
use Symfony\Component\Filesystem\Filesystem;

class InGeneratorStatuses extends Command
{
    protected static $defaultName = 'nae:localconfig:InGeneratorStatuses';

    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        parent::__construct();
        $this->menuService = $menuService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();

        $statusItemsTemplate = file_get_contents(
            __DIR__ . '/templates/statuses/StatusItems.txt'
        );

        $generatedPath = Utils::generatedPath('statuses/badges');

        $db = new \SQLite3($_ENV['NAE_SFS_CP_DB_PATH']);

        $sql = "SELECT 
                    statuses.id, statuses.text, statuses.entity, statuses.status, statuses.type, statuses.color, statuses.brightness,
                    entities.slug as entity_slug,
                    entities.slug || ' (' || entities.titleSingle || ')' as entity_title
                FROM statuses 
                left join entities on statuses.entity = entities.id
                WHERE 1 = 1 
                ";
        $result = $db->query($sql);

        $statuses = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!isset($statuses[$data['entity_slug']])) {
                $statuses[$data['entity_slug']] = [];
            }
            $statuses[$data['entity_slug']][] = $data;
        }

        foreach ($statuses as $slug => $entityStatuses) {
            $compName = Utils::fixComponentName(ucfirst($slug) . 'Statuses');
            $badges = [];
            $badgeVarNames = [];
            foreach ($entityStatuses as $entityStatus) {
                $statusName = Utils::fixComponentName(
                    ucfirst($slug) .
                    ucfirst($entityStatus['type']) .
                    'Badge' .
                    $entityStatus['status']
                );
                $badgeVarNames[] = 'if (status === ' . $entityStatus['status'] . ' and type === "' . $entityStatus['type'] . '") {:
                    return <' . $statusName . ' />;
                }
';
                $badges[] = "
    export const " . $statusName . " = () => {
        const { t } = useTranslation();
        
        return (
            <UI.Badges.Badge
              bgColor={'" . $entityStatus['color'] . "'}
              brightness={" . mb_substr($entityStatus['brightness'], 1) . "}
              size={'sm'}
              className={'w-56 float-right'}
            >
              {t('" . $entityStatus['text'] . "')}
            </UI.Badges.Badge>
        )
    };";
            }

            $tpBadgesStr = implode("\n", $badges);
            $tpBadgesExportStr = implode("\n", $badgeVarNames);

            $fileName = $generatedPath . '/' . $compName . '.tsx';
            $generatedContent = str_replace(
                [
                    'TP_BADGES_EXPORT',
                    'TP_BADGES',
                    'TP_COMP_NAME',
                ],
                [
                    $tpBadgesExportStr,
                    $tpBadgesStr,
                    $compName,
                ],
                $statusItemsTemplate
            );
            Utils::writeOnChanges($fileName, $generatedContent);
        }

        return Command::SUCCESS;
    }
}