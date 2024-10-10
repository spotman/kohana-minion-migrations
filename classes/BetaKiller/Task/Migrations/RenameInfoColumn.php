<?php

namespace BetaKiller\Task\Migrations;

use BetaKiller\Console\ConsoleHelper;
use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;
use Database;
use DB;
use Kohana;

/**
 * Creates a new migration file
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Denis Terekhov (https://github.com/spotman)
 * @copyright  (c) 2016-2017 Spotman
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class RenameInfoColumn extends AbstractTask
{
    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            // No options here
        ];
    }

    public function run(ConsoleInputInterface $params): void
    {
        $config = Kohana::$config->load('migrations')
            ->as_array();

        $table = $config['table'];

        DB::query(Database::SELECT, "ALTER TABLE `$table` CHANGE `info` `description` TEXT NOT NULL DEFAULT '' AFTER `name`;");

        ConsoleHelper::write('Done!');
    }
}
