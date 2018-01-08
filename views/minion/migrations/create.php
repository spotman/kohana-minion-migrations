<?php echo '<?php' ?>


class <?php echo $class ?> extends Migration {

	/**
	 * Returns migration ID
	 *
	 * @return integer
	 */
	public function id()
	{
		return <?php echo $id ?>;
	}

	/**
	 * Returns migration name
	 *
	 * @return string
	 */
	public function name()
	{
		return '<?php echo addcslashes($name, '\'') ?>';
	}

	/**
	 * Returns migration info
	 *
	 * @return string
	 */
	public function description()
	{
		return '<?php echo addcslashes($description, '\'') ?>';
	}

	/**
	 * Takes a migration
	 *
	 * @return void
	 */
	public function up()
	{

	}

	/**
	 * Removes migration
	 *
	 * @return void
	 */
	public function down()
	{

	}

} // End <?php echo $class ?>

