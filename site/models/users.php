<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Model for Users component.
 *
 * @package  CMMobile
 *
 * @since    1.0.0
 */
class CMMobileModelUsers extends JModelLegacy
{
	/**
	 * Method to get the registration form.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getRegistrationForm()
	{
		// Get the form.
		JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
		JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

		try
		{
			$form = JForm::getInstance('com_cmmobile.users_registration', 'users_registration');
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $form;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @since   1.0.0
	 */
	public function validateRegistrationForm($form, $data, $group = null)
	{
		// Filter and validate the form data.
		$data = $form->filter($data);
		$return = $form->validate($data, $group);

		// Check for an error.
		if ($return instanceof Exception)
		{
			$this->setError($return->getMessage());

			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				$this->setError($message);
			}

			return false;
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The user id on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function register($data)
	{
		$params = JComponentHelper::getParams('com_users');

		// Initialise the table with JUser.
		$user = new JUser;

		// Get the default new user group, Registered if not specified.
		$system = $params->get('new_usertype', 2);

		$data['groups'] = array($system);

		// Prepare the data for the user object.
		$data['email'] = JStringPunycode::emailToPunycode($data['email']);
		$data['password'] = $data['password'];
		$useractivation = $params->get('useractivation');
		$sendpassword = $params->get('sendpassword', 1);

		// Check if the user needs to activate the account.
		if (($useractivation == 1) || ($useractivation == 2))
		{
			$data['activation'] = JApplication::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}

		// Bind the data.
		if (!$user->bind($data))
		{
			$this->setError(JText::sprintf('COM_USERS_REGISTRATION_BIND_FAILED', $user->getError()));

			return false;
		}

		// Load the users plugin group.
		JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save())
		{
			$this->setError(JText::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $user->getError()));

			return false;
		}

		$config = JFactory::getConfig();
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Compile the notification mail values.
		$data = $user->getProperties();
		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');
		$data['siteurl'] = JUri::root();

		// Handle account activation/confirmation emails.
		if ($useractivation == 2)
		{
			// Set the link to confirm the user email.
			$uri = JUri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username'],
					$data['password_clear']
				);
			}
			else
			{
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY_NOPW',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username']
				);
			}
		}
		elseif ($useractivation == 1)
		{
			// Set the link to activate the user account.
			$uri = JUri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username'],
					$data['password_clear']
				);
			}
			else
			{
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username']
				);
			}
		}
		else
		{
			$emailSubject = JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_BODY',
					$data['name'],
					$data['sitename'],
					$data['siteurl'],
					$data['username'],
					$data['password_clear']
				);
			}
			else
			{
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_BODY_NOPW',
					$data['name'],
					$data['sitename'],
					$data['siteurl']
				);
			}
		}

		// Send the registration email.
		$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

		// Send Notification mail to administrators.
		if (($params->get('useractivation') < 2) && ($params->get('mail_to_admin') == 1))
		{
			$emailSubject = JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			$emailBodyAdmin = JText::sprintf(
				'COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY',
				$data['name'],
				$data['username'],
				$data['siteurl']
			);

			// Get all admin users
			$query->clear()
				->select($db->quoteName(array('name', 'email', 'sendEmail')))
				->from($db->quoteName('#__users'))
				->where($db->quoteName('sendEmail') . ' = ' . 1);

			$db->setQuery($query);

			try
			{
				$rows = $db->loadObjectList();
			}
			catch (RuntimeException $e)
			{
				$this->setError(JText::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage()), 500);

				return false;
			}

			// Send mail to all superadministrators.
			foreach ($rows as $row)
			{
				$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $row->email, $emailSubject, $emailBodyAdmin);

				// Check for an error.
				if ($return !== true)
				{
					$this->setError(JText::_('COM_USERS_REGISTRATION_ACTIVATION_NOTIFY_SEND_MAIL_FAILED'));

					return false;
				}
			}
		}

		// Check for an error.
		if ($return !== true)
		{
			$this->setError(JText::_('COM_USERS_REGISTRATION_SEND_MAIL_FAILED'));

			// Send a system message to administrators receiving system mails
			$db = JFactory::getDbo();
			$query->clear()
				->select($db->quoteName(array('name', 'email', 'sendEmail', 'id')))
				->from($db->quoteName('#__users'))
				->where($db->quoteName('block') . ' = ' . (int) 0)
				->where($db->quoteName('sendEmail') . ' = ' . (int) 1);
			$db->setQuery($query);

			try
			{
				$sendEmail = $db->loadColumn();
			}
			catch (RuntimeException $e)
			{
				$this->setError(JText::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage()), 500);

				return false;
			}

			if (count($sendEmail) > 0)
			{
				$jdate = new JDate;

				// Build the query to add the messages
				foreach ($sendEmail as $userid)
				{
					$values = array($db->quote($userid), $db->quote($userid), $db->quote($jdate->toSql()), $db->quote(JText::_('COM_USERS_MAIL_SEND_FAILURE_SUBJECT')), $db->quote(JText::sprintf('COM_USERS_MAIL_SEND_FAILURE_BODY', $return, $data['username'])));
					$query->clear()
						->insert($db->quoteName('#__messages'))
						->columns($db->quoteName(array('user_id_from', 'user_id_to', 'date_time', 'subject', 'message')))
						->values(implode(',', $values));
					$db->setQuery($query);

					try
					{
						$db->execute();
					}
					catch (RuntimeException $e)
					{
						$this->setError(JText::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage()), 500);

						return false;
					}
				}
			}

			return false;
		}

		if ($useractivation == 1)
		{
			return "useractivate";
		}
		elseif ($useractivation == 2)
		{
			return "adminactivate";
		}
		else
		{
			return $user->id;
		}
	}
}
