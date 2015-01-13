<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/**
 * HTML utility class for CM Mobile component.
 *
 * @since  1.0.0
 */
abstract class JHtmlCMMobile
{
	/**
	 * Render the component's menu.
	 *
	 * @return  string  Menu's HTML.
	 *
	 * @since   1.0.0
	 */
	public static function addMenu()
	{
		$jinput = JFactory::getApplication()->input;
		$activeView = $jinput->get('view', 'dashboard', 'word');

		// List of menu items.
		$items = array(
			'dashboard' => array(
				'label' => JText::_('COM_CMMOBILE_MENU_DASHBOARD'),
				'link' => 'index.php?option=com_cmmobile&view=dashboard',
			),
			'sessions' => array(
				'label' => JText::_('COM_CMMOBILE_MENU_SESSIONS'),
				'link' => 'index.php?option=com_cmmobile&view=sessions',
			),
		);

		$html = '<div class="cm-menu hidden-phone">';
		$html .= '<div class="navbar">';
		$html .= '<div class="navbar-inner">';
		$html .= '<ul class="nav nav-pills">';

		foreach ($items as $view => $item)
		{
			$hasChildren = (isset($item['items']) && count($item['items']) > 0) ? true : false;

			$active = ($activeView == $view) ? true : false;

			$liClass = ($hasChildren) ? "dropdown" : '';
			$aClass = ($hasChildren) ? 'dropdown-toggle' : '';
			$aData = ($hasChildren) ? ' data-toggle="dropdown"' : '';
			$caret = ($hasChildren) ? ' <b class="caret"></b>' : '';
			$link = ($hasChildren) ? '#' : $item['link'];

			$subActive = false;
			$subHtml = '';

			if ($hasChildren)
			{
				$subHtml .= '<ul class="dropdown-menu">';

				foreach ($item['items'] as $subView => $subItem)
				{
					if ($activeView == $subView)
					{
						$subActive = true;
						$subLiClass = 'active';
					}
					else
					{
						$subLiClass = '';
					}

					$subHtml .= '<li class="' . $subLiClass . '">';
					$subHtml .= '<a href="' . $subItem['link'] . '">' . $subItem['label'] . '</a>';
					$subHtml .= '</li>';
				}

				$subHtml .= '</ul>';
			}

			if ($active || $subActive)
			{
				$liClass .= ' active';
			}

			$html .= '<li class="' . $liClass . '">';
			$html .= '<a href="' . $link . '" class="' . $aClass . '"' . $aData . '>' . $item['label'] . $caret . '</a>';
			$html .= $subHtml;

			$html .= '</li>';
		}

		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
