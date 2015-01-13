<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Table class for session.
 *
 * @since  1.0.0
 */
class CMMobileTableSession extends JTable
{
	/**
	 * Constructor.
	 *
	 * @param   JDatabaseDriver  &$db  Database driver object.
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__cmmobile_sessions', 'token', $db);
	}
}
