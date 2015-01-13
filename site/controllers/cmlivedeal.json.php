<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

require __DIR__ . '/abstract.php';

/**
 * Controller for connection with CM Live Deal component.
 *
 * @package  CMMobile
 *
 * @since    1.0.0
 */
class CMMobileControllerCMLiveDeal extends CMMobileControllerAbstract
{
	/**
	 * Get deals.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function getDeals()
	{
		$model = $this->getModel('CMLiveDeal', 'CMMobileModel');

		$deals = $model->getDeals();
		$total = $model->getTotalDeals();

		$data = array(
			'total'	=> $total,
			'deals'	=> $deals,
		);

		$json = new JResponseJson($data);

		$this->displayView($json);
	}

	/**
	 * Get categories.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function getCategories()
	{
		$model = $this->getModel('CMLiveDeal', 'CMMobileModel');

		$categories = $model->getCategories();

		$data = array(
			'categories'	=> $categories,
		);

		$json = new JResponseJson($data);

		$this->displayView($json);
	}

	/**
	 * Get cities.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function getCities()
	{
		$model = $this->getModel('CMLiveDeal', 'CMMobileModel');

		$cities = $model->getCities();

		$data = array(
			'cities'	=> $cities,
		);

		$json = new JResponseJson($data);

		$this->displayView($json);
	}

	/**
	 * Get coupons of a user.
	 * Only logged-in users can take this action.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function getCoupons()
	{
		// Validate checksum and token.
		$this->validate();

		$params = JComponentHelper::getParams('com_cmmobile');
		$method = $params->get('method', 'post');

		$model = $this->getModel('CMLiveDeal', 'CMMobileModel');

		$coupons = $model->getCoupons();
		$total = $model->getTotalCoupons();

		$data = array(
			'login'		=> false,
			'total'		=> $total,
			'coupons'	=> $coupons,
		);

		$json = new JResponseJson($data);

		$this->displayView($json);
	}

	/**
	 * Get a coupon.
	 * Only logged-in users can take this action.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function captureCoupon()
	{
		// Validate checksum and token.
		$this->validate();

		$model = $this->getModel('CMLiveDeal', 'CMMobileModel');

		$response = array(
			'login'	=> false
		);

		/**
		 * $data['success']: true if capture successfully.
		 * $data['message']: error message if fail.
		 * $data['coupon']: coupon data if succeed.
		 */
		$data = $model->captureCoupon();

		if ($data['success'])
		{
			$response['coupon'] = $data['coupon'];
			$json = new JResponseJson($response);
		}
		else
		{
			$json = new JResponseJson($response, $data['message'], true);
		}

		$this->displayView($json);
	}
}
