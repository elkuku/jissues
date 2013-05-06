<?php
/**
 * @package     JTracker\View
 * @subpackage  Renderer
 *
 * @copyright   Copyright (C) 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View\Renderer;

class Twigg extends \Twig_Environment
{
	private $config = array(
		'themes_base_dir'   => 'templates/',
		'default_theme'     => 'default/',
		'template_file_ext' => '.tpl',
		'twig_cache_dir'    => 'cache/twig/',
		'delimiters'        => array(
			'tag_comment'  => array('{#', '#}'),
			'tag_block'    => array('{%', '%}'),
			'tag_variable' => array('{{', '}}')
		),
		'environment'       => array()
	);
	private $data = array();
	private $templateLocations = array();
	private $theme;
	private $template;
	private $twigLoader;

	/**
	 * Constructor
	 *
	 * @param  array  $config  The array of configuration parameters
	 */
	public function __construct($config = array())
	{
		// Merge the config.
		$this->config = array_merge($this->config, $config);

		// Set the template location.
		$this->setTemplateLocations($this->config['default_theme']);

		try
		{
			$this->twigLoader = new \Twig_Loader_Filesystem($this->templateLocations);
		}
		catch (\Twig_Error_Loader $e)
		{
			echo $e->getRawMessage();
		}

		parent::__construct($this->twigLoader, $this->config['environment']);

		// Set lexer.
		$this->setLexer(new \Twig_Lexer($this, $this->config['delimiters']));
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
				$this->data[$key] = $value;
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
		if (array_key_exists($key, $this->data))
		{
			unset($this->data[$key]);
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
		$this->template = $name;

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
			$this->template = $template;
		}

		if (!empty($data))
		{
			$this->data = $data;
		}

		try
		{
			return $this->load()->render($this->data);
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
			$this->template = $template;
		}

		if (!empty($data))
		{
			$this->data = $data;
		}

		try
		{
			$this->load()->display($this->data);
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
		return $this->theme;
	}

	/**
	 * Get the current template name.
	 *
	 * @return  string  The name of the currently loaded template file (without the extension)
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Load the template and return an output object.
	 *
	 * @return  object  output
	 */
	private function load()
	{
		return $this->loadTemplate($this->template . $this->config['template_file_ext']);
	}

	/**
	 * Set the template locations.
	 *
	 * @param   string  $theme  The name of theme to load
	 *
	 * @return  void
	 */
	private function setTemplateLocations($theme)
	{
		$this->templateLocations[] = $this->config['themes_base_dir'] . $theme;

		// Reset the paths if needed.
		if (is_object($this->twigLoader))
		{
			$this->twigLoader->setPaths($this->templateLocations);
		}
	}
}
