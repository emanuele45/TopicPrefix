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
			'savesettings',
			'picker',
			'savestyles',
			'pickboards',
			'saveboards',
		);

		if (!isset($_REQUEST['do']) || !in_array($_REQUEST['do'], $subActions))
			$method = 'action_list';
		else
			$method = 'action_' . $_REQUEST['do'];

		return $this->$method();
	}

	public function action_savesettings()
	{
		global $settings;

		checkSession();
		validateToken('admin-editprefix');

		$prefix_id = $this->_savePrefix($_POST);

		if ($prefix_id === false)
		{
			$this->_addToSession($prefix_id, $text, $boards, $style);
			return $this->action_editboards();
		}
		else
			$this->_cleanSession();

		redirectexit('action=admin;area=postsettings;sa=prefix;do=editboards;pid=' . $prefix_id);
	}

	public function action_saveboards()
	{
		global $context, $db_show_debug;

		checkSession();

		$id = $this->_savePrefix($_POST);
		Template_Layers::getInstance()->removeAll();

		// Make room for ajax
		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
		$context['json_data'] = array(
			'success' => (bool) $id,
		);

		// No darn debugs here!!!
		$db_show_debug = false;
	}

	public function action_picker()
	{
		global $context, $settings;

		require_once(SUBSDIR . '/TopicPrefix.class.php');
		require_once(SUBSDIR . '/Prefix/StylePicker.class.php');

		loadTemplate('TopicPrefix');

		$picker = new StylePicker('Prefix');

		Template_Layers::getInstance()->removeAll();
		$context['sub_template'] = 'prefix_picker_popup';

		$prefix_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;
		$px_manager = new TopicPrefix();
		$styles = $px_manager->styleToArray($px_manager->getStyle($prefix_id, $settings['theme_dir']));

		$context['style_picker_elements'] = $picker->getAttributes();

		foreach ($styles as $key => $val)
		{
			if (isset($context['style_picker_elements'][$key]))
					$context['style_picker_elements'][$key]['value'] = $val;
		}
	}

	protected function _savePrefix($data)
	{
		global $settings;

		require_once(SUBSDIR . '/TopicPrefix.class.php');

		$prefix_id = isset($data['pid']) ? (int) $data['pid'] : 0;

		$text = isset($data['prefix_name']) ? $data['prefix_name'] : null;
		$boards = isset($data['brd']) ? $data['brd'] : null;
		$style = isset($data['prefix_style']) ? $data['prefix_style'] : null;

		$px_manager = new TopicPrefix();

		if (empty($prefix_id))
			$prefix_id = $px_manager->newPrefix($text, $boards, $style, $settings['theme_dir']);
		else
			$prefix_id = $px_manager->updatePrefix($prefix_id, $text, $boards, $style, $settings['theme_dir']);

		return $prefix_id;
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

			$px_manager = new TopicPrefix();
			if (empty($prefix_id))
			{
				$prefix_id = 0;
				$context['topicprefix'] = $px_manager->getPrefixDetails($prefix_id);
			}
			else
			{
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

		$context['topicprefix_action'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=savesettings;pid=' . $prefix_id;

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

	public function action_pickboards()
	{
		global $context, $settings;

		$prefix_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;

		$px_manager = new TopicPrefix();
		if (!empty($prefix_id))
		{
			$context['topicprefix'] = $px_manager->getPrefixDetails($prefix_id);
		}
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

		Template_Layers::getInstance()->removeAll();
		$this->_loadTemplate();
		$context['sub_template'] = 'prefix_boardspicker_popup';
	}

	public function action_savenames()
	{
		global $context, $db_show_debug;

		checkSession();

		$id = $this->_savePrefix($_POST);
		Template_Layers::getInstance()->removeAll();

		// Make room for ajax
		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
		$context['json_data'] = array(
			'success' => (bool) $id,
		);

		// No darn debugs here!!!
		$db_show_debug = false;
	}

	public function action_savestyles()
	{
		global $context, $db_show_debug;

		checkSession();

		require_once(SUBSDIR . '/Prefix/StylePicker.class.php');
		require_once(SUBSDIR . '/DataValidator.class.php');

		$picker = new StylePicker('Prefix');
		$validator = new Data_Validator();
		$result = $picker->validate($_POST, $validator);
		$prefix_style = '';
		foreach ($result as $key => $val)
			if (!empty($val))
				$prefix_style .= $key . ':' . $val . ';';

		$id = $this->_savePrefix(array('pid' => $_POST['pid'], 'prefix_style' => $prefix_style));
		Template_Layers::getInstance()->removeAll();

		// Make room for ajax
		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
		$context['json_data'] = array(
			'success' => (bool) $id,
		);

		// No darn debugs here!!!
		$db_show_debug = false;
	}

	public function action_list()
	{
		global $context, $scripturl, $txt;

		$this->_loadTemplate();

		$context['sub_template'] = 'manage_topicprefix';
		$context['topicprefix_action'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=savenames';
		$context['topicprefix_addnew_url'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=editboards';
		addJavascriptVar(array(
			'prefix_style_header' => $txt['prefix_style_header'],
			'prefix_boards_header' => $txt['choose_board'],
			'prefix_save_button' => $txt['save'],
		), true);

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
