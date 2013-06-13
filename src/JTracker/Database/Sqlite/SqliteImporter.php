<?php
/**
 * Part of the Joomla Framework Database Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Database\Sqlite;

use Joomla\Database\Sqlite\SqliteDriver;
use JTracker\Database\AbstractDatabaseImporter;

/**
 * Class TrackerSqliteImporter
 *
 * @since  1.0
 */
class SqliteImporter extends AbstractDatabaseImporter
{
	/**
	 * Get the SQL syntax for a single column that would be included in a table create or alter statement.
	 *
	 * @param   \SimpleXMLElement  $field  The XML field definition.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	protected function getColumnSQL(\SimpleXMLElement $field)
	{
		// TODO Incorporate into parent class and use $this.
		$blobs = array('text', 'smalltext', 'mediumtext', 'largetext');

		$attribs = $field->attributes();

		$fName = (string) $attribs->Field;
		$fType = $this->getSimpleType((string) $attribs->Type);
		$fNull = (string) $attribs->Null;
		$fDefault = ($attribs->Default) ? : null;
		$fExtra = (string) $attribs->Extra;
		$fKey = (string) $attribs->Key;

		$comment = (string) $attribs->Comment;

		// $fExtra = str_replace('auto_increment', 'AUTOINCREMENT', $fExtra);
		$fExtra = str_replace('auto_increment', '', $fExtra);

		$sql = '';

		$sql .= ($comment) ? '--- ' . $comment . "\n" : '';

		$sql .= $fName . ' ' . $fType;

		if ('PRI' == $fKey)
		{
			// $sql .= ' PRIMARY KEY';
		}

		if ('NO' == $fNull && 'PRI' != $fKey)
		{
			if (in_array($fType, $blobs) || $fDefault === null)
			{
				// $sql .= ' NOT NULL';
			}
			else
			{
				// TODO Don't quote numeric values.
				// $sql .= ' NOT NULL DEFAULT ' . $this->db->quote($fDefault);
				$sql .= ' DEFAULT ' . $this->database->quote($fDefault);
			}
		}
		elseif ('PRI' != $fKey)
		{
			if ($fDefault === null)
			{
				$sql .= ' DEFAULT NULL';
			}
			else
			{
				// TODO Don't quote numeric values.
				$sql .= ' DEFAULT ' . $this->database->quote($fDefault);
			}
		}

		if ($fExtra)
		{
			$sql .= ' ' . strtoupper($fExtra);
		}

		return $sql;
	}

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
	protected function getAddColumnSQL($table, \SimpleXMLElement $field)
	{
		$sql = 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $this->getColumnSQL($field);

		return $sql;
	}

	/**
	 * Get alters for table if there is a difference.
	 *
	 * @param   \SimpleXMLElement  $structure  The XML structure pf the table.
	 *
	 * @since   1.0
	 * @return  array
	 */
	protected function getAlterTableSQL(\SimpleXMLElement $structure)
	{
		$table = $this->getRealTableName($structure['name']);
		$oldFields = $this->database->getTableColumns($table, false);
		$oldKeys = $this->database->getTableKeys($table);
		$alters = array();

		// Get the fields and keys from the XML that we are aiming for.
		$newFields = $structure->xpath('field');
		$newKeys = $structure->xpath('key');

		// Loop through each field in the new structure.
		foreach ($newFields as $field)
		{
			$fName = (string) $field['Field'];

			if (isset($oldFields[$fName]))
			{
				// The field exists, check it's the same.
				$column = $oldFields[$fName];

				// Test whether there is a change.
				$change = ((string) $field['Type'] != $column->Type)
					|| ((string) $field['Null'] != $column->Null)
					|| ((string) $field['Default'] != $column->Default)
					|| ((string) $field['Extra'] != $column->Extra);

				if ($change)
				{
					$alters[] = $this->getChangeColumnSQL($table, $field);
				}

				// Unset this field so that what we have left are fields that need to be removed.
				unset($oldFields[$fName]);
			}
			else
			{
				// The field is new.
				$alters[] = $this->getAddColumnSQL($table, $field);
			}
		}

		// Any columns left are orphans
		foreach ($oldFields as $name => $column)
		{
			// Delete the column.
			$alters[] = $this->getDropColumnSQL($table, $name);
		}

		// Get the lookups for the old and new keys.
		$oldLookup = $this->getKeyLookup($oldKeys);
		$newLookup = $this->getKeyLookup($newKeys);

		// Loop through each key in the new structure.
		foreach ($newLookup as $name => $keys)
		{
			// Check if there are keys on this field in the existing table.
			if (isset($oldLookup[$name]))
			{
				$same = true;
				$newCount = count($newLookup[$name]);
				$oldCount = count($oldLookup[$name]);

				// There is a key on this field in the old and new tables. Are they the same?
				if ($newCount == $oldCount)
				{
					// Need to loop through each key and do a fine grained check.
					for ($i = 0; $i < $newCount; $i++)
					{
						$same = (((string) $newLookup[$name][$i]['Non_unique'] == $oldLookup[$name][$i]->Non_unique)
							&& ((string) $newLookup[$name][$i]['Column_name'] == $oldLookup[$name][$i]->Column_name)
							&& ((string) $newLookup[$name][$i]['Seq_in_index'] == $oldLookup[$name][$i]->Seq_in_index)
							&& ((string) $newLookup[$name][$i]['Collation'] == $oldLookup[$name][$i]->Collation)
							&& ((string) $newLookup[$name][$i]['Index_type'] == $oldLookup[$name][$i]->Index_type));

						/*
						Debug.
						echo '<pre>';
						echo '<br />Non_unique:   '.
							((string) $newLookup[$name][$i]['Non_unique'] == $oldLookup[$name][$i]->Non_unique ? 'Pass' : 'Fail').' '.
							(string) $newLookup[$name][$i]['Non_unique'].' vs '.$oldLookup[$name][$i]->Non_unique;
						echo '<br />Column_name:  '.
							((string) $newLookup[$name][$i]['Column_name'] == $oldLookup[$name][$i]->Column_name ? 'Pass' : 'Fail').' '.
							(string) $newLookup[$name][$i]['Column_name'].' vs '.$oldLookup[$name][$i]->Column_name;
						echo '<br />Seq_in_index: '.
							((string) $newLookup[$name][$i]['Seq_in_index'] == $oldLookup[$name][$i]->Seq_in_index ? 'Pass' : 'Fail').' '.
							(string) $newLookup[$name][$i]['Seq_in_index'].' vs '.$oldLookup[$name][$i]->Seq_in_index;
						echo '<br />Collation:    '.
							((string) $newLookup[$name][$i]['Collation'] == $oldLookup[$name][$i]->Collation ? 'Pass' : 'Fail').' '.
							(string) $newLookup[$name][$i]['Collation'].' vs '.$oldLookup[$name][$i]->Collation;
						echo '<br />Index_type:   '.
							((string) $newLookup[$name][$i]['Index_type'] == $oldLookup[$name][$i]->Index_type ? 'Pass' : 'Fail').' '.
							(string) $newLookup[$name][$i]['Index_type'].' vs '.$oldLookup[$name][$i]->Index_type;
						echo '<br />Same = '.($same ? 'true' : 'false');
						echo '</pre>';
						 */

						if (!$same)
						{
							// Break out of the loop. No need to check further.
							break;
						}
					}
				}
				else
				{
					// Count is different, just drop and add.
					$same = false;
				}

				if (!$same)
				{
					$alters[] = $this->getDropKeySQL($table, $name);
					$alters[] = $this->getAddKeySQL($table, $keys);
				}

				// Unset this field so that what we have left are fields that need to be removed.
				unset($oldLookup[$name]);
			}
			else
			{
				// This is a new key.
				$alters[] = $this->getAddKeySQL($table, $keys);
			}
		}

		// Any keys left are orphans.
		foreach ($oldLookup as $name => $keys)
		{
			if (strtoupper($name) == 'PRIMARY')
			{
				// $alters[] = $this->getDropPrimaryKeySQL($table);
			}
			else
			{
			}

			$alters[] = $this->getDropKeySQL($table, $name);
		}

		return $alters;
	}

