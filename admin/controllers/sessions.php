<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Controller for list of sessions.
 *
 * @since  1.0.0
 */
class CMMobileControllerSessions extends JControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $text_prefix = 'COM_CMMOBILE_SESSION';

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.0.0
	 */
	public function getModel($name = 'Session', $prefix = 'CMMobileModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Remove a session.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function delete()
	{
		// Check for request forgeries.
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$cid = JFactory::getApplication()->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			JLog::add(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Remove the items.
			if ($model->delete($cid))
			{
				$this->setMessage(JText::plural($this->text_prefix . '_N_ITEMS_DELETED', count($cid)));
			}
			else
			{
				$this->setMessage($model->getError(), 'error');
			}
		}
		// Invoke the postDelete method to allow for the child class to access the model.
		$this->postDeleteHook($model, $cid);

		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Remove all expired sessions.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function deleteExpired()
	{
		// Check for request forgeries.
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get the model.
		$model = $this->getModel();

		// Remove the items.
		if ($model->deleteExpired())
		{
			$this->setMessage(JText::_('COM_CMMOBILE_SESSION_EXPIRED_SESSIONS_DELETED'));
		}
		else
		{
			$this->setMessage($model->getError(), 'error');
		}

		// Invoke the postDelete method to allow for the child class to access the model.
		$this->postDeleteHook($model, $cid);

		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Method to provide child classes the opportunity to process after the delete task.
	 *
	 * @param   JModelLegacy  $model  The model for the component
	 * @param   mixed         $ids    array of ids deleted.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function postDeleteHook(JModelLegacy $model, $ids = null)
	{
	}
}
