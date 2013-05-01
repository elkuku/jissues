<?php
/**
 * @package     JTracker\View
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View;

use Joomla\Model\ModelInterface;
use Joomla\View\AbstractView;

/**
 * Tracker HTML View Class
 *
 * @since  1.0
 */
abstract class AbstractTrackerHtmlView extends AbstractView
{
	/**
	 * The view layout.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $layout = 'default.index';

	/**
	 * The view template engine.
	 *
	 * @var    \Twiggy
	 * @since  1.0
	 */
	protected $tmplEngine = null;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   ModelInterface  $model  The model object.
	 *
	 * @since   1.0
	 */
	public function __construct(ModelInterface $model)
	{
		parent::__construct($model);

		// Load the template engine.
		require JPATH_BASE . '/libraries/Twiggy.php';
		$this->tmplEngine = new \Twiggy;
	}

	/**
	 * Magic toString method that is a proxy for the render method.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Method to escape output.
	 *
	 * @param   string  $output  The output to escape.
	 *
	 * @return  string  The escaped output.
	 *
	 * @see     ViewInterface::escape()
	 * @since   1.0
	 */
	public function escape($output)
	{
		// Escape the output.
		return htmlspecialchars($output, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Method to get the view layout.
	 *
	 * @return  string  The layout name.
	 *
	 * @since   1.0
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function render()
	{
		return $this->tmplEngine->template($this->getLayout())->display();
	}

	/**
	 * Method to set the view layout.
	 *
	 * @param   string  $layout  The layout name.
	 *
	 * @return  AbstractTrackerHtmlView  Method supports chaining.
	 *
	 * @since   1.0
	 */
	public function setLayout($layout)
	{
		$this->layout = $layout;

		return $this;
	}
}