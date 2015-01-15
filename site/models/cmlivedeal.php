<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Model for CM Live Deal component.
 *
 * @package  CMMobile
 *
 * @since    1.0.0
 */
class CMMobileModelCMLiveDeal extends JModelLegacy
{
	/**
	 * Build the query for getting deals.
	 *
	 * @return  JDatabaseQuery   A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0.0
	 */
	private function getDealsQuery()
	{
		$method = JComponentHelper::getParams('com_cmmobile')->get('method', 'post');
		$jinput = JFactory::getApplication()->input;
		$params = JComponentHelper::getParams('com_cmlivedeal');

		$integration = $params->get('membership_integration', '');

		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$now = JFactory::getDate()->toSql();

		// Select the required fields from the table.
		$query->select(
			$db->quoteName(
				array(
					'a.id', 'a.title', 'a.description', 'a.fine_print',
					'a.user_id', 'a.image_id', 'a.starting_time'
				)
			)
		);

		$query->from($db->quoteName('#__cmlivedeal_deals') . ' AS a')
			->where($db->quoteName('a.published') . ' = ' . $db->quote('1'))
			->where($db->quoteName('a.approved') . ' = ' . $db->quote('1'))
			->where($db->quoteName('a.starting_time') . ' <= ' . $db->quote($now));

		// Get merchant's info.
		$query->select($db->quoteName('m.name') . ' AS merchant_name')
			->select($db->quoteName('m.about') . ' AS merchant_about')
			->select($db->quoteName('m.address') . ' AS merchant_address')
			->select($db->quoteName('m.latitude') . ' AS merchant_latitude')
			->select($db->quoteName('m.longitude') . ' AS merchant_longitude')
			->select($db->quoteName('m.phone') . ' AS merchant_phone')
			->select($db->quoteName('m.website') . ' AS merchant_website')
			->select($db->quoteName('m.facebook') . ' AS merchant_merchant_facebook')
			->select($db->quoteName('m.twitter') . ' AS merchant_twitter')
			->select($db->quoteName('m.pinterest') . ' AS merchant_pinterest')
			->select($db->quoteName('m.google_plus') . ' AS merchant_google_plus')
			->join(
				'LEFT',
				$db->quoteName('#__cmlivedeal_merchants') . ' AS m ON ' . $db->quoteName('m.user_id') . ' = ' . $db->quoteName('a.user_id')
			);

		if ($integration == '')
		{
			$query->select($db->quoteName('a.ending_time') . ' AS ending_time')
				->where($db->quoteName('ending_time') . ' >= ' . $db->quote($now));
		}
		else
		{
			$subQuery1 = CMLiveDealHelper::buildQueryUserActivePlans('a.user_id', $integration);

			$subQuery2 = $db->getQuery(true)
				->select($db->quoteName('p.length'))
				->from($db->quoteName('#__cmlivedeal_plans') . ' AS p')
				->where($db->quoteName('p.plan_id') . ' IN (' . $subQuery1->__toString() . ')')
				->order($db->quoteName('p.ordering') . ' DESC');

			$query->select('DATE_ADD(a.starting_time,INTERVAL (' . $subQuery2->__toString() . ' LIMIT 1) DAY) AS ending_time');
			$query->having('ending_time >= ' . $db->quote($now));
		}

		// Filter by search in title.
		$keyword = $jinput->$method->get('keyword', '', 'string');

		if (!empty($keyword))
		{
			$keyword = $db->quote('%' . $db->escape($keyword, true) . '%');
			$query->where(
				$db->quoteName('a.title') . ' LIKE ' . $keyword . ' OR ' .
				$db->quoteName('a.description') . ' LIKE ' . $keyword
			);
		}

		// Filter by category.
		$categoryId = $jinput->$method->get('category', 0, 'uint');

		if ($categoryId > 0)
		{
			$cats = array($categoryId);

			CMLiveDealHelper::getChildrenCategories($categoryId, $cats);

			$query->where($db->quoteName('a.category_id') . ' IN (' . implode(',', $cats) . ')');
		}

		// Filter by city.
		$cityId = $jinput->$method->get('city', 0, 'int');

		if ($cityId > 0 || $cityId == -1)
		{
			$distance = 0;
			$latitude = null;
			$longitude = null;

			if ($cityId > 0)
			{
				$cityQuery = $db->getQuery(true)
					->select($db->quoteName(array('id', 'latitude', 'longitude', 'radius')))
					->from($db->quoteName('#__cmlivedeal_cities'))
					->where($db->quoteName('id') . ' = ' . $db->quote($cityId));
				$city = $db->setQuery($cityQuery)->loadObject();

				if (!empty($city->id))
				{
					$distance = $city->radius;
					$latitude = $city->latitude;
					$longitude = $city->longitude;
				}
			}
			else
			{
				$latitude = $jinput->$method->get('latitude', null, 'float');
				$longitude = $jinput->$method->get('longitude', null, 'float');
				$distance = $params->get('search_radius', 5, 'uint');
			}

			if ($latitude !== null && $longitude !== null && $distance > 0)
			{
				// Earth radius is about 6,371 kilometers (3,959 miles).
				$earthRadius = 6371;

				$query->select("($earthRadius * acos(cos(radians($latitude)) * cos(radians(m.latitude)) * cos(radians(m.longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(m.latitude)))) AS distance")
					->having("distance <= $distance");
			}
		}

		// Add the list ordering clause.
		$orderCol = 'a.starting_time';
		$orderDirn = 'DESC';
		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	/**
	 * Get deal quantity.
	 *
	 * @return  integer  Number of total deals.
	 *
	 * @since   1.0.0
	 */
	public function getTotalDeals()
	{
		jimport('joomla.filesystem.file');

		$helperFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmlivedeal.php';
		$merchantFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmldmerchant.php';

		if (JFile::exists($helperFilePath) && JFile::exists($merchantFilePath))
		{
			require_once $helperFilePath;
			require_once $merchantFilePath;
		}
		else
		{
			return array();
		}

		$db = $this->getDbo();

		$query = $this->getDealsQuery();
		$query = 'SELECT COUNT(*) FROM (' . $query->__toString() . ') a';

		$db->setQuery($query);

		try
		{
			$count = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			$count = 0;
		}

		return $count;
	}

	/**
	 * Get deals.
	 *
	 * @return  array   Array of deal objects.
	 *
	 * @since   1.0.0
	 */
	public function getDeals()
	{
		jimport('joomla.filesystem.file');

		$helperFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmlivedeal.php';
		$merchantFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmldmerchant.php';

		if (JFile::exists($helperFilePath) && JFile::exists($merchantFilePath))
		{
			require_once $helperFilePath;
			require_once $merchantFilePath;
		}
		else
		{
			return array();
		}

		$db = $this->getDbo();
		$method = JComponentHelper::getParams('com_cmmobile')->get('method', 'post');
		$jinput = JFactory::getApplication()->input;
		$params = JComponentHelper::getParams('com_cmlivedeal');

		$query = $this->getDealsQuery();

		$defaultLimit = $params->get('default_pagination', 20);
		$start = $jinput->$method->get('start', 0, 'uint');
		$limit = $jinput->$method->get('limit', $defaultLimit, 'uint');

		$db->setQuery($query, $start, $limit);

		try
		{
			$deals = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			$deals = array();
		}

		if (!empty($deals))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/tables', 'CMLiveDealTable');
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/models', 'CMLiveDealModel');

			$imageModel = JModelLegacy::getInstance('Image', 'CMLiveDealModel');

			$imagePath = JComponentHelper::getParams('com_media')->get('image_path', 'images');
			$merchantPath = $params->get('image_folder');

			foreach ($deals as &$deal)
			{
				$deal->thumbnail = '';
				$folderPath = JPath::clean(JPATH_ROOT . '/' . $imagePath . '/' . $merchantPath . '/' . $deal->user_id);
				$image = $imageModel->getItem($deal->image_id);

				if (!empty($image->id))
				{
					$filePath = $folderPath . '/' . $image->file_name;

					if (JFile::exists($filePath))
					{
						$deal->thumbnail = JUri::root() . $imagePath . '/' . $merchantPath . '/' . $deal->user_id . '/' . $image->file_name;
					}
				}

				unset($deal->user_id);
				unset($deal->image_id);
			}
		}

		return $deals;
	}

