<?php

namespace BetaKiller\Task\Migrations;

use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;
use BetaKiller\Migration\MigrationHelper;
use Minion_CLI;

/**
 * Show available to apply migrations
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Status extends AbstractTask
{
    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            // No options here
        ];
    }

    public function run(ConsoleInputInterface $params): void
    {
        $migrations = MigrationHelper::get_available_migrations();

        if (sizeof($migrations) == 0) {
            Minion_CLI::write('There is no available migrations');

            return;
        }

        $columns = [
            'id',
            'name',
            'description',
        ];

        Minion_CLI::write('Available migrations:');
        Minion_CLI::write(MigrationHelper::table($migrations, $columns));
    }
}