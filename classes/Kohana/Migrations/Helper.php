<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Migrations helper
 *
 * @package    Minion/Migrations
 * @category   Helpers
 * @author     Leemo Studio
 * @author     Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license    BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Kohana_Migrations_Helper {

	/**
	 * Migrations filename delimiter
	 *
	 * @var string
	 */
	const DELIMITER = '___';

	/**
	 * Returns migration class name from migration filename
	 *
	 * @param  string $filename
	 * @return string
	 */
	public static function filename_to_class($filename)
	{
		$filename = explode('___', $filename);

		$stamp    = $filename[0];
		$filename = str_replace(array('_', EXT), array(' ', (string) NULL), $filename[1]);

		return 'Migration'.$stamp.'_'.str_replace(' ', '_', UTF8::ucwords($filename));
	}

	/**
	 * Returns available migrations
	 *
	 * @return array
	 */
	public static function get_available_migrations()
	{
		$config = Kohana::$config->load('migrations')
			->as_array();

		$applied_migrations = DB::select('id', 'filename')
			->from($config['table'])
			->execute()
			->as_array('id', 'filename');

		$files = Kohana::list_files($config['directory']);

		$available_migrations = array();

		foreach (array_values($files) as $file)
		{
			$filename = pathinfo($file, PATHINFO_BASENAME);

			if ( ! in_array($filename, $applied_migrations))
			{
				require $file;

				$class = self::filename_to_class($filename);

				$migration = static::create_migration_instance($class);

				$available_migrations[$migration->id()] = array
				(
					'id'       => $migration->id(),
					'filename' => $filename,
					'name'     => $migration->name(),
					'description'     => $migration->description()
				);
			}
		}

		ksort($available_migrations);

		return $available_migrations;
	}

	/**
	 * Direction UP identifier
	 */
	const DIRECTION_UP   = 'up';

	/**
	 * Direction DOWN identifier
	 */
	const DIRECTION_DOWN = 'down';

	/**
	 * Apply or remove migration
	 *
	 * @param string $filename  migration filename
	 * @param string $direction direction
	 * @param Minion_Task $task
	 * @return bool
	 * @throws Kohana_Exception
	 * @throws Kohana_Minion_Exception
	 */
	public static function apply($filename, $direction, Minion_Task $task)
	{
		$config = Kohana::$config->load('migrations')
			->as_array();

		$class = self::filename_to_class($filename);

		if ( ! class_exists($class))
		{
			require Kohana::find_file($config['directory'], $filename, FALSE);
		}

		$instance = static::create_migration_instance($class);

		// Skip migrations without permission
		if (!static::check_file_permissions($instance, $task))
		{
            return FALSE;
        }

		try
		{
		    static::before_migration($instance, $direction);

		    $instance->$direction();

            static::after_migration($instance, $direction);
		}
		catch (Throwable $e)
		{
			throw new Kohana_Minion_Exception('Fatal error! '.$e->getMessage().' in '.$e->getFile().':'.$e->getFile());
		}
		catch (Exception $e)
		{
			throw new Kohana_Minion_Exception('Fatal error! '.$e->getMessage().' in '.$e->getFile().':'.$e->getFile());
		}

        if ($direction === self::DIRECTION_UP)
		{
			DB::insert($config['table'], array('id', 'date', 'name', 'filename', 'description'))
				->values(array(
					'id'            => $instance->id(),
					'date'          => date('Y-m-d H:i:s'),
					'name'          => $instance->name(),
					'filename'      => $filename,
					'description'   => $instance->description(),
					))
				->execute();
		}
		else
		{
			DB::delete($config['table'])
				->where('id', '=', $instance->id())
				->execute();
		}

		return TRUE;
	}

    /**
     * @param $class_name
     *
     * @return Migration
     */
    protected static function create_migration_instance($class_name)
    {
        return new $class_name;
	}

	/**
	 * Checks permissions for current migration
	 * Returns FALSE if migration is not allowed in current context
	 *
	 * @param Migration $obj
	 * @param Minion_Task $task
	 * @return bool
	 */
	protected static function check_file_permissions(Migration $obj, Minion_Task $task)
	{
		return TRUE;
	}

    /**
     * @param Migration $migration
     * @param string $direction
     */
	protected static function before_migration(Migration $migration, $direction)
	{
	    switch ($direction) {
            case self::DIRECTION_UP:
                $migration->before_up();
                break;

            case self::DIRECTION_DOWN:
                $migration->before_down();
                break;
        }
	}

    /**
     * @param Migration $migration
     * @param string $direction
     */
	protected static function after_migration(Migration $migration, $direction)
	{
        switch ($direction) {
            case self::DIRECTION_UP:
                $migration->after_up();
                break;

            case self::DIRECTION_DOWN:
                $migration->after_down();
                break;
        }
	}

	/**
	 * Draws a table by data
	 *
	 * @param   array  $data    data array
	 * @param   array  $filters data filters array
	 * @return  string
	 * @uses    Arr::get
	 * @uses    Arr::flatten
	 * @uses    Arr::path
	 */
	public static function table(array $data, array $filters = NULL)
	{
        $columns = array();

		if (count($data) > 0)
		{
			$columns = ($filters === NULL) ? array_keys(Arr::get(array_values($filters), 0)) : array_keys($filters);

			foreach ($columns as $column)
			{
				if ( ! isset($filters[$column]))
				{
					$filters[$column] = array();
				}

				$filters[$column][] = array('explode', array("\n", ':value'));
			}
		}

		$data = self::_filter_data($data, $filters);

		$column_sizes = array();

		foreach ($columns as $column)
		{
			$column_values = Arr::flatten(Arr::path($data, '*.'.$column));
			$column_values[] = $column;

			$column_sizes[$column] = self::_get_max_stren($column_values);
		}

		$lines = self::_data_to_lines($data, $column_sizes);

		$border_size = array_sum($column_sizes) + 3 * count($column_sizes) + 1;

		return View::factory('minion/migrations/helper/table')
			->set('lines', $lines)
			->set('border_size', $border_size)
            ->render();
	}

	/**
	 * Automatically inserts a newline in string
	 *
	 * @param  string  $text  phrase to limit characters of
	 * @param  integer $limit number of characters to limit to
	 * @return string
	 * @uses   UTF8::strlen
	 */
	public static function fit_text($text, $limit = 32)
	{
		$text = trim(str_replace(array("\r", "\n", "\t"), NULL, $text));

		$words = explode(' ', $text);

		$result = array();
		$row = '';

		foreach ($words as $word)
		{
			$row_length  = UTF8::strlen($row);
			$word_length = UTF8::strlen($word);

			if ($word_length > $limit)
			{
				if (empty($row))
				{
					$result[] = $word;
				}
				else
				{
					$result[] = $row;
					$row      = '';
				}
			}
			else
			{
				$possible_row_length = $row_length + $word_length + 1;

				if ($possible_row_length >= $limit)
				{
					$result[] = $row;
					$row      = $word;
				}
				else
				{
					$row .= ( ! empty($row) ? ' ' : '').$word;
				}
			}
		}

		$result[] = $row;

		return implode("\n", $result);
	}

	/**
	 * Removes a timestamp from migration filename
	 *
	 * @param  string $filename task filename
	 * @return string
	 */
	public static function beautify_migration_filename($filename)
	{
		return str_replace(array('_', EXT), array(' ', (string) NULL), $filename);
	}

	/**
	 * Applies filters to data
	 *
	 * @param  array $data    data
	 * @param  array $filters filters
	 * @return array
	 */
	protected static function _filter_data($data, $filters)
	{
		$result = array();

		foreach ($data as $row_key => $row)
		{
			$result_row = array();

			foreach ($row as $column_key => $column)
			{
				if (isset($filters[$column_key]))
				{
					foreach ($filters[$column_key] as $filter)
					{
						if (isset($filter[1]))
						{
							$values = array();

							foreach ($filter[1] as $value)
							{
								$values[] = ($value === ':value') ? $column : $value;
							}
						}
						else
						{
							$values = array($column);
						}

						$column = call_user_func_array($filter[0], $values);
					}
				}

				$result_row[$column_key] = $column;
			}

			$result[$row_key] = $result_row;
		}

		return $result;
	}

	/**
	 * Returns max string length of string array
	 *
	 * @param  array $array
	 * @return integer
	 * @uses   UTF8::strlen
	 */
	protected static function _get_max_stren(array $array)
	{
		$max_strlen = 0;

		foreach ($array as $string)
		{
			if (UTF8::strlen($string) > $max_strlen)
			{
				$max_strlen = UTF8::strlen($string);
			}
		}

		return $max_strlen;
	}

	/**
	 * Returns max count from array of arrays
	 *
	 * @param  array $array
	 * @return integer
	 */
	protected static function _get_max_count($array)
	{
		$max_count = 0;

		foreach ($array as $row)
		{
			if (count($row) > $max_count)
			{
				$max_count = count($row);
			}
		}

		return $max_count;
	}

	/**
	 * Returns lines array by data array
	 *
	 * @param  array $data         data array
	 * @param  array $column_sizes columns max size array
	 * @return array
	 * @uses   UTF8::strtoupper
	 */
	protected static function _data_to_lines(array $data, array $column_sizes)
	{
		$lines = array();

		$lines[] = NULL; // Border

		$line = array();

		$columns = array_keys($column_sizes);

		foreach ($column_sizes as $column => $size)
		{
			$line[] = str_pad(UTF8::strtoupper($column), $size, ' ', STR_PAD_RIGHT);
		}

		$lines[] = $line; // Header line
		$lines[] = NULL;  // Border

		foreach ($data as $row)
		{
			$max_row_count = self::_get_max_count($row);

			for ($i = 0; $i < $max_row_count; $i++)
			{
				$line = array();

				foreach ($columns as $column)
				{
					$line[] = (isset($row[$column][$i])) ? str_pad($row[$column][$i], $column_sizes[$column], ' ', STR_PAD_RIGHT) : str_repeat(' ', $column_sizes[$column]);
				}

				$lines[] = $line;
			}

			$lines[] = NULL;
		}

		return $lines;
	}

} // End Kohana_Migrations_Helper
