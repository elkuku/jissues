<?php
/**
 * Part of the Joomla Framework Database Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Database;

use Joomla\Database\DatabaseDriver;

/**
 * Joomla Framework Database Exporter Class
 *
 * @since  1.0
 */
abstract class AbstractDatabaseExporter
{
	/**
	 * The database connector to use for exporting structure and/or data.
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	protected $database = null;

	/**
	 * An array input sources (table names).
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $tables = array();

	/**
	 * Options for the exporter.
	 *
	 * @var    \stdClass
	 * @since  1.0
	 */
	protected $options = null;

	/**
	 * Constructor.
	 *
	 * Sets up the default options for the exporter.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->options = new \stdClass;

		$this->cache = array('columns' => array(), 'keys' => array());

		// Export with only structure
		$this->withStructure();
	}

	/**
	 * Run the export.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function export()
	{
		return $this->__toString();
	}

	/**
	 * Magic function to exports the data to a string.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error is encountered.
	 * @return  string
	 */
	public function __toString()
	{
		// Check everything is ok to run first.
		$this->check();

		return $this->buildXml();
	}

	/**
	 * Builds the XML data for the tables to export.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error occurs.
	 * @return  string  An XML string
	 */
	abstract protected function buildXml();

	/**
	 * Builds the XML structure to export.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error occurs.
	 * @return  array  An array of XML lines (strings).
	 */
	abstract protected function buildXmlStructure();

	/**
	 * Checks if all data and options are in order prior to exporting.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error is encountered.
	 * @return  DatabaseDriver
	 */
	abstract public function check();

	/**
	 * Specifies a list of table names to export.
	 *
	 * @param   mixed  $tables  The name of a single table, or an array of the table names to export.
	 *
	 * @since   1.0
	 * @throws  \Exception if input is not a string or array.
	 * @return  AbstractDatabaseExporter
	 */
	public function setTables($tables)
	{
		if (is_string($tables))
		{
			$this->tables = array($tables);
		}
		elseif (is_array($tables))
		{
			$this->tables = $tables;
		}
		else
		{
			throw new \Exception('Input must be string or array');
		}

		return $this;
	}

	/**
	 * Get the generic name of the table, converting the database prefix to the wildcard string.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @return  string  The name of the table with the database prefix replaced with #__.
	 *
	 * @since   1.0
	 */
	protected function getGenericTableName($table)
	{
		$prefix = $this->database->getPrefix();

		// Replace the magic prefix if found.
		$table = preg_replace("|^$prefix|", '#__', $table);

		return $table;
	}

	/**
	 * Sets the database connector to use for exporting structure and/or data from MySQL.
	 *
	 * @param   DatabaseDriver  $database  The database connector.
	 *
	 * @return  AbstractDatabaseExporter
	 *
	 * @since   1.0
	 */
	public function setDbo(DatabaseDriver $database)
	{
		$this->database = $database;

		return $this;
	}

	/**
	 * Sets an internal option to export the structure of the input table(s).
	 *
	 * @param   boolean  $setting  True to export the structure, false to not.
	 *
	 * @since   1.0
	 * @return  AbstractDatabaseExporter
	 */
	public function withStructure($setting = true)
	{
		$this->options->withStructure = (boolean) $setting;

		return $this;
	}

	/**
	 * Sets an internal option to export the data of the input table(s).
	 *
	 * @param   boolean  $setting  True to export the data, false to not.
	 *
	 * @since 1.0
	 * @return AbstractDatabaseExporter
	 */
	public function withData($setting = true)
	{
		$this->options->withData = (boolean) $setting;

		return $this;
	}
}
