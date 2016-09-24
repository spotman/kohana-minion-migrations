<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Creates a new migration file
 *
 * @package    Minion/Migrations
 * @category   Helpers
 * @author     Denis Terekhov (https://github.com/spotman)
 * @copyright  (c) 2016-2017 Spotman
 * @license    BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Kohana_Task_Migrations_RenameInfoColumn extends Minion_Task {

	protected function _execute(array $params)
	{
        $config = Kohana::$config->load('migrations')
            ->as_array();

        $table = $config['table'];

        DB::query(Database::SELECT, "ALTER TABLE `$table` CHANGE `info` `description` TEXT NOT NULL DEFAULT '' AFTER `name`;");

		Minion_CLI::write('Done!');
	}

} // End Kohana_Task_Migrations_RenameInfoColumn
