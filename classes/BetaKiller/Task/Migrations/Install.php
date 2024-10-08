<?php

namespace BetaKiller\Task\Migrations;

use BetaKiller\Console\ConsoleException;
use BetaKiller\Console\ConsoleHelper;
use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;
use Database;
use Kohana;
use Throwable;

/**
 * Install migrations service
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Install extends AbstractTask
{
    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            // No options here
        ];
    }

    public function run(ConsoleInputInterface $params): void
    {
        $db = Database::instance();

        $table_prefix  = $db->table_prefix();
        $instance_name = (string)$db;

        $connection_type = Kohana::$config
            ->load('database.'.$instance_name.'.type');

        $install_file = Kohana::find_file('schemas', 'migrations/'.$connection_type.'/install', 'sql');

        if (!is_file($install_file)) {
            throw new ConsoleException('File schemas/migrations/'.$connection_type.'/install.sql doesn\'t exist');
        }

        $query = file_get_contents($install_file);

        try {
            Database::instance()
                ->query(null, str_replace(':prefix_', $table_prefix, $query));
        } catch (Throwable $e) {
            throw new ConsoleException($e->getMessage());
        }

        ConsoleHelper::write('Migrations service successfully installed');
    }
}