	/**
	 * Get categories.
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public function getCategories()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(
			$db->quoteName(
				array('a.id', 'a.title', 'a.level')
			)
		);

		$query->from($db->quoteName('#__categories') . ' AS a')
			->where($db->quoteName('a.published') . ' = ' . $db->quote('1'))
			->where($db->quoteName('a.parent_id') . ' > ' . $db->quote('0'))
			->where($db->quoteName('a.extension') . ' = ' . $db->quote('com_cmlivedeal'));

		$query->order($db->escape('a.lft'));

		$db->setQuery($query);

		try
		{
			$categories = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			return array();
		}

		foreach ($categories as &$category)
		{
			$repeat = ($category->level - 1 >= 0) ? $category->level - 1 : 0;
			$category->title = str_repeat('- ', $repeat) . $category->title;
			unset($category->level);
		}

		return $categories;
	}

	/**
	 * Get cities.
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public function getCities()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'name')))
			->from($db->quoteName('#__cmlivedeal_cities'))
			->where($db->quoteName('published') . ' = ' . $db->quote(1))
			->order($db->quoteName('name') . ' ASC');
		$db->setQuery($query);

		try
		{
			$cities = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			return array();
		}

		return $cities;
	}

	/**
	 * Build the query for getting coupons.
	 *
	 * @return  JDatabaseQuery   A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0.0
	 */
	private function getCouponsQuery()
	{
		$jinput = JFactory::getApplication()->input;
		$params = JComponentHelper::getParams('com_cmmobile');
		$method = $params->get('method', 'post');
		$token = $jinput->$method->get('token', '', 'alnum');

		// Get user ID from session..
		$session = CMMobileSession::getSession($token);

		if (!empty($session->userid))
		{
			$userId = (int) $session->userid;

			// Create a new query object.
			$db = $this->getDbo();
			$query = $db->getQuery(true);
			$now = JFactory::getDate()->toSql();

			// Select the required fields from the table.
			$query->select(
				$db->quoteName(
					array('a.id','a.code', 'a.created')
				)
			);

			$query->from($db->quoteName('#__cmlivedeal_coupons') . ' AS a')
				->where($db->quoteName('a.user_id') . ' = ' . $db->quote($userId));

			// Get deal's info.
			$query->select($db->quoteName('d.title') . ' AS deal_name')
				->select($db->quoteName('d.ending_time') . ' AS deal_ending_time')
				->select($db->quoteName('d.fine_print') . ' AS deal_fine_print')
				->select(
					'IF(' .
						$db->quoteName('d.ending_time') . ' <= ' . $db->quote($now) .
						', ' . $db->quote('true') . ', ' . $db->quote('false') .
					') AS deal_expired')
				->join(
					'LEFT',
					$db->quoteName('#__cmlivedeal_deals') . ' AS d ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('a.deal_id')
				);

			// Get merchant's info.
			$query->select($db->quoteName('m.name') . ' AS merchant_name')
				->select($db->quoteName('m.about') . ' AS merchant_about')
				->select($db->quoteName('m.address') . ' AS merchant_address')
				->select($db->quoteName('m.latitude') . ' AS merchant_latitude')
				->select($db->quoteName('m.longitude') . ' AS merchant_longitude')
				->select($db->quoteName('m.phone') . ' AS merchant_phone')
				->select($db->quoteName('m.website') . ' AS merchant_website')
				->select($db->quoteName('m.facebook') . ' AS merchant_merchant_facebook')
				->select($db->quoteName('m.twitter') . ' AS merchant_twitter')
				->select($db->quoteName('m.pinterest') . ' AS merchant_pinterest')
				->select($db->quoteName('m.google_plus') . ' AS merchant_google_plus')
				->join(
					'LEFT',
					$db->quoteName('#__cmlivedeal_merchants') . ' AS m ON ' . $db->quoteName('m.user_id') . ' = ' . $db->quoteName('d.user_id')
				);

			// Filter by search in title.
			$keyword = $jinput->$method->get('keyword', '', 'string');

			if (!empty($keyword))
			{
				$keyword = $db->quote('%' . $db->escape($keyword, true) . '%');
				$query->where(
					$db->quoteName('m.name') . ' LIKE ' . $keyword . ' OR ' .
					$db->quoteName('m.address') . ' LIKE ' . $keyword . ' OR ' .
					$db->quoteName('m.phone') . ' LIKE ' . $keyword . ' OR ' .
					$db->quoteName('d.title') . ' LIKE ' . $keyword . ' OR ' .
					$db->quoteName('d.description') . ' LIKE ' . $keyword . ' OR ' .
					$db->quoteName('a.code') . ' LIKE ' . $keyword
				);
			}

			// Filter by status.
			$status = $jinput->$method->get('status', -1, 'int');

			if ($status == 1)
			{
				$query->where($db->quoteName('d.ending_time') . ' > ' . $db->quote($now));
			}
			elseif ($status == 0)
			{
				$query->where($db->quoteName('d.ending_time') . ' <= ' . $db->quote($now));
			}

			$orderCol = 'a.id';
			$orderDirn = 'DESC';
			$query->order($db->escape($orderCol . ' ' . $orderDirn));

			return $query;
		}

		return null;
	}

