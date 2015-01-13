<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/helpers/session.php';

$controller = JControllerLegacy::getInstance('CMMobile');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
