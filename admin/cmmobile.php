<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

if (!JFactory::getUser()->authorise('core.manage', 'com_cmmobile'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Load helpers.
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/cmmobile.php';
JHtml::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html');

$controller = JControllerLegacy::getInstance('CMMobile');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