	/**
	 * Get coupon quantity.
	 *
	 * @return  integer  Number of total coupons.
	 *
	 * @since   1.0.0
	 */
	public function getTotalCoupons()
	{
		jimport('joomla.filesystem.file');

		$helperFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmlivedeal.php';
		$merchantFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmldmerchant.php';

		if (JFile::exists($helperFilePath) && JFile::exists($merchantFilePath))
		{
			require_once $helperFilePath;
			require_once $merchantFilePath;
		}
		else
		{
			return array();
		}

		$db = $this->getDbo();

		$query = $this->getCouponsQuery();
		$query = 'SELECT COUNT(*) FROM (' . $query->__toString() . ') a';

		$db->setQuery($query);

		try
		{
			$count = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			$count = 0;
		}

		return $count;
	}

	/**
	 * Get coupons.
	 *
	 * @return  array   Array of coupon objects.
	 *
	 * @since   1.0.0
	 */
	public function getCoupons()
	{
		jimport('joomla.filesystem.file');

		$helperFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmlivedeal.php';
		$merchantFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmldmerchant.php';

		if (JFile::exists($helperFilePath) && JFile::exists($merchantFilePath))
		{
			require_once $helperFilePath;
			require_once $merchantFilePath;
		}
		else
		{
			return array();
		}

		$query = $this->getCouponsQuery();

		if ($query != null)
		{
			$db = $this->getDbo();
			$method = JComponentHelper::getParams('com_cmmobile')->get('method', 'post');
			$jinput = JFactory::getApplication()->input;
			$params = JComponentHelper::getParams('com_cmlivedeal');

			$defaultLimit = $params->get('default_pagination', 20);
			$start = $jinput->$method->get('start', 0, 'uint');
			$limit = $jinput->$method->get('limit', $defaultLimit, 'uint');

			$db->setQuery($query, $start, $limit);

			try
			{
				$coupons = $db->loadObjectList();
			}
			catch (RuntimeException $e)
			{
				$coupons = array();
			}
		}
		else
		{
			$coupons = array();
		}

		if (!empty($coupons))
		{
			$loadedProfile = array();
			$attributes = array('name', 'address', 'latitude', 'longitude', 'phone');
		}

		return $coupons;
	}

