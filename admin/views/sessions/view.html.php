<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * View class for list of sessions.
 *
 * @since  1.0.0
 */
class CMMobileViewSessions extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

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
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar();
		$this->menu = JHtml::_('cmmobile.addMenu');
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function addToolbar()
	{
		$state	= $this->get('State');
		$user	= JFactory::getUser();

		JToolBarHelper::title(JText::_('COM_CMMOBILE_TOOLBAR_SESSIONS'), 'clock');

		if ($user->authorise('core.delete', 'com_cmmobile'))
		{
			JToolbarHelper::deleteList('', 'sessions.delete');
		}

		if ($user->authorise('core.delete', 'com_cmmobile'))
		{
			JToolbarHelper::custom('sessions.deleteExpired', 'delete.png', 'delete_f2.png', 'COM_CMMOBILE_SESSION_DELETE_EXPIRED', false);
		}

		if ($user->authorise('core.admin', 'com_cmmobile'))
		{
			JToolbarHelper::preferences('com_cmmobile');
		}
	}
}
