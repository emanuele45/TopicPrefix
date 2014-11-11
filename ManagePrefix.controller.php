<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.2
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
		// Our tidy subActions array
		$subActions = array(
			'list',
			'savenames',
			'editboards',
			'saveboards',
		);

		if (!isset($_REQUEST['do']) || !in_array($_REQUEST['do'], $subActions))
			$method = 'action_list';
		else
			$method = 'action_' . $_REQUEST['do'];

		return $this->$method();
	}

	public function action_saveboards()
	{
		global $settings;

		checkSession();
		validateToken('admin-editprefix');
		require_once(SUBSDIR . '/TopicPrefix.class.php');

		$prefix_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;

		$text = isset($_POST['prefix_name']) ? $_POST['prefix_name'] : '';
		$boards = isset($_POST['brd']) ? $_POST['brd'] : array();
		$style = isset($_POST['prefix_style']) ? $_POST['prefix_style'] : '';

		$px_manager = new TopicPrefix();

		if (empty($prefix_id))
			$prefix_id = $px_manager->newPrefix($text, $boards, $style, $settings['theme_dir']);
		else
			$prefix_id = $px_manager->updatePrefix($prefix_id, $text, $boards, $style, $settings['theme_dir']);

		if ($prefix_id === false)
		{
			$this->_addToSession($prefix_id, $text, $boards, $style);
			return $this->action_editboards();
		}
		else
			$this->_cleanSession();

		redirectexit('action=admin;area=postsettings;sa=prefix;do=editboards;pid=' . $prefix_id);
	}

	protected function _addToSession($id, $text, $boards, $style)
	{
		$_SESSION['prefix'] = array(
			$id,
			$text,
			$boards,
			$style
		);
	}

	protected function _cleanSession()
	{
		unset($_SESSION['prefix']);
	}

	protected function _loadFromSession()
	{
		if (isset($_SESSION['prefix']))
			return $_SESSION['prefix'];
		else
			return false;
	}

	public function action_editboards()
	{
		global $context, $scripturl, $settings;

		$this->_loadTemplate();
		$context['sub_template'] = 'prefixeditboards';

		$tmp = $this->_loadFromSession();
		if ($tmp === false)
		{
			$prefix_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;

			if (empty($prefix_id))
			{
				$prefix_id = 0;
				$px_manager = new TopicPrefix();
				$context['topicprefix'] = $px_manager->getPrefixDetails($prefix_id);
			}
			else
			{
				$px_manager = new TopicPrefix();
				$context['topicprefix'] = $px_manager->getPrefixDetails($prefix_id);
				$context['topicprefix']['style'] = $px_manager->getStyle($prefix_id, $settings['theme_dir']);
			}
		}
		else
		{
			$prefix_id = $tmp[0];
			$context['topicprefix'] = array(
				'id_prefix' => $tmp[0],
				'text' => $tmp[1],
				'boards' => $tmp[2],
				'style' => $tmp[3]
			);
		}

		$context['topicprefix_action'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=saveboards;pid=' . $prefix_id;

		require_once(SUBSDIR . '/Boards.subs.php');
		$context += getBoardList(array('not_redirection' => true));
		$context['boards_in_category'] = array();

		$num_boards = countBoards(null, array('include_redirects' => false));

		$context['boards_check_all'] = count($context['topicprefix']['boards']) == $num_boards;
		foreach ($context['categories'] as $cat => &$category)
		{
			$context['boards_in_category'][$cat] = count($category['boards']);
			$category['child_ids'] = array_keys($category['boards']);
			foreach ($category['boards'] as &$board)
				$board['selected'] = in_array($board['id'], $context['topicprefix']['boards']);
		}
		createToken('admin-editprefix');
	}

	public function action_savenames()
	{
	
	}

	public function action_list()
	{
		global $context, $scripturl;

		$this->_loadTemplate();

		$context['sub_template'] = 'manage_topicprefix';
		$context['topicprefix_action'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=savenames';
		$context['topicprefix_addnew_url'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=editboards';

		$px_manager = new TopicPrefix();
		$context['topicprefix'] = $px_manager->loadPrefixes(null, null, true);
	}

	protected function _loadTemplate()
	{

		loadTemplate('TopicPrefix');
		loadTemplate('ManageMembergroups');
		loadTemplate('GenericBoards');

		require_once(SUBSDIR . '/TopicPrefix.class.php');
		require_once(SUBSDIR . '/Boards.subs.php');
		loadJavascriptFile('TopicPrefix.js');
		loadCSSFile('TopicPrefix.css');
	}
}
