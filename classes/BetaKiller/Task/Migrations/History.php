<?php

namespace BetaKiller\Task\Migrations;

use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;
use DateTimeImmutable;
use DB;
use Kohana;
use BetaKiller\Migration\MigrationHelper;
use Minion_CLI;

/**
 * Show migrations history
 *
 * It can accept the following options:
 *  --from:  From what date to show the history (not required)
 *  --limit: Number of the latter parameters shown (not required, default=10)
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class History extends AbstractTask
{
    private const ARG_FROM  = 'from';
    private const ARG_LIMIT = 'limit';

    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            $builder->string(self::ARG_FROM)->optional()->label('Start date'),
            $builder->int(self::ARG_LIMIT)->optional()->label('Limit'),
        ];
    }

    /**
     * @param \BetaKiller\Console\ConsoleInputInterface $params
     *
     * @return void
     */
    public function run(ConsoleInputInterface $params): void
    {
        $history = DB::select('id', 'date', 'name', 'description')
            ->from(Kohana::$config->load('migrations')->table);

        $legend = '';

        if ($params->has(self::ARG_LIMIT)) {
            $limit = $params->getInt(self::ARG_LIMIT);

            $history->limit($limit);
            $legend = 'Last '.$limit.' migrations';
        }

        if ($params->has(self::ARG_FROM)) {
            if (empty($legend)) {
                $legend = 'Migrations';
            }

            $date = date('Y-m-d H:i:s', strtotime($params->getInt(self::ARG_FROM)));

            $history->where('date', '>=', $date);

            $legend .= ' from '.$date;
        }

        Minion_CLI::write($legend.':');

        /** @var \Database_Result $result */
        $result = $history->order_by('id', 'DESC')->execute();

        $history = $result->as_array();

        if (sizeof($history) == 0) {
            Minion_CLI::write('Nothing found');

            return;
        }

        $columns = [
            'id',
            'date',
            'name',
            'description',
        ];

        Minion_CLI::write(MigrationHelper::table($history, $columns));
    }
}