	/**
	 * Checks if all data and options are in order prior to importing.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error is encountered.
	 * @return  $this
	 */
	public function check()
	{
		// Check if the db connector has been set.
		if (!($this->database instanceof SqliteDriver))
		{
			throw new \Exception('Wrong database connector type');
		}

		// Check if the tables have been specified.
		if (empty($this->source))
		{
			throw new \Exception('No source specified');
		}

		return $this;
	}

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
	protected function getChangeColumnSQL($table, \SimpleXMLElement $field)
	{
		throw new \RuntimeException(__METHOD__ . ' not supported.');
	}

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
	protected function getAddKeySQL($table, $keys)
	{
		throw new \RuntimeException(__METHOD__ . ' not supported.');
	}

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
	protected function getDropKeySQL($table, $name)
	{
		throw new \RuntimeException(__METHOD__ . ' not supported.');
	}

	/**
	 * Get the SQL syntax for a key.
	 *
	 * @param   array  $columns  An array of SimpleXMLElement objects comprising the key.
	 *
	 * @since   1.0
	 * @return  string
	 */
	protected function getKeySQL($columns)
	{
		// TODO Error checking on array and element types.

		$kNonUnique = (string) $columns[0]['Non_unique'];
		$kName = (string) $columns[0]['Key_name'];
		$kColumn = (string) $columns[0]['Column_name'];

		$prefix = '';

		if ($kName == 'PRIMARY')
		{
			$prefix = 'PRIMARY KEY ';
		}
		elseif ($kNonUnique == 0)
		{
			// $prefix = 'UNIQUE ';
		}

		if (!$prefix)
		{
			// UNSUPPORTED ?
			return '';
		}

		$nColumns = count($columns);
		$kColumns = array();

		if ($nColumns == 1)
		{
			$kColumns[] = $kColumn;
		}
		else
		{
			foreach ($columns as $column)
			{
				$kColumns[] = (string) $column['Column_name'];
			}
		}

		$sql = $prefix . ($kName != 'PRIMARY' ? $kName : '') . ' (' . implode(',', $kColumns) . ')';

		return $sql;
	}

