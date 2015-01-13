<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Model class for list of sessions.
 *
 * @since  1.0.0
 */
class CMMobileModelSessions extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'token', 'a.token',
				'userid', 'a.userid',
				'username', 'a.username',
				'created', 'a.created',
				'expired'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state');
		$this->setState('filter.state', $state);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_cmmobile');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.created', 'desc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 * 
	 * @since   1.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		$sessionLifetime = $this->state->params->get('session_lifetime', 24);
		$now = JFactory::getDate()->toSql();

		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.token, a.userid, a.username, a.created'
			)
		);

		$query->select('DATE_ADD(a.created, INTERVAL ' . $sessionLifetime . ' HOUR) AS expired');

		$query->from($db->quoteName('#__cmmobile_sessions') . ' AS a');

		// Filter by searching.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where(
					$db->quoteName('a.token') . ' LIKE ' . $search . ' OR ' .
					$db->quoteName('a.username') . ' LIKE ' . $search
				);
		}

		// Filter by state.
		$state = $this->getState('filter.state');

		if ($state == 'active')
		{
			$query->where('DATE_ADD(a.created, INTERVAL ' . $sessionLifetime . ' HOUR) > ' . $db->quote($now));
		}
		elseif ($state == 'expired')
		{
			$query->where('DATE_ADD(a.created, INTERVAL ' . $sessionLifetime . ' HOUR) < ' . $db->quote($now));
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}
}
