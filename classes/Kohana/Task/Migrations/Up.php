<?php

use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;

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
class Kohana_Task_Migrations_Up extends AbstractTask
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
        $available_migrations = Migrations_Helper::get_available_migrations();

        $to = $params->has(self::ARG_TO)
            ? $params->getInt(self::ARG_TO)
            : null;

        foreach ($available_migrations as $migration) {
            if ($to === null or $migration['id'] <= $to) {
                try {
                    $result = Migrations_Helper::apply($migration['filename'], Migrations_Helper::DIRECTION_UP, $this);

                    if ($result) {
                        Minion_CLI::write('Migration '.$migration['filename'].' applied'.PHP_EOL);
                    }
                } catch (Kohana_Minion_Exception $e) {
                    Minion_CLI::write($e->getMessage());
                    Minion_CLI::write('Halted!');
                    exit(1);
                }
            }
        }
    }
}
