<?php echo '<?php' ?>

class <?php echo $class ?> extends \BetaKiller\Migration\AbstractMigration {

	/**
	 * Returns migration ID
	 *
	 * @return integer
	 */
	public function id(): int
	{
		return <?php echo $id ?>;
	}

	/**
	 * Returns migration name
	 *
	 * @return string
	 */
	public function name(): string
	{
		return '<?php echo addcslashes($name, '\'') ?>';
	}

	/**
	 * Returns migration info
	 *
	 * @return string
	 */
	public function description(): string
	{
		return '<?php echo addcslashes($description, '\'') ?>';
	}

	/**
	 * Takes a migration
	 *
	 * @return void
	 */
	public function up(): void
	{

	}

	/**
	 * Removes migration
	 *
	 * @return void
	 */
	public function down(): void
	{

	}
}
