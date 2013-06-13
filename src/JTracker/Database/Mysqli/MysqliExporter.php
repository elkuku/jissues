<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Database\Mysqli;

use Joomla\Database\Mysqli\MysqliDriver;

use JTracker\Database\AbstractDatabaseExporter;

/**
 * Class TrackerMysqliExporter
 *
 * @since  1.0
 */
class MysqliExporter extends AbstractDatabaseExporter
{
	/**
	 * Builds the XML data for the tables to export.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error occurs.
	 * @return  string  An XML string
	 */
	protected function buildXml()
	{
		$buffer = array();

		$buffer[] = '<?xml version="1.0"?>';
		$buffer[] = '<mysqldump xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
		$buffer[] = ' <database>';

		$buffer = array_merge($buffer, $this->buildXmlStructure());

		$buffer[] = ' </database>';
		$buffer[] = '</mysqldump>';

		return implode("\n", $buffer);
	}

	/**
	 * Builds the XML structure to export.
	 *
	 * @since   1.0
	 * @return  array  An array of XML lines (strings).
	 */
	protected function buildXmlStructure()
	{
		$buffer = array();
		$query = $this->database->getQuery(true);

		foreach ($this->tables as $table)
		{
			// Replace the magic prefix if found.
			$table = $this->getGenericTableName($table);

			/*
			 * Table structure
			 */

			// Get the details columns information.
			$fields = $this->database->getTableColumns($table, false);
			$keys = $this->database->getTableKeys($table);

			$buffer[] = '  <table_structure name="' . $table . '">';

			foreach ($fields as $field)
			{
				$buffer[] = '   <field'
					. ' Field="' . $field->Field . '"'
					. ' Type="' . $field->Type . '"'
					. ' Null="' . $field->Null . '"'
					. ' Key="' . $field->Key . '"'
					. (isset($field->Default) ? ' Default="' . $field->Default . '"' : '')
					. ' Extra="' . $field->Extra . '"'
					. ' Comment="' . htmlspecialchars($field->Comment) . '"'
					. ' />';
			}

			foreach ($keys as $key)
			{
				$buffer[] = '   <key'
					. ' Table="' . $table . '"'
					. ' Non_unique="' . $key->Non_unique . '"'
					. ' Key_name="' . $key->Key_name . '"'
					. ' Seq_in_index="' . $key->Seq_in_index . '"'
					. ' Column_name="' . $key->Column_name . '"'
					. ' Collation="' . $key->Collation . '"'
					. ' Null="' . $key->Null . '"'
					. ' Index_type="' . $key->Index_type . '"'
					. ' Comment="' . htmlspecialchars($key->Comment) . '"'
					. ' Index_comment="' . htmlspecialchars($key->Index_comment) . '"'
					. ' />';
			}

			$buffer[] = '  </table_structure>';

			/*
			 * Table data
			 */

			if (!$this->options->withData)
			{
				continue;
			}

			$query->clear()
				->from($this->database->quoteName($table))
				->select('*');

			$rows = $this->database->setQuery($query)->loadObjectList();

			$buffer[] = '  <table_data name="' . $table . '">';

			foreach ($rows as $row)
			{
				$buffer[] = '   <row>';

				foreach ($row as $fieldName => $fieldValue)
				{
					$buffer[] = '    <field'
						. ' name="' . $fieldName . '">'
						. htmlspecialchars($fieldValue)
						. '</field>';
				}

				$buffer[] = '   </row>';
			}

			$buffer[] = '  </table_data>';
		}

		return $buffer;
	}

	/**
	 * Checks if all data and options are in order prior to exporting.
	 *
	 * @since   1.0
	 * @throws  \Exception if an error is encountered.
	 * @return  $this
	 */
	public function check()
	{
		// Check if the db connector has been set.
		if (!($this->database instanceof MysqliDriver))
		{
			throw new \Exception('JPLATFORM_ERROR_DATABASE_CONNECTOR_WRONG_TYPE');
		}

		// Check if the tables have been specified.
		if (empty($this->tables))
		{
			throw new \Exception('JPLATFORM_ERROR_NO_TABLES_SPECIFIED');
		}

		return $this;
	}
}
