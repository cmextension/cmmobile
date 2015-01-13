<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * View for response.
 *
 * @package  CMMobile
 *
 * @since    1.0.0
 */
class CMMobileViewResponse extends JViewLegacy
{
	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();

		// Ignore request the request comes directly to the view.
		if (!isset($this->response))
		{
			$app->close();
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode("\n", $errors), 'error');

			$this->response = new JResponseJson(null, null, true);
		}

		echo $this->response;
	}
}
