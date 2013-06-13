<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Dump;

use CliApp\Application\TrackerApplication;
use CliApp\Command\TrackerCommand;

use JTracker\Database\Mysqli\MysqliExporter;

/**
 * Class Dump
 *
 * @since  1.0
 */
class Dump extends TrackerCommand
{
	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerApplication $application)
	{
		parent::__construct($application);

		$this->description = 'Create a database dump.';

		/*		$this->addOption(
					new TrackerCommandOption(
						'reinstall', '',
						'Reinstall the application (without confirmation)'
					)
				);
		*/
	}

	/**
	 * Execute the command.
	 *
	 * @since   1.0
	 * @throws \RuntimeException
	 * @return  $this
	 */
	public function execute()
	{
		$this->application->outputTitle('Database Dump');

		$database = $this->application->getDatabase();

		$dbType = $this->application->get('database.driver');

		$exporter = null;

		switch ($dbType)
		{
			case 'mysqli' :
				$exporter = new MysqliExporter;
			break;

			default :
				throw new \RuntimeException('Unsupported database (yet)...: ' . $dbType);
			break;
		}

		$dump = $exporter
			->setDbo($database)
			->setTables($database->getTableList())
			->withData()
			->export();

		// @todo ugly path ../
		$path = realpath(__DIR__ . '/../../../..') . '/etc/sql.xml';

		file_put_contents($path, $dump);

		$this
			->out()
			->out('The file has been written to:')
			->out('<b>' . $path . '</b>')
			->out()
			->out('Finished =;)');

		return $this;
	}
}
