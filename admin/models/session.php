<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Model class for session.
 *
 * @since  1.0.0
 */
class CMMobileModelSession extends JModelAdmin
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.0.0
	 */
	public function getTable($type = 'Session', $prefix = 'CMMobileTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return false;
	}

	/**
	 * Method to delete expired session records.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.0.0
	 */
	public function deleteExpired()
	{
		$sessionLifetime = JComponentHelper::getParams('com_cmmobile')->get('session_lifetime', 24);
		$now = JFactory::getDate()->toSql();

		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__cmmobile_sessions'))
			->where('DATE_ADD(created, INTERVAL ' . $sessionLifetime . ' HOUR) < ' . $db->quote($now));

		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}
}
