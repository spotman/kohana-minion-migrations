<?php

namespace BetaKiller\Task\Migrations;

use BetaKiller\Console\ConsoleInputInterface;
use BetaKiller\Console\ConsoleOptionBuilderInterface;
use BetaKiller\Task\AbstractTask;
use Kohana;
use BetaKiller\Migration\MigrationHelper;
use Minion_CLI;
use Minion_Exception;
use Throwable;
use UTF8;

/**
 * Creates a new migration file
 *
 * @package        Minion/Migrations
 * @category       Helpers
 * @author         Leemo Studio
 * @author         Alexey Popov (https://github.com/Alexeyco)
 * @copyright  (c) 2009-2013 Leemo Studio
 * @license        BSD 3 http://opensource.org/licenses/BSD-3-Clause
 */
class Create extends AbstractTask
{
    private const ARG_NAME  = 'name';
    private const ARG_DESC  = 'description';
    private const ARG_SCOPE = 'scope';

    public function defineOptions(ConsoleOptionBuilderInterface $builder): array
    {
        return [
            $builder->string(self::ARG_NAME)->optional(),
            $builder->string(self::ARG_DESC)->optional(),
            $builder->string(self::ARG_SCOPE)->optional(),
        ];
    }

    public function run(ConsoleInputInterface $params): void
    {
        $scope = $params->has(self::ARG_SCOPE)
            ? $params->getString(self::ARG_SCOPE)
            : Minion_CLI::read('Migration scope ('.implode(', ', array_keys($this->get_allowed_scopes())).')');

        $name = $params->has(self::ARG_NAME)
            ? $params->getString(self::ARG_NAME)
            : Minion_CLI::read('Migration short name (3-128 characters, [A-Za-z0-9-_] )');

        $desc = $params->has(self::ARG_DESC)
            ? $params->getString(self::ARG_DESC)
            : Minion_CLI::read('Migration description (not necessarily)');

        $validation = \Validation::factory(['name' => $name])
            ->rules('name', [
                ['not_empty'],
                ['min_length', [':value', 3]],
                ['max_length', [':value', 128]],
                ['regex', [':value', '/^[a-zA-Z0-9-_ ]*$/i']],
            ])
            ->label('name', 'Migration name');

        if (!$validation->check()) {
            foreach ($validation->errors('minion/migrations') as $error) {
                Minion_CLI::write($error);
            }

            return;
        }

        $id       = time();
        $filename = $this->_filename($id, $name);
        $name     = UTF8::ucfirst($name);
        $class    = MigrationHelper::filename_to_class($filename);

        $contents = \View::factory('minion/migrations/create')
            ->set('class', $class)
            ->set('id', $id)
            ->set('name', $name)
            ->set('description', $desc)
            ->render();

        $scope_directory = rtrim($this->detect_scope_directory($scope), DIRECTORY_SEPARATOR);
        $named_directory = $this->get_directory_name();

        $full_path = implode(DIRECTORY_SEPARATOR, [$scope_directory, $named_directory, $filename]);

        $directory_path = dirname($full_path);

        if (!file_exists($directory_path)) {
            mkdir($directory_path);
        }

        try {
            file_put_contents($full_path, $contents);
        } catch (Throwable $e) {
            Minion_CLI::write('Error! '.$e->getMessage());
        }

        Minion_CLI::write('Done! Check '.$full_path);
    }

    protected function detect_scope_directory($scope)
    {
        $allowed_scopes = $this->get_allowed_scopes();

        $current_scopes = explode(':', $scope);
        $scopes_count   = count($current_scopes);

        $path = null;

        if ($scopes_count == 3) {
            // Entity setting
            $entity_name = array_pop($current_scopes);
            $key         = implode(':', $current_scopes);

            if (isset($allowed_scopes[$key])) {
                $path = rtrim($allowed_scopes[$key], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$entity_name;
            }
        } elseif ($scopes_count == 1) {
            // Root setting
            $key = array_shift($current_scopes);

            if (isset($allowed_scopes[$key])) {
                $path = rtrim($allowed_scopes[$key], DIRECTORY_SEPARATOR);
            }
        }

        if (!$path) {
            throw new Minion_Exception('Unknown migration scope :value', [':value' => $scope]);
        }

        return $path;
    }

    protected function get_directory_name()
    {
        return Kohana::$config->load('migrations')->get('directory');
    }

    protected function get_allowed_scopes()
    {
        return (array)Kohana::$config->load('migrations')->get('scopes');
    }

    /**
     * Generates a task full filename by task name
     *
     * @param integer $id   migration id
     * @param string  $name migration name
     *
     * @return string
     */
    protected function _filename($id, $name)
    {
        return $id.MigrationHelper::DELIMITER.str_replace(['-', ' '], '_', UTF8::strtolower($name)).EXT;
    }

} // End Kohana_Task_Migrations_Create
