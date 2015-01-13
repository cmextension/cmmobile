<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * View class for dashboard.
 *
 * @since  1.0.0
 */
class CMMobileViewDashboard extends JViewLegacy
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
		JToolBarHelper::title(JText::_('COM_CMMOBILE_TOOLBAR_DASHBOARD'), 'dashboard');

		if (JFactory::getUser()->authorise('core.admin', 'com_cmmobile'))
		{
			JToolbarHelper::preferences('com_cmmobile');
		}
	}
}
