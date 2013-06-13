<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Update;

use App\Debug\Logger\CallbackLogger;

use CliApp\Application\TrackerApplication;
use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;

/**
 * Class Update
 *
 * @since  1.0
 */
class Update extends TrackerCommand
{
	private $logCount = 0;

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

		$this->description = 'Update the database.';

		$this->addOption(
			new TrackerCommandOption(
				'withData', '',
				'Update with data. (can be used to install a SQLite db)'
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @throws \RuntimeException
	 * @since   1.0
	 * @return  void
	 */
	public function execute()
	{
		$this->application->outputTitle('DB Update');

		$dbType = $this->application->get('database.driver');

		$className = '\\JTracker\\Database\\' . ucfirst($dbType) . '\\' . ucfirst($dbType) . 'Importer';

		if (false == class_exists($className))
		{
			throw new \UnexpectedValueException('No importer found for database: ' . $dbType);
		}

		/* @type \JTracker\Database\AbstractDatabaseImporter $importer */
		$importer = new $className;

		// @todo ugly path ../
		$path = realpath(__DIR__ . '/../../../..') . '/etc/sql.xml';

		$xml = file_get_contents($path);

		if (!$xml)
		{
			throw new \RuntimeException('Invalid file: ' . $path);
		}

		$importer
			->setDbo($this->application->getDatabase())
			->setSource($xml)
			->withStructure()
			->withData($this->application->input->get('withData') ? true : false)
			->setLogger(new CallbackLogger(array($this, 'log')))
			->process();

		$this
			->out()
			->out(sprintf('<b>%d</b> actions executed.', $this->logCount))
			->out()
			->out('Finished =;)');
	}

	/**
	 * Log a message (to stdout ;).
	 *
	 * @param   string  $level    The log level.
	 * @param   string  $message  The log message.
	 *
	 * @return $this
	 */
	public function log($level, $message)
	{
		$this->out()
			->out($message);

		$this->logCount ++;

		return $this;
	}
}
