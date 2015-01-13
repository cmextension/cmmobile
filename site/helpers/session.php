<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * Session manager.
 *
 * @package  CMMobile
 *
 * @since    1.0.0
 */
class CMMobileSession
{
	/**
	 * Login.
	 *
	 * @param   string  $username  Username.
	 * @param   string  $password  Password.
	 *
	 * @return  string  Token string if login successfully, empty string if login failed.
	 *
	 * @since   1.0.0
	 */
	public static function login($username = '', $password = '')
	{
		// Get the global JAuthentication object.
		jimport('joomla.user.authentication');

		$credentials = array(
			'username'	=> $username,
			'password'	=> $password,
		);

		$authenticate = JAuthentication::getInstance();
		$response = $authenticate->authenticate($credentials, array());

		$token = '';

		if ($response->status === JAuthentication::STATUS_SUCCESS)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			// Username and password are correct. Create token for new session.
			// Make sure token is unique.
			do
			{
				$query->select('COUNT(token)')
					->from($db->quoteName('#__cmmobile_sessions'))
					->where($db->quoteName('token') . ' = ' . $db->quote($token));
				$count = $db->setQuery($query)->loadResult();
				$token = self::createToken();
			} while($count != 0);

			$db = JFactory::getDbo();

			// Get user ID.
			$userId = JUserHelper::getUserId($username);

			// Current date time.
			$now = JFactory::getDate()->toSql();

			// Store new session to database.
			$query->clear()
				->insert($db->quoteName('#__cmmobile_sessions'))
				->columns($db->quoteName(array('token', 'userid', 'username', 'created')))
				->values(
					$db->quote($token) . ', ' .
					$db->quote($userId) . ', ' .
					$db->quote($username) . ', ' .
					$db->quote($now)
				);

			$db->setQuery($query);

			if ($db->execute())
			{
				// If new session is created successfully, we delete all previous sessions.
				$query->clear()
					->delete($db->quoteName('#__cmmobile_sessions'))
					->where($db->quoteName('token') . ' != ' . $db->quote($token))
					->where($db->quoteName('userid') . ' = ' . $db->quote($userId))
					->where($db->quoteName('username') . ' = ' . $db->quote($username));
				$db->setQuery($query)->execute();

				return $token;
			}
			else
			{
				return '';
			}
		}

		return $token;
	}

	/**
	 * Logout, all user's sessions are cleared.
	 *
	 * @param   string  $token  Token.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public static function logout($token)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('userid'))
			->from($db->quoteName('#__cmmobile_sessions'))
			->where($db->quoteName('token') . ' = ' . $db->quote($token));
		$userId = $db->setQuery($query)->loadResult();

		if (!empty($userId))
		{
			$query->clear()
				->delete($db->quoteName('#__cmmobile_sessions'))
				->where($db->quoteName('userid') . ' = ' . $db->quote($userId));
			$db->setQuery($query);

			if ($db->execute())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Create a token string
	 *
	 * @return  string  Generated token
	 *
	 * @since   1.0.0
	 */
	public static function createToken()
	{
		$params = JComponentHelper::getParams('com_cmmobile');
		$tokenLength = $params->get('token_length', 32);

		static $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
		$max = strlen($chars) - 1;
		$token = '';

		for ($i = 0; $i < $tokenLength; ++$i)
		{
			$token .= $chars[(rand(0, $max))];
		}

		return $token;
	}

	/**
	 * Validate token and checksum.
	 * Auto-delete the token in database if it is too old.
	 *
	 * @param   string  $token     Token.
	 * @param   string  $checksum  Checksum
	 *
	 * @return  string  Empty message if both are valid, otherwise error message.
	 *
	 * @since   1.0.0
	 */
	public static function validate($token, $checksum)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__cmmobile_sessions'))
			->where($db->quoteName('token') . ' = ' . $db->quote($token));
		$session = $db->setQuery($query)->loadObject();

		// Token exists. Check its lifetime. Calculate the checksum.
		if (isset($session->token))
		{
			$params = JComponentHelper::getParams('com_cmmobile');

			$lifetime = $params->get('session_lifetime', 24);
			$now = strtotime(JFactory::getDate()->toSql());

			$sessionCreatedDate = strtotime($session->created);
			$sessionExpiredDate = strtotime("+" . (int) $lifetime . ' hour', $sessionCreatedDate);

			// Session is expired. Delete it.
			if ($now > $sessionExpiredDate)
			{
				$query->clear()
					->delete($db->quoteName('#__cmmobile_sessions'))
					->where($db->quoteName('token') . ' = ' . $db->quote($token));
				$db->setQuery($query)->execute();

				return JText::_('COM_CMMOBILE_USERS_LOGOUT_EXPIRED_TOKEN');
			}

			// Check the checksum if not in test mode.
			$test = $params->get('test', 0);

			if ((int) $test == 0)
			{
				$secretKey = $params->get('secret_key', '');
				$string = $token . $session->username . $secretKey;

				$calculatedChecksum = md5($string, false);

				return ($calculatedChecksum == $checksum) ? '' : JText::_('COM_CMMOBILE_USERS_LOGOUT_INVALID_CHECKSUM');
			}
			else
			{
				return '';
			}
		}

		return JText::_('COM_CMMOBILE_USERS_LOGOUT_INVALID_TOKEN');
	}

	/**
	 * Get session's info.
	 *
	 * @param   string  $token  Token.
	 *
	 * @return  object
	 *
	 * @since   1.0.0
	 */
	public static function getSession($token)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__cmmobile_sessions'))
			->where($db->quoteName('token') . ' = ' . $db->quote($token));
		$session = $db->setQuery($query)->loadObject();

		return $session;
	}
}
