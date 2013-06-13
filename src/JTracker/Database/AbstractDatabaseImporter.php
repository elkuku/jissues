<?php
/**
 * Part of the Joomla Framework Database Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Database;

use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Joomla Framework Database Importer Class
 *
 * @since  1.0
 */
abstract class AbstractDatabaseImporter
{
	/**
	 * The database connector to use for exporting structure and/or data.
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	protected $database = null;

	/**
	 * The input source.
	 *
	 * @var    \SimpleXMLElement
	 * @since  1.0
	 */
	protected $source = array();

	/**
	 * An array of options for the exporter.
	 *
	 * @var    Registry
	 * @since  1.0
	 */
	protected $options = null;

	/**
	 * A logger.
	 *
	 * @var LoggerInterface
	 * @since  1.0
	 */
	private $logger = null;

	/**
	 * Constructor.
	 *
	 * Sets up the default options for the exporter.
	 *
	 * @param   Registry  $options  Options.
	 *
	 * @since   1.0
	 */
	public function __construct(Registry $options = null)
	{
		$this->options = $options ? : new Registry;

		// Import with only structure
		$this->options->set('withStructure', true);
	}

	/**
	 * Run the import.
	 *
	 * @since  1.0
	 * @return $this
	 */
	public function process()
	{
		if ($this->options->get('withStructure'))
		{
			$this->mergeStructure();
		}

		if ($this->options->get('withData'))
		{
			$this->insertData();
		}

		return $this;
	}

	/**
	 * Specifies the data source to import.
	 *
	 * @param   mixed  $source  The data source to import.
	 *
	 * @since  1.0
	 * @throws \UnexpectedValueException
	 * @return $this
	 */
	public function setSource($source)
	{
		$this->source = ($source instanceof \SimpleXMLElement)
			? $source
			: new \SimpleXMLElement($source);

		if (!$this->source)
		{
			throw new \UnexpectedValueException('Invalid source');
		}

		return $this;
	}

	/**
	 * Sets the database connector to use for exporting structure and/or data.
	 *
	 * @param   DatabaseDriver  $database  The database connector.
	 *
	 * @since   1.0
	 * @return  $this
	 */
	public function setDbo(DatabaseDriver $database)
	{
		$this->database = $database;

		return $this;
	}

	/**
	 * Sets an internal option to merge the structure based on the input data.
	 *
	 * @param   boolean  $setting  True to import the structure, false to not.
	 *
	 * @since   1.0
	 * @return  $this
	 */
	public function withStructure($setting = true)
	{
		$this->options->set('withStructure', (boolean) $setting);

		return $this;
	}

	/**
	 * Sets an internal option to import the data of the input table(s).
	 *
	 * @param   boolean  $setting  True to import the data, false to not.
	 *
	 * @since 1.0
	 * @return $this
	 */
	public function withData($setting = true)
	{
		$this->options->set('withData', (boolean) $setting);

		return $this;
	}

	/**
	 * Set a logger.
	 *
	 * @param   LoggerInterface  $logger  The logger to attach
	 *
	 * @since   1.0
	 * @return  $this
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * Merges the incoming structure definition with the existing structure.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @return  $this
	 */
	protected function mergeStructure()
	{
		$prefix = $this->database->getPrefix();
		$tables = $this->database->getTableList();

		// Get all the table definitions.
		$xmlTables = $this->source->xpath('database/table_structure');

		foreach ($xmlTables as $table)
		{
			// Convert the magic prefix into the real table name.
			$tableName = (string) $table->attributes()->name;
			$tableName = preg_replace('|^#__|', $prefix, $tableName);

			if (in_array($tableName, $tables))
			{
				// The table already exists. Now check if there is any difference.

				foreach ($this->getAlterTableSQL($table) as $query)
				{
					// Run the queries to upgrade the data structure.
					$this->runQuery($query);
				}
			}
			else
			{
				// This is a new table.

				$this->runQuery($this->getCreateTableSQL($table));
			}
		}

		return $this;
	}

	/**
	 * Import the data if requested
	 *
	 * @since  1.0
	 * @return $this
	 */
	protected function insertData()
	{
		$tables = $this->source->xpath('database/table_data');

		foreach ($tables as $table)
		{
			// Run the queries to upgrade the data structure.
			$query = $this->getInsertSQL($table);

			if ($query)
			{
				$this->runQuery($query);
			}
		}

		return $this;
	}

	/**
	 * Run a database query.
	 *
	 * @param   string  $query  The query.
	 *
	 * @since  1.0
	 * @throws \Exception|\RuntimeException
	 * @return $this
	 */
	private function runQuery($query)
	{
		try
		{
			$this->database
				->setQuery((string) $query)
				->execute();

			$this->log($this->database->getQuery());
		}
		catch (\RuntimeException $e)
		{
			$this->log('ERROR: ' . $this->database->getQuery());

			throw $e;
		}

		return $this;
	}

	/**
	 * Get the real name of the table, converting the prefix wildcard string if present.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @since   1.0
	 * @return  string	The real name of the table.
	 */
	protected function getRealTableName($table)
	{
		$prefix = $this->database->getPrefix();

		// Replace the magic prefix if found.
		$table = preg_replace('|^#__|', $prefix, $table);

		return $table;
	}

