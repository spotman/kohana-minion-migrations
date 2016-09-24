<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Migrations abstract class
 *
 * @package    Minion/Migrations
 * @category   Helpers
 * @author     Leemo Studio
 * @author     Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license    BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
abstract class Kohana_Migration {

	/**
	 * Returns migration ID
	 *
	 * @return integer
	 */
	abstract public function id();

	/**
	 * Returns migtation name
	 *
	 * @return string
	 */
	abstract public function name();

	/**
	 * Returns migtation description
	 *
	 * @return string
	 */
	abstract public function description();

	/**
	 * Takes a migration
	 *
	 * @return void
	 */
	abstract public function up();

	/**
	 * Removes migration
	 *
	 * @return void
	 */
	abstract public function down();

    /**
     * This method will be executed before up migration starts
     * You may put additional checks here
     */
    public function before_up()
    {
        // Nothing by default
    }

    /**
     * This method will be executed before down migration starts
     * You may put additional checks here
     */
    public function before_down()
    {
        // Nothing by default
    }

    /**
     * This method will be executed after up migration ends
     * You may put here finalizing/cleanup statements
     */
    public function after_up()
    {
        // Nothing by default
    }

    /**
     * This method will be executed after down migration ends
     * You may put here finalizing/cleanup statements
     */
    public function after_down()
    {
        // Nothing by default
    }

} // End Kohana_Migration
