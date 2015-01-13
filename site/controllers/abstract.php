<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

jimport('joomla.environment.browser');

/**
 * Abstract controller.
 *
 * @package  CMMobile
 *
 * @since    1.0.0
 */
abstract class CMMobileControllerAbstract extends JControllerLegacy
{
	// The default view.
	protected $viewName = 'response';

	// The default document's format.
	protected $viewFormat = 'json';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 * Recognized key values include 'name', 'default_task', 'model_path', and
	 * 'view_path' (this list is not meant to be comprehensive).
	 *
	 * @since   12.2
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_cmmobile');

		// Check for user agent, only proceed if user agent is valid.
		$allowedUserAgent = $params->get('user_agent');
		$jBrwoser = new JBrowser;
		$requestUserAgent = $jBrwoser->getAgentString();

		if ($allowedUserAgent != $requestUserAgent)
		{
			$json = new JResponseJson(null, JText::_('COM_CMMOBILE_ACCESS_DENIED'), true);
			echo $json;

			$app->close();
		}
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.0.0
	 */
	public function getModel($name = '', $prefix = 'CMMobileModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Validate token and checksum. Stop if one of them are invalid.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function validate()
	{
		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_cmmobile');
		$method = $params->get('method', 'post');
		$token = $this->input->$method->get('token', '', 'alnum');
		$checksum = $this->input->$method->get('checksum', '', 'alnum');

		$error = CMMobileSession::validate($token, $checksum);

		if ($error != '')
		{
			$data = array(
				'login'	=> 'true',
			);

			$json = new JResponseJson($data, $error, true);
			$this->displayView($json);

			$app->close();
		}
	}

	/**
	 * Display the view.
	 *
	 * @param   string  $response  The JSON response string.
	 * @param   object  $model     The model (optinnal).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function displayView($response = '', $model = null)
	{
		$document = JFactory::getDocument();

		// Get the view.
		try
		{
			$view = $this->getView($this->viewName, $this->viewFormat);
		}
		catch (Exception $e)
		{
			// View can't be found, response with error message.
			$json = new JResponseJson(null, JText::_('COM_CMMOBILE_VIEW_NOT_FOUND'), true);
			echo $json;
			$app->close();
		}

		if ($model != null)
		{
			$view->setModel($model);
		}

		$view->assignRef('response', $response);

		// Push document object into the view.
		$view->document = $document;

		// Display the view.
		$view->display();
	}
}
