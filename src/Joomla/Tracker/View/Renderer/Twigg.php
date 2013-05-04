<?php
/**
 * @package     JTracker\View
 * @subpackage  Renderer
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View\Renderer;

// Check for Composer autoloader
if (!class_exists('\Twig_Autoloader'))
{
	require_once JPATH_BASE . '/vendor/twig/twig/lib/Twig/Autoloader.php';
}
\Twig_Autoloader::register();

class Twigg extends \Twig_Environment
{
	private $_config = array(
		'themes_base_dir' => 'templates/',
		'default_theme' => 'default/',
		'template_file_ext' => '.tpl',
		'delimiters' => array(
				'tag_comment' 	=> array('{#', '#}'),
				'tag_block'   	=> array('{%', '%}'),
				'tag_variable'	=> array('{{', '}}')
			),
		'environment' => array()
		);
	private $_data = array();
	private $_templateLocations = array();
	private $_theme;
	private $_template;
	private $_twigLoader;

	/**
	 * Constructor
	 */
	public function __construct(array $config = array())
	{
		// Merge the config.
		$this->_config = array_replace($this->_config, $config);

		// Set the template location.
		$this->_setTemplateLocations($this->_config['default_theme']);

		try
		{
			$this->_twigLoader = new \Twig_Loader_Filesystem($this->_templateLocations);
		}
		catch (\Twig_Error_Loader $e)
		{
			echo $e->getRawMessage();
		}

		parent::__construct($this->_twigLoader, $this->_config['environment']);

		$this->setLexer(new \Twig_Lexer($this, $this->_config['delimiters']));
	}

	/**
	 * Set the data.
	 *
	 * @param   mixed    $key     The variable name or an array of variable names with values.
	 * @param   mixed    $value   The value.
	 * @param   boolean  $global  Is this a global variable?
	 *
	 * @return  object   Instance of this class
	 */
	public function set($key, $value, $global = false)
	{
		if (is_array($key))
		{
			foreach ($key as $k => $v) $this->set($k, $v, $global);
		}
		else
		{
			if ($global)
			{
				$this->addGlobal($key, $value);
			}
			else
			{
				$this->_data[$key] = $value;
			}
		}

		return $this;
	}

	/**
	 * Unset a particular variable.
	 *
	 * @param   mixed  $key  The variable name
	 *
	 * @return  object  Instance of this class
	 */
	public function unset_data($key)
	{
		if (array_key_exists($key, $this->_data))
		{
			unset($this->_data[$key]);
		}

		return $this;
	}

	/**
	 * Set the template.
	 *
	 * @param   string  $name  The name of the template file
	 *
	 * @return  object  Instance of this class
	 */
	public function setTemplate($name)
	{
		$this->_template = $name;

		return $this;
	}

	/**
	 * Render and return compiled HTML.
	 *
	 * @param   string  $template  The template file name
	 * @param   array   $data      An array of data to pass to the template
	 *
	 * @return  string  compiled HTML
	 */
	public function render($template = '', array $data = array())
	{
		if (!empty($template))
		{
			$this->setTemplate($template);
		}

		if (!empty($data))
		{
			$this->_data = $data;
		}

		try
		{
			return $this->_load()->render($this->_data);
		}
		catch (\Twig_Error_Loader $e)
		{
			echo $e->getRawMessage();
		}
	}

	/**
	 * Display the compiled HTML content.
	 *
	 * @param   string  $template  The template file name
	 * @param   array   $data      An array of data to pass to the template
	 *
	 * @return  void
	 */
	public function display($template = '', array $data = array())
	{
		if (!empty($template))
		{
			$this->setTemplate($template);
		}

		if (!empty($data))
		{
			$this->_data = $data;
		}

		try
		{
			$this->_load()->display($this->_data);
		}
		catch (\Twig_Error_Loader $e)
		{
			echo $e->getRawMessage();
		}
	}

	/**
	 * Get the current theme name.
	 *
	 * @return  string  The name of the currently loaded theme
	 */
	public function getTheme()
	{
		return $this->_theme;
	}

	/**
	 * Get the current template name.
	 *
	 * @return  string  The name of the currently loaded template file (without the extension)
	 */
	public function getTemplate()
	{
		return $this->_template;
	}

	/**
	 * Load the template and return an output object.
	 *
	 * @return  object  output
	 */
	private function _load()
	{
		return $this->loadTemplate($this->_template . $this->_config['template_file_ext']);
	}

	/**
	 * Set the template locations.
	 *
	 * @param   string  $theme  The name of theme to load
	 *
	 * @return  void
	 */
	private function _setTemplateLocations($theme)
	{
		$this->_templateLocations[] = $this->_config['themes_base_dir'] . $theme;

		// Reset the paths if needed.
		if (is_object($this->_twigLoader))
		{
			$this->_twigLoader->setPaths($this->_templateLocations);
		}
	}
}
