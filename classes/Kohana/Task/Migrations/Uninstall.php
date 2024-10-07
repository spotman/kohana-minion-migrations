<?php

use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;

/**
 * Uninstall migrations service
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Kohana_Task_Migrations_Uninstall extends AbstractTask
{
    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            // No options here
        ];
    }

    public function run(ConsoleInputInterface $params): void
    {
        Minion_CLI::write('You\'re going to uninstall the migrations service. All migrations data will be lost.');
        $sure = Minion_CLI::read('If you are sure, type fully "yes"');

        if ($sure !== 'yes') {
            Minion_CLI::write('Uninstalling canceled');

            return;
        }

        $db = Database::instance();

        $table_prefix  = $db->table_prefix();
        $instance_name = (string)$db;

        $connection_type = Kohana::$config
            ->load('database.'.$instance_name.'.type');

        $uninstall_file = Kohana::find_file('schemas', 'migrations/'.$connection_type.'/uninstall', 'sql');

        if (!is_file($uninstall_file)) {
            throw new Kohana_Minion_Exception('File schemas/migrations/'.$connection_type.'/uninstall.sql doesn\'t exist');
        }

        $query = file_get_contents($uninstall_file);

        try {
            Database::instance()
                ->query(null, str_replace(':prefix_', $table_prefix, $query));
        } catch (Throwable $e) {
            throw new Kohana_Minion_Exception($e->getMessage());
        }

        Minion_CLI::write('Migrations service successfully uninstalled');
    }
}
