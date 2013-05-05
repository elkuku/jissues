<?php
/**
 * @package     JTracker\View
 * @subpackage  Renderer
 *
 * @copyright   Copyright (C) 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View\Renderer;

class Mustache extends \Mustache_Engine
{
	private $config = array(
		'templates_base_dir'	=> '/templates',
		'partials_base_dir'		=> '/partials'
	);
	private $data;
	private $template;

	/**
	 * Constructor
	 *
	 * @param  array  $config  The array of configuration parameters
	 */
	public function __construct($config = array())
	{
		// Merge the config.
		$this->config = array_merge($this->config, $config);

		parent::__construct(array(
			'loader'			=> new \Mustache_Loader_FilesystemLoader($this->config['templates_base_dir']),
			'partials_loader'	=> new \Mustache_Loader_FilesystemLoader($this->config['partials_base_dir']),
			)
		);
	}

	/**
	 * Set the data.
	 *
	 * @param   mixed    $key     The variable name or an array of variable names with values.
	 * @param   mixed    $value   The value.
	 *
	 * @return  object   Instance of this class
	 */
	public function set($key, $value)
	{
		if (is_array($key))
		{
			foreach ($key as $k => $v) $this->set($k, $v);
		}
		else
		{
			$this->data[$key] = $value;
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
	 * @param   mixed   $data      The data to pass to the template
	 *
	 * @return  string  compiled HTML
	 */
	public function render($template = '', $data = '')
	{
		if (!empty($template))
		{
			$this->template = $template;
		}

		if (!empty($data))
		{
			$this->data = $data;
		}

		return $this->load()->render($this->data);
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
		return $this->loadTemplate($this->template);
	}
}
