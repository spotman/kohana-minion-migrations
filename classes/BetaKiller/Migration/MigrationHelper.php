<?php

namespace BetaKiller\Migration;

use Arr;
use BetaKiller\Console\ConsoleTaskInterface;
use DateTimeImmutable;
use DB;
use Kohana;
use BetaKiller\Console\ConsoleException;
use Throwable;
use UTF8;
use View;

/**
 * Migrations helper
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class MigrationHelper
{
    /**
     * Migrations filename delimiter
     *
     * @var string
     */
    public const DELIMITER = '___';

    /**
     * Returns migration class name from migration filename
     *
     * @param string $filename
     *
     * @return string
     */
    public static function filename_to_class($filename)
    {
        $filename = explode('___', $filename);

        $stamp    = $filename[0];
        $filename = str_replace(['_', EXT], [' ', (string)null], $filename[1]);

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

        $available_migrations = [];

        foreach (array_values($files) as $file) {
            $filename = pathinfo($file, PATHINFO_BASENAME);

            if (!in_array($filename, $applied_migrations)) {
                require $file;

                $class = self::filename_to_class($filename);

                $migration = static::create_migration_instance($class);

                $available_migrations[$migration->id()] = [
                    'id'          => $migration->id(),
                    'filename'    => $filename,
                    'name'        => $migration->name(),
                    'description' => $migration->description(),
                ];
            }
        }

        ksort($available_migrations);

        return $available_migrations;
    }

    /**
     * Direction UP identifier
     */
    const DIRECTION_UP = 'up';

    /**
     * Direction DOWN identifier
     */
    const DIRECTION_DOWN = 'down';

    /**
     * Apply or remove migration
     *
     * @param string                                   $filename  migration filename
     * @param string                                   $direction direction
     * @param \BetaKiller\Console\ConsoleTaskInterface $task
     *
     * @return bool
     * @throws \BetaKiller\Exception
     * @throws \Kohana_Exception
     * @throws \BetaKiller\Console\ConsoleException
     */
    public static function apply(string $filename, string $direction, ConsoleTaskInterface $task): bool
    {
        $config = Kohana::$config->load('migrations')
            ->as_array();

        $class = self::filename_to_class($filename);

        if (!class_exists($class)) {
            require Kohana::find_file($config['directory'], $filename, false);
        }

        $instance = static::create_migration_instance($class);

        // Skip migrations without permission
        if (!static::check_file_permissions($instance, $task)) {
            return false;
        }

        try {
            static::before_migration($instance, $direction);

            $instance->$direction();

            static::after_migration($instance, $direction);
        } catch (Throwable $e) {
            throw new ConsoleException('Fatal error! '.$e->getMessage().' in '.$e->getFile().':'.$e->getFile());
        }

        if ($direction === self::DIRECTION_UP) {
            DB::insert($config['table'], ['id', 'date', 'name', 'filename', 'description'])
                ->values([
                    'id'          => $instance->id(),
                    'date'        => date('Y-m-d H:i:s'),
                    'name'        => $instance->name(),
                    'filename'    => $filename,
                    'description' => $instance->description(),
                ])
                ->execute();
        } else {
            DB::delete($config['table'])
                ->where('id', '=', $instance->id())
                ->execute();
        }

        return true;
    }

    /**
     * @param $class_name
     *
     * @return \BetaKiller\Migration\MigrationInterface
     */
    protected static function create_migration_instance($class_name)
    {
        return \BetaKiller\DI\Container::getInstance()->get($class_name);
    }

    /**
     * Checks permissions for current migration
     * Returns FALSE if migration is not allowed in current context
     *
     * @param \BetaKiller\Migration\MigrationInterface $obj
     * @param \BetaKiller\Console\ConsoleTaskInterface $task
     *
     * @return bool
     */
    protected static function check_file_permissions(MigrationInterface $obj, ConsoleTaskInterface $task): bool
    {
        return true;
    }

    /**
     * @param \BetaKiller\Migration\MigrationInterface $migration
     * @param string                                   $direction
     */
    protected static function before_migration(MigrationInterface $migration, string $direction): void
    {
        switch ($direction) {
            case self::DIRECTION_UP:
                $migration->beforeUp();
                break;

            case self::DIRECTION_DOWN:
                $migration->beforeDown();
                break;
        }
    }

    /**
     * @param \BetaKiller\Migration\MigrationInterface $migration
     * @param string                                   $direction
     */
    protected static function after_migration(MigrationInterface $migration, string $direction): void
    {
        switch ($direction) {
            case self::DIRECTION_UP:
                $migration->afterUp();
                break;

            case self::DIRECTION_DOWN:
                $migration->afterDown();
                break;
        }
    }

    /**
     * Draws a table by data
     *
     * @param array $data    data array
     * @param array $columns Columns to display
     *
     * @return  string
     * @throws \View_Exception
     * @uses    Arr::get
     * @uses    Arr::flatten
     * @uses    Arr::path
     */
    public static function table(array $data, array $columns)
    {
        $lookup = [
            'id' => [],

            'date' => [
                [
                    function (string $dt, string $format) {
                        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dt);

                        return $date->format($format);
                    },
                    [':value', "Y-m-d\nH:i"],
                ],
            ],

            'name' => [
                [
                    ['BetaKiller\Migration\MigrationHelper', 'fit_text'],
                    [':value', 32],
                ],
            ],

            'description' => [
                [
                    ['BetaKiller\Migration\MigrationHelper', 'fit_text'],
                    [':value', 32],
                ],
            ],
        ];

        $filters = [];

        $hasData = count($data) > 0;

        if (!$hasData) {
            $columns = [];
        }

        foreach ($columns as $column) {
            $filters[$column] = $lookup[$column] ?? [];

            if ($hasData) {
                $filters[$column][] = ['explode', ["\n", ':value']];
            }
        }

        $data = self::_filter_data($data, $filters);

        $column_sizes = [];

        foreach ($columns as $column) {
            $column_values   = Arr::flatten(Arr::path($data, '*.'.$column));
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
     * @param string  $text  phrase to limit characters of
     * @param integer $limit number of characters to limit to
     *
     * @return string
     * @uses   UTF8::strlen
     */
    public static function fit_text($text, $limit = 32)
    {
        $text = trim(str_replace(["\r", "\n", "\t"], '', $text));

        $words = explode(' ', $text);

        $result = [];
        $row    = '';

        foreach ($words as $word) {
            $row_length  = UTF8::strlen($row);
            $word_length = UTF8::strlen($word);

            if ($word_length > $limit) {
                if (empty($row)) {
                    $result[] = $word;
                } else {
                    $result[] = $row;
                    $row      = '';
                }
            } else {
                $possible_row_length = $row_length + $word_length + 1;

                if ($possible_row_length >= $limit) {
                    $result[] = $row;
                    $row      = $word;
                } else {
                    $row .= (!empty($row) ? ' ' : '').$word;
                }
            }
        }

        $result[] = $row;

        return implode("\n", $result);
    }

    /**
     * Removes a timestamp from migration filename
     *
     * @param string $filename task filename
     *
     * @return string
     */
    public static function beautify_migration_filename($filename)
    {
        return str_replace(['_', EXT], [' ', (string)null], $filename);
    }

    /**
     * Applies filters to data
     *
     * @param array $data    data
     * @param array $filters filters
     *
     * @return array
     */
    protected static function _filter_data($data, $filters)
    {
        $result = [];

        foreach ($data as $row_key => $row) {
            $result_row = [];

            foreach ($row as $column_key => $column) {
                if (isset($filters[$column_key])) {
                    foreach ($filters[$column_key] as $filter) {
                        if (isset($filter[1])) {
                            $values = [];

                            foreach ($filter[1] as $value) {
                                $values[] = ($value === ':value') ? $column : $value;
                            }
                        } else {
                            $values = [$column];
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
     * @param array $array
     *
     * @return integer
     * @uses   UTF8::strlen
     */
    protected static function _get_max_stren(array $array)
    {
        $max_strlen = 0;

        foreach ($array as $string) {
            if (UTF8::strlen($string) > $max_strlen) {
                $max_strlen = UTF8::strlen($string);
            }
        }

        return $max_strlen;
    }

    /**
     * Returns max count from array of arrays
     *
     * @param array $array
     *
     * @return integer
     */
    protected static function _get_max_count($array)
    {
        $max_count = 0;

        foreach ($array as $row) {
            if (count($row) > $max_count) {
                $max_count = count($row);
            }
        }

        return $max_count;
    }

    /**
     * Returns lines array by data array
     *
     * @param array $data         data array
     * @param array $column_sizes columns max size array
     *
     * @return array
     * @uses   UTF8::strtoupper
     */
    protected static function _data_to_lines(array $data, array $column_sizes)
    {
        $lines = [];

        $lines[] = null; // Border

        $line = [];

        $columns = array_keys($column_sizes);

        foreach ($column_sizes as $column => $size) {
            $line[] = str_pad(UTF8::strtoupper($column), $size, ' ', STR_PAD_RIGHT);
        }

        $lines[] = $line; // Header line
        $lines[] = null;  // Border

        foreach ($data as $row) {
            $max_row_count = self::_get_max_count($row);

            for ($i = 0; $i < $max_row_count; $i++) {
                $line = [];

                foreach ($columns as $column) {
                    $line[] = (isset($row[$column][$i]))
                        ? str_pad($row[$column][$i], $column_sizes[$column], ' ', STR_PAD_RIGHT)
                        : str_repeat(
                            ' ',
                            $column_sizes[$column]
                        );
                }

                $lines[] = $line;
            }

            $lines[] = null;
        }

        return $lines;
    }
}
