<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

require __DIR__ . '/abstract.php';

/**
 * Controller for connection with Users component.
 *
 * @package  CMMobile
 *
 * @since    1.0.0
 */
class CMMobileControllerUsers extends CMMobileControllerAbstract
{
	/**
	 * Login.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function login()
	{
		$params = JComponentHelper::getParams('com_cmmobile');
		$method = $params->get('method', 'post');
		$app = JFactory::getApplication();

		$username = $this->input->$method->get('username');
		$password = $this->input->$method->get('password');

		// Perform the log in.
		$token = CMMobileSession::login($username, $password);

		$data = array(
			'function'	=> 'login',
		);

		if ($token != '')
		{
			// Login succeeded. Return the token.
			$data ['token'] = $token;

			$message = JText::_('COM_CMMOBILE_USERS_LOGIN_SUCCESS');
			$json = new JResponseJson($data, $message);
		}
		else
		{
			// Login failed.
			$json = new JResponseJson($data, JText::_('COM_CMMOBILE_USERS_LOGIN_FAILURE'), true);
		}

		$this->displayView($json);
	}

	/**
	 * Logout.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function logout()
	{
		// Validate checksum and token.
		$this->validate();

		$params = JComponentHelper::getParams('com_cmmobile');
		$method = $params->get('method', 'post');
		$app = JFactory::getApplication();
		$token = $this->input->$method->get('token', '', 'alnum');
		$succeed = false;
		$message = '';

		// Logout succeeded.
		if (CMMobileSession::logout($token))
		{
			$succeed = true;
			$message = JText::_('COM_CMMOBILE_USERS_LOGOUT_SUCCESS');
		}
		else
		{
			$message = JText::_('COM_CMMOBILE_USERS_LOGOUT_FAILURE');
		}

		if ($succeed)
		{
			$json = new JResponseJson(null, $message);
		}
		else
		{
			$json = new JResponseJson(null, $message, true);
		}

		$this->displayView($json);
	}

	/**
	 * Method to register a user.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function register()
	{
		JFactory::getLanguage()->load('com_users', JPATH_SITE);

		$params = JComponentHelper::getParams('com_cmmobile');
		$method = $params->get('method', 'post');
		$app = JFactory::getApplication();

		// Registration is disabled.
		if (JComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0)
		{
			$json = new JResponseJson(null, JText::_('COM_CMMOBILE_USERS_REGISTER_DISABLED'), true);

			$this->displayView($json);

			$app->close();
		}

		$model = $this->getModel('Users', 'CMMobileModel');

		// Get the user data.
		$name = $this->input->$method->get('name', '', 'string');
		$username = $this->input->$method->get('username', '', 'username');
		$password = $this->input->$method->get('password', '', 'raw');
		$email = $this->input->$method->get('email', '', 'string');

		$requestData = array(
			'name'		=> $name,
			'username'	=> $username,
			'password'	=> $password,
			'email'		=> $email,
		);

		// Validate the posted data.
		$form = $model->getRegistrationForm();

		if (!$form)
		{
			$json = new JResponseJson(null, $model->getError(), true);

			$this->displayView($json);

			$app->close();
		}

		$data = $model->validateRegistrationForm($form, $requestData);

		// Check for validation errors.
		if ($data === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to only 1 validation messages out to the user.
			if ($errors[0] instanceof Exception)
			{
				$message = $errors[0]->getMessage();
			}
			else
			{
				$message = $errors[0];
			}

			$json = new JResponseJson(null, $message, true);

			$this->displayView($json);

			$app->close();
		}

		// Attempt to save the data.
		$return = $model->register($data);

		// Check for errors.
		if ($return === false)
		{
			$json = new JResponseJson(null, $model->getError(), true);

			$this->displayView($json);

			$app->close();
		}

		// User is created successfully. Log user in.
		$token = CMMobileSession::login($username, $password);

		if ($token != '')
		{
			// Login succeeded. Return the token.
			$data = array(
				'token'	=> $token,
			);
			$message = JText::_('COM_CMMOBILE_USERS_REGISTER_LOGIN_SUCCESS');
			$json = new JResponseJson($data, $message);
		}
		else
		{
			// Login failed.
			$json = new JResponseJson(null, JText::_('COM_CMMOBILE_USERS_REGISTER_LOGIN_FAILURE'), true);
		}

		$this->displayView($json);
	}
}
