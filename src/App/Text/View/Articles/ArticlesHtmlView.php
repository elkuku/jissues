<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\View\Articles;

use App\Text\Model\ArticlesModel;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * Users view class for the Users component
 *
 * @since  1.0
 */
class ArticlesHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     ArticlesModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer->set('items', $this->model->getItems());

		return parent::render();
	}
}