	/**
	 * Get and prepare data for a specific coupon.
	 *
	 * @param   string  $couponCode  Coupon code.
	 *
	 * @return  object
	 *
	 * @since   1.0.0
	 */
	public function getCoupon($couponCode = '')
	{
		jimport('joomla.filesystem.file');

		$coupon = new StdClass;

		$helperFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmlivedeal.php';
		$merchantFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmldmerchant.php';

		if (JFile::exists($helperFilePath) && JFile::exists($merchantFilePath))
		{
			require_once $helperFilePath;
			require_once $merchantFilePath;
		}
		else
		{
			return $coupon;
		}

		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_cmmobile');

		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$db->quoteName(
				array('a.id','a.code', 'a.deal_id', 'a.created')
			)
		);

		$query->from($db->quoteName('#__cmlivedeal_coupons') . ' AS a')
			->where($db->quoteName('a.code') . ' = ' . $db->quote($couponCode));

		// Get deal's info.
		$query->select($db->quoteName('d.title') . ' AS deal_name')
			->select($db->quoteName('d.ending_time') . ' AS deal_ending_time')
			->select($db->quoteName('d.fine_print') . ' AS deal_fine_print')
			->select($db->quoteName('d.user_id') . ' AS merchant_id')
			->join(
				'LEFT',
				$db->quoteName('#__cmlivedeal_deals') . ' AS d ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('a.deal_id')
			);

		$db->setQuery($query);

		try
		{
			$coupon = $db->loadObject();
		}
		catch (RuntimeException $e)
		{
			return $coupon;
		}

		if (!empty($coupon->id))
		{
			$attributes = array('name', 'address', 'latitude', 'longitude', 'phone');

			// Get merchant info.
			$cmldmerchant = new CMLDMerchant($coupon->merchant_id);

			if (!empty($cmldmerchant))
			{
				$merchant = new StdClass;

				foreach ($attributes as $attribute)
				{
					$merchant->$attribute = $cmldmerchant->get($attribute);
				}
			}

			$coupon->merchant = $merchant;

			$now = JFactory::getDate()->toSql();

			if (strtotime($now) >= strtotime($coupon->deal_ending_time))
			{
				$expired = true;
			}
			else
			{
				$expired = false;
			}

			$coupon->deal_expired = $expired;

			// Remove what we don't need from the JSON.
			unset($coupon->merchant_id);
			unset($coupon->deal_id);
		}

