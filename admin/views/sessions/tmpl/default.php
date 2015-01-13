<?php
/**
 * @package    CMMobile
 * @copyright  Copyright (C) 2014-2015 CMExtension Team http://www.cmext.vn/
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');

JFactory::getDocument()->addStyleSheet('components/com_cmmobile/assets/css/style.css');

$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$now		= strtotime(JFactory::getDate()->toSql());
?>
<div class="cmmobile">
	<?php echo $this->menu; ?>

	<form action="<?php echo JRoute::_('index.php?option=com_cmmobile&view=sessions'); ?>" method="post" name="adminForm" id="adminForm">
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search" class="element-invisible"><?php echo JText::_('COM_CMMOBILE_SEARCH');?></label>
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" class="hasTooltip" title="<?php echo JHtml::tooltipText('COM_CMMOBILE_SEARCH'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button type="button" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<select name="filter_state" id="filter_state" onchange="this.form.submit()">
					<option value=""><?php echo JText::_('COM_CMMOBILE_SESSION_ALL_SESSIONS');?></option>
					<?php echo JHtml::_('select.options', array(JHtml::_('select.option', 'active', 'COM_CMMOBILE_SESSION_ACTIVE_SESSIONS'), JHtml::_('select.option', 'expired', 'COM_CMMOBILE_SESSION_EXPIRED_SESSIONS')), 'value', 'text', $this->state->get('filter.state'), true); ?>
				</select>
			</div>
		</div>

		<div class="clearfix"> </div>

		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th width="1%" class="hidden-phone">
						<?php echo JHtml::_('grid.checkall'); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_CMMOBILE_SESSION_TOKEN', 'a.token', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_CMMOBILE_SESSION_USERID', 'a.userid', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_CMMOBILE_SESSION_USERNAME', 'a.username', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_CMMOBILE_SESSION_CREATED', 'a.created', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_CMMOBILE_SESSION_EXPIRED', 'expired', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="6">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center hidden-phone">
						<?php echo JHtml::_('grid.id', $i, $item->token); ?>
					</td>
					<td>
						<?php echo $item->token; ?>
					</td>
					<td>
						<?php echo $item->userid; ?>
					</td>
					<td>
						<?php echo $item->username; ?>
					</td>
					<td>
						<?php echo $item->created; ?>
					</td>
					<td>
						<?php
						if ($now > strtotime($item->expired))
						{
							echo '<span class="expired-time">' . $item->expired . '</span>';
						}
						else
						{
							echo $item->expired;
						}
						?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
