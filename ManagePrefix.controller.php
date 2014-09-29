<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

if (!defined('ELK'))
	die('No access...');

class ManagePrefix_Controller extends Action_Controller
{
	/**
	 * Default (sub)action for ?action=prefix
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		if (isset($_REQUEST['boards']))
			return $this->_loadBoards();

		if (isset($_REQUEST['save']))
			$this->_save();

		$this->_show_list();
	}

	private function _loadBoards()
	{
	
	}

	private function _save()
	{
	
	}

	private function _show_list()
	{
		global $context, $modSettings, $scripturl;

		loadTemplate('TopicPrefix');
		loadTemplate('ManageMembergroups');
		loadTemplate('GenericBoards');

		require_once(SUBSDIR . '/TopicPrefix.class.php');
		require_once(SUBSDIR . '/Boards.subs.php');
		loadJavascriptFile('TopicPrefix.js');
		loadCSSFile('TopicPrefix.css');

		$context['sub_template'] = 'manage_topicprefix';
		$context['topicprefix_action'] = $scripturl . '?action=admin;area=postsettings;sa=prefix';

		$px_manager = new TopicPrefix();
		$context['topicprefix'] = $px_manager->loadPrefixes();
		// Get rid of the "No prefix" psuedo-prefix.
		if (isset($context['topicprefix'][0]))
			unset($context['topicprefix'][0]);

// 		$context += getBoardList(array('not_redirection' => true));
// 		$context['boards_in_category'] = array();
// 		foreach ($context['categories'] as $cat => &$category)
// 		{
// 			$context['boards_in_category'][$cat] = count($category['boards']);
// 			$category['child_ids'] = array_keys($category['boards']);
// 			foreach ($category['boards'] as &$board)
// 				$board['selected'] = (empty($context['search_params']['brd']) && (empty($modSettings['recycle_enable']) || $board['id'] != $modSettings['recycle_board'])) || (!empty($context['search_params']['brd']) && in_array($board['id'], $context['search_params']['brd']));
// 		}
// 		_debug($context['boards']);
	}
}