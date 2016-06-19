<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

use ElKuKu\Crowdin\Languagefile;

use g11n\Support\ExtensionHelper;

use Joomla\Filter\OutputFilter;

use JTracker\Helper\LanguageHelper;

/**
 * Class for pushing translation files to Crowdin.
 *
 * @since  1.0
 */
class Crowdin extends Update
{
	/**
	 * Array containing application languages to retrieve translations for
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $languages = [];

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Push language files to Crowdin project.');
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle(g11n3t('Upload Translation Files'));

		$this->languages = $this->getApplication()->get('languages');

		// Remove English from the language array
		unset($this->languages[0]);

		$this->logOut(g11n3t('Start importing translations.'))
			->setupLanguageProvider()
			->fetchTranslations()
			->out()
			->logOut(g11n3t('Finished.'));
	}

	/**
	 * Fetch translations.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	private function fetchTranslations()
	{
		LanguageHelper::addDomainPaths();

		defined('JDEBUG') || define('JDEBUG', 0);

		// Process CLI files
		$this->receiveFiles('cli', 'CLI');

		// Process core files
		$this->receiveFiles('JTracker', 'Core');

		// Process core JS files
		$this->receiveFiles('JTracker.js', 'CoreJS');

		// Process template files
		$this->receiveFiles('JTracker', 'Template');

		// Process app files
		/* @type \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(JPATH_ROOT . '/src/App') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$this->receiveFiles($fileInfo->getFilename(), 'App');
		}

		return $this;
	}

	/**
	 * Receives language files from the provider
	 *
	 * @param   string  $extension  The extension to process
	 * @param   string  $domain     The domain of the extension
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	private function receiveFiles($extension, $domain)
	{
		$alias = OutputFilter::stringUrlUnicodeSlug($extension . ' ' . $domain);

		$this->out(sprintf('Processing: %s %s... ', $domain, $extension), false);

		$scopePath     = ExtensionHelper::getDomainPath($domain);
		$extensionPath = ExtensionHelper::getExtensionLanguagePath($extension);

		// Fetch the file for each language and place it in the file tree
		foreach ($this->languages as $language)
		{
			if ('en-GB' == $language)
			{
				continue;
			}

			$this->out($language . '... ', false);

			// Write the file
			$path = $scopePath . '/' . $extensionPath . '/' . $language . '/' . $language . '.' . $extension . '.po';

			switch ($this->languageProvider)
			{
				case 'crowdin':
					$this->crowdin->translation->upload(new Languagefile($path, $alias . '_en.po'), LanguageHelper::getCrowdinLanguageTag($language));
					break;
			}
		}

		$this->outOK();
	}
}