<?php

namespace BetaKiller\Task\Migrations;

use BetaKiller\Console\ConsoleException;
use BetaKiller\Console\ConsoleHelper;
use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;
use BetaKiller\Migration\MigrationHelper;

/**
 * Applies migrations
 *
 * It can accept the following options:
 *  --to: ID of migration, to which you want to upgrade.
 *       In other words, the ID of the migration, you want to apply the latest!
 *       (see migrations:status)
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Up extends AbstractTask
{
    private const ARG_TO = 'to';

    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            $builder->int(self::ARG_TO)->optional()->label('Migration ID'),
        ];
    }

    public function run(ConsoleInputInterface $params): void
    {
        $available_migrations = \BetaKiller\Migration\MigrationHelper::get_available_migrations();

        $to = $params->has(self::ARG_TO)
            ? $params->getInt(self::ARG_TO)
            : null;

        foreach ($available_migrations as $migration) {
            if ($to === null or $migration['id'] <= $to) {
                try {
                    $result = MigrationHelper::apply($migration['filename'], MigrationHelper::DIRECTION_UP, $this);

                    if ($result) {
                        ConsoleHelper::write('Migration '.$migration['filename'].' applied'.PHP_EOL);
                    }
                } catch (ConsoleException $e) {
                    ConsoleHelper::write($e->getMessage());
                    ConsoleHelper::write('Halted!');
                    exit(1);
                }
            }
        }
    }
}