	/**
	 * Get insert statements for the table.
	 *
	 * @param   \SimpleXMLElement  $structure  The XML structure of the table.
	 *
	 * @since   1.0
	 * @return  array
	 */
	protected function getInsertSQL(\SimpleXMLElement $structure)
	{
		$name = (string) $structure->attributes()->name;

		if (!$name)
		{
			throw new \UnexpectedValueException('Empty table name');
		}

		$insert = array();

		foreach ($structure->row as $row)
		{
			$values = array();

			if (!count($insert))
			{
				$fs = array();

				foreach ($row->field as $field)
				{
					$fs[] = "'$field' AS " . $field->attributes()->name;
				}

				$insert[] = '    SELECT ' . implode(', ', $fs);
			}
			else
			{
				foreach ($row->field as $field)
				{
					$values[] = "'$field'";
				}

				$insert[] = 'UNION SELECT ' . implode(', ', $values);
			}
		}

		return ($insert)
		? 'INSERT INTO ' . $name
			. "\n" . implode("\n", $insert)
		: '';
	}

	/**
	 * Get the SQL syntax to drop a key.
	 *
	 * @param   string  $table  The table name.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	protected function getDropPrimaryKeySQL($table)
	{
		throw new \RuntimeException(__METHOD__ . ' not supported.');
	}

	/**
	 * Get a Sqlite data type.
	 *
	 * @param   string  $complex  The "complex" type e.g. "tinyint(11) unsigned" becomes INTEGER
	 *
	 * @throws \UnexpectedValueException
	 * @return int|string
	 */
	private function getSimpleType($complex)
	{
		$simpleTypes = array(
			'INTEGER' => array('int', 'tinyint'),
			'REAL' => array(),
			'TEXT' => array('varchar', 'char', 'text', 'mediumtext', 'datetime'),
			'BLOB' => array(),
			'NULL' => array()
		);

		$c = preg_replace('/[0-9\(\)]+/', '', $complex);
		$c = str_replace(array('unsigned'), '', $c);
		$c = trim($c);

		foreach ($simpleTypes as $type => $types)
		{
			if (in_array($c, $types))
			{
				return $type;
			}
		}

		throw new \UnexpectedValueException('Unknown complex: ' . $complex);
	}
}
