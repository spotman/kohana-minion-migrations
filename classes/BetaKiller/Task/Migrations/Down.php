<?php

namespace BetaKiller\Task\Migrations;

use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;
use Kohana;
use Kohana_Minion_Exception;
use BetaKiller\Migration\MigrationHelper;
use Minion_CLI;

/**
 * Applies migrations
 *
 * It can accept the following options:
 *  --to: Id of migration, to which you want to rolled back.
 *       In other words, ID migration, to which you want to roll back, but not including!
 *       (see migrations:history)
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Down extends AbstractTask
{
    private const ARG_TO    = 'to';
    private const ARG_LIMIT = 'limit';

    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            $builder->int(self::ARG_TO)->optional()->label('Migration ID'),
            $builder->int(self::ARG_LIMIT)->optional(),
        ];
    }

    public function run(ConsoleInputInterface $params): void
    {
        $applied_migrations = \DB::select('filename')
            ->from(Kohana::$config->load('migrations')->table);

        if ($params->has(self::ARG_TO)) {
            $applied_migrations->where('id', '>', $params->getInt(self::ARG_TO));
        }

        if ($params->has(self::ARG_LIMIT)) {
            $applied_migrations->limit($params->getInt(self::ARG_LIMIT));
        }

        $applied_migrations = $applied_migrations->order_by('id', 'DESC')
            ->execute()
            ->as_array();

        foreach ($applied_migrations as $migration) {
            try {
                $result = \BetaKiller\Migration\MigrationHelper::apply($migration['filename'], MigrationHelper::DIRECTION_DOWN, $this);

                if ($result) {
                    Minion_CLI::write('Migration '.$migration['filename'].' rolled back');
                }
            } catch (Kohana_Minion_Exception $e) {
                Minion_CLI::write($e->getMessage());
                Minion_CLI::write('Halted!');
                exit(1);
            }
        }

        Minion_CLI::write('Done!');
    }
}
