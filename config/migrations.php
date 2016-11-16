<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	/**
	 * Directory to store migrations in APPPATH
	 *
	 * @var string
	 */
	'directory' => 'migrations',

	/**
	 * Migrations table
	 *
	 * @var string
	 */
	'table' => 'migrations',

    /**
     * Scopes with paths for creating migration files
     */
    'scopes'    =>  [
        'app'           =>  APPPATH,
        'app:module'    =>  MODPATH,
    ],
);