	/**
	 * Get the details list of keys for a table.
	 *
	 * @param   array  $keys  An array of objects that comprise the keys for the table.
	 *
	 * @since   1.0
	 * @return  array  The lookup array. array({key name} => array(object, ...))
	 */
	protected function getKeyLookup($keys)
	{
		// First pass, create a lookup of the keys.
		$lookup = array();

		foreach ($keys as $key)
		{
			if ($key instanceof \SimpleXMLElement)
			{
				$kName = (string) $key->attributes()->Key_name;
			}
			else
			{
				$kName = $key->Key_name;
			}

			if (empty($lookup[$kName]))
			{
				$lookup[$kName] = array();
			}

			$lookup[$kName][] = $key;
		}

		return $lookup;
	}

	/**
	 * Add a log message.
	 *
	 * @param   string  $message  The message
	 *
	 * @since  1.0
	 * @return $this
	 */
	protected function log($message)
	{
		if (is_null($this->logger))
		{
			return $this;
		}

		$this->logger->log(LogLevel::DEBUG, $message);

		return $this;
	}

	/**
	 * Get the create statement for a table..
	 *
	 * @param   \SimpleXMLElement  $table  The XML structure pf the table.
	 *
	 * @since   1.0
	 * @throws \UnexpectedValueException
	 * @return  string
	 */
	protected function getCreateTableSQL(\SimpleXMLElement $table)
	{
		$name = (string) $table->attributes()->name;

		if (!$name)
		{
			throw new \UnexpectedValueException('Empty table name');
		}

		$create = array();

		$create[] = 'CREATE TABLE ' . $name;

		// Process fields

		$fields = array();

		foreach ($table->field as $field)
		{
			$fields[] = $this->getColumnSQL($field);
		}

		$create[] = '(';

		$create[] = implode(",\n", $fields);

		// Process keys

		$ks = array();

		foreach ($this->getKeyLookup($table->key) as $keys)
		{
			$s = $this->getKeySQL($keys);

			if ($s)
			{
				$ks[] = $s;
			}
		}

		if ($ks)
		{
			$create[] = ",\n" . implode(",\n", $ks);
		}

		$create[] = ');';

		return implode("\n", $create);
	}

	/**
	 * Get the SQL syntax to drop a column.
	 *
	 * @param   string  $table  The table name.
	 * @param   string  $name   The name of the field to drop.
	 *
	 * @todo move to implementation ?
	 *
	 * @since   1.0
	 * @return  string
	 */
	protected function getDropColumnSQL($table, $name)
	{
		$sql = 'ALTER TABLE ' . $this->database->quoteName($table) . ' DROP COLUMN ' . $this->database->quoteName($name);

		return $sql;
	}

	/**
	 * Checks if all data and options are in order prior to exporting.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error is encountered.
	 * @return  $this
	 */
	abstract public function check();

	/**
	 * Get alters for table if there is a difference.
	 *
	 * @param   \SimpleXMLElement  $structure  The XML structure of the table.
	 *
	 * @since   1.0
	 * @return  array
	 */
	abstract protected function getAlterTableSQL(\SimpleXMLElement $structure);

	/**
	 * Get insert statements for the table.
	 *
	 * @param   \SimpleXMLElement  $structure  The XML structure of the table.
	 *
	 * @since   1.0
	 * @return  array
	 */
	abstract protected function getInsertSQL(\SimpleXMLElement $structure);

	/**
	 * Get the SQL syntax for a single column that would be included in a table create or alter statement.
	 *
	 * @param   \SimpleXMLElement  $field  The XML field definition.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	abstract protected function getColumnSQL(\SimpleXMLElement $field);

	/**
	 * Get the SQL syntax to add a column.
	 *
	 * @param   string             $table  The table name.
	 * @param   \SimpleXMLElement  $field  The XML field definition.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	abstract protected function getAddColumnSQL($table, \SimpleXMLElement $field);

	/**
	 * Get the syntax to alter a column.
	 *
	 * @param   string             $table  The name of the database table to alter.
	 * @param   \SimpleXMLElement  $field  The XML definition for the field.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	abstract protected function getChangeColumnSQL($table, \SimpleXMLElement $field);

	/**
	 * Get the SQL syntax for a key.
	 *
	 * @param   array  $columns  An array of SimpleXMLElement objects comprising the key.
	 *
	 * @since   1.0
	 * @return  string
	 */
	abstract protected function getKeySQL($columns);

	/**
	 * Get the SQL syntax to add a key.
	 *
	 * @param   string  $table  The table name.
	 * @param   array   $keys   An array of the fields pertaining to this key.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	abstract protected function getAddKeySQL($table, $keys);

	/**
	 * Get the SQL syntax to drop a key.
	 *
	 * @param   string  $table  The table name.
	 * @param   string  $name   The name of the key to drop.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	abstract protected function getDropKeySQL($table, $name);

	/**
	 * Get the SQL syntax to drop a key.
	 *
	 * @param   string  $table  The table name.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	abstract protected function getDropPrimaryKeySQL($table);
}