		return $coupon;
	}

	/**
	 * Capture a coupon. Return an array:
	 * Array(
	 *   'success' => true if capture successfully,
	 *   'message' => error message if fail,
	 *   'coupon'  => coupon data if succeed,
	 * )
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public function captureCoupon()
	{
		jimport('joomla.filesystem.file');

		$params = JComponentHelper::getParams('com_cmmobile');
		$method = $params->get('method', 'post');
		$app = JFactory::getApplication();

		$return = array(
			'success'	=> false,
			'message'	=> '',
			'coupon'	=> null,
		);

		// We can reuse some classes from CM Live Deal component.
		$helperFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/helpers/cmlivedeal.php';
		$dealModelFilePath = JPATH_SITE . '/components/com_cmlivedeal/models/deal.php';
		$couponModelFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/models/coupon.php';
		$couponTableFilePath = JPATH_ADMINISTRATOR . '/components/com_cmlivedeal/tables/coupon.php';

		if (JFile::exists($helperFilePath)
			&& JFile::exists($dealModelFilePath)
			&& JFile::exists($couponModelFilePath)
			&& JFile::exists($couponTableFilePath))
		{
			require_once $helperFilePath;
			require_once $dealModelFilePath;
			require_once $couponModelFilePath;
			require_once $couponTableFilePath;
		}
		else
		{
			return $return;
		}

		// Get the request token.
		$token = $app->input->$method->get('token', '', 'alnum');

		// Load the language of CM Live Deal.
		JFactory::getLanguage()->load('com_cmlivedeal', JPATH_BASE . '/components/com_cmlivedeal/');

		// Get user ID from session..
		$session = CMMobileSession::getSession($token);

		// Somehow user ID was not stored in the session?.
		if (empty($session->userid))
		{
			$return['message'] = JText::_('COM_CMMOBILE_CMMOBILE_USER_NOT_FOUND');

			return $return;
		}

		$userId = $session->userid;
		$dealId = $app->input->$method->get('deal_id', 0, 'uint');

		// Get deal and check if it exists.
		$deal = JModelLegacy::getInstance('Deal', 'CMLiveDealModel')->getDeal($dealId);

		if (!isset($deal->id))
		{
			$return['message'] = JText::_('COM_CMMOBILE_CMLIVEDEAL_DEAL_NOT_FOUND');

			return $return;
		}

		// Make sure the deal is published and approved.
		if ($deal->published == 0 || $deal->approved == 0)
		{
			$return['message'] = JText::_('COM_CMMOBILE_CMLIVEDEAL_DEAL_NOT_FOUND');

			return $return;
		}

		// Make sure the deal is active.
		$now = JFactory::getDate()->toSql();
		$active = (strtotime($deal->ending_time) >= strtotime($now)) ? true : false;

		if (!$active)
		{
			$return['message'] = JText::_('COM_CMLIVEDEAL_ERROR_DEAL_NOT_FOUND');

			return $return;
		}

		// Check if user is the merchant who owns the deal.
		if ($deal->user_id == $userId)
		{
			$return['message'] = JText::_('COM_CMLIVEDEAL_ERROR_NOT_CAPTURE_OWN');

			return $return;
		}

		// Check if user has already got a coupon for this deal.
		$captured = CMLiveDealHelper::doesUserHaveCoupon($userId, $deal->id);

		if ($captured)
		{
			$return['message'] = JText::_('COM_CMLIVEDEAL_ERROR_COUPON_ALREADY_GOT');

			return $return;
		}

		$couponCode = JModelLegacy::getInstance('Coupon', 'CMLiveDealModel')->createCoupon($userId, $deal->id);

		if ($couponCode !== false)
		{
			$coupon = $this->getCoupon($couponCode);

			if (isset($coupon->id))
			{
				$return['success'] = true;
				$return['coupon'] = $coupon;
			}
			else
			{
				$return['message'] = JText::_('COM_CMMOBILE_CMLIVEDEAL_COUPON_CREATE_ERROR');
			}
		}
		else
		{
			$return['message'] = JText::_('COM_CMMOBILE_CMLIVEDEAL_COUPON_CREATE_ERROR');
		}

		return $return;
	}
}
