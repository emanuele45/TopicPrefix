<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.5
 */

class ManagePrefix_Controller extends Action_Controller
{
	/**
	 * Some cruft we always need
	 */
	public function pre_dispatch()
	{
		require_once(SUBSDIR . '/TopicPrefix.class.php');
		require_once(SUBSDIR . '/TopicPrefix.subs.php');

		// Does nothing but is proper
		parent::pre_dispatch();
	}

	/**
	 * Default (sub)action for ?action=prefix
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		global $context;

		// Our tidy subActions array
		$subActions = array(
			'list' => array($this, 'action_list'),
			'savenames' => array($this, 'action_savenames'),
			'editboards' => array($this, 'action_editboards'),
			'savesettings' => array($this, 'action_savesettings'),
			'picker' => array($this, 'action_picker'),
			'savestyles' => array($this, 'action_savestyles'),
			'pickboards' => array($this, 'action_pickboards'),
			'saveboards' => array($this, 'action_saveboards'),
		);

		$action = new Action();
		$subAction = $action->initialize($subActions, 'list', 'do');

		// Any Final page details
		$context['sub_action'] = $subAction;

		// Now go!
		$action->dispatch($subAction);
	}

	/**
	 * Save the prefix form
	 *
	 * @throws \Elk_Exception
	 */
	public function action_savesettings()
	{
		checkSession();
		validateToken('admin-editprefix');

		$prefix_id = $this->_savePrefix((array) $this->_req->post);
		if ($prefix_id === false)
		{
			// Something to repopulate the form when the user bones it up
			$text = $this->_req->getPost('prefix_name', 'trim', null);
			$boards = $this->_req->getPost('brd', 'trim', null);
			$style = $this->_req->getPost('prefix_style', 'trim', null);
			$this->_addToSession($prefix_id, $text, $boards, $style);

			return $this->action_editboards();
		}

		$this->_cleanSession();

		redirectexit('action=admin;area=postsettings;sa=prefix');
	}

	/**
	 * Save / Update a prefix
	 *
	 * @param mixed[] $data data should contain the id, name, groups and style css
	 * @return bool|int
	 * @throws \Elk_Exception
	 */
	protected function _savePrefix($data)
	{
		global $settings, $txt;

		$prefix_id = isset($data['pid']) ? (int) $data['pid'] : 0;
		$text = isset($data['prefix_name']) ? $data['prefix_name'] : null;
		$boards = isset($data['brd']) ? $data['brd'] : null;
		$style = isset($data['prefix_style']) ? $data['prefix_style'] : null;

		// Prefix name is always needed
		if (empty($data['prefix_name']))
		{
			throw new Elk_Exception($txt['prefix_error_prefixname']);
		}

		// And we need some style rules even if they are empty
		if (empty($style))
		{
			throw new Elk_Exception($txt['prefix_error_stylecss']);
		}

		$prefixManager = new TopicPrefix();
		if (empty($prefix_id))
		{
			$prefix_id = $prefixManager->newPrefix($text, $boards, $style, $settings['theme_dir']);
		}
		else
		{
			$prefix_id = $prefixManager->updatePrefix($prefix_id, $text, $boards, $style, $settings['theme_dir']);
		}

		return $prefix_id;
	}

	/**
	 * Stuff the session with our form data so we don't loose it on error
	 *
	 * @param int $id
	 * @param string $text
	 * @param string $boards
	 * @param string $style
	 */
	protected function _addToSession($id, $text, $boards, $style)
	{
		$_SESSION['prefix'] = array(
			$id,
			$text,
			$boards,
			$style
		);
	}

	/**
	 * Add or edit a prefix to a selection of boards
	 *
	 * @throws \Elk_Exception
	 */
	public function action_editboards()
	{
		global $context, $scripturl, $settings, $txt;

		// We need to save our prefix css to the custom.css file, so make sure it exists
		if (!file_exists($settings['theme_dir'] . '/css/custom.css'))
		{
			file_put_contents($settings['theme_dir'] . '/css/custom.css', '', LOCK_EX);

			if (!file_exists($settings['theme_dir'] . '/css/custom.css'))
			{
				throw new Elk_Exception($txt['prefix_error_customcss'] . $settings['theme_dir'] . '/css/custom.css');
			}
		}

		$tempPrefix = $this->_loadFromSession();
		if ($tempPrefix === false)
		{
			$prefix_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;

			$prefixManager = new TopicPrefix();
			if (empty($prefix_id))
			{
				$context['topicprefix'] = $prefixManager->getPrefixDetails($prefix_id);
			}
			else
			{
				$context['topicprefix'] = $prefixManager->getPrefixDetails($prefix_id);
				$context['topicprefix']['style'] = $prefixManager->getStyle($prefix_id, $settings['theme_dir']);
			}
		}
		else
		{
			$prefix_id = $tempPrefix[0];
			$context['topicprefix'] = array(
				'id_prefix' => $tempPrefix[0],
				'text' => $tempPrefix[1],
				'boards' => $tempPrefix[2],
				'style' => $tempPrefix[3]
			);
		}

		$this->getBoards();

		// Get things ready for the template
		$this->_loadTemplate();
		$context['sub_template'] = 'prefixeditboards';
		$context['topicprefix_action'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=savesettings;pid=' . $prefix_id;
		createToken('admin-editprefix');
	}

	/**
	 * Load prefix information from the session if available
	 *
	 * @return bool|mixed
	 */
	protected function _loadFromSession()
	{
		if (isset($_SESSION['prefix']))
		{
			return $_SESSION['prefix'];
		}

		return false;
	}

	/**
	 * Loads the board listing, including those selected, into context for template use
	 */
	private function getBoards()
	{
		global $context;

		require_once(SUBSDIR . '/Boards.subs.php');
		$context += getBoardList(array('not_redirection' => true));
		$num_boards = countBoards(null, array('include_redirects' => false));

		$context['topicprefix']['boards'] = empty($context['topicprefix']['boards']) ? array() : $context['topicprefix']['boards'];
		$context['boards_check_all'] = count($context['topicprefix']['boards']) == $num_boards;

		$context['boards_in_category'] = array();
		foreach ($context['categories'] as $cat => &$category)
		{
			$context['boards_in_category'][$cat] = count($category['boards']);
			$category['child_ids'] = array_keys($category['boards']);

			foreach ($category['boards'] as &$board)
			{
				$board['selected'] = in_array($board['id'], $context['topicprefix']['boards']);
			}
		}
	}

	/**
	 * Load all the templates, CSS, JS
	 *
	 * @throws \Elk_Exception
	 */
	protected function _loadTemplate()
	{
		require_once(SUBSDIR . '/Boards.subs.php');

		loadTemplate('TopicPrefix');
		loadTemplate('ManageMembergroups');
		loadTemplate('GenericBoards');
		loadJavascriptFile('TopicPrefix.js');
		loadCSSFile('TopicPrefix.css');
	}

	/**
	 * Purge the prefix info from the session
	 */
	protected function _cleanSession()
	{
		unset($_SESSION['prefix']);
	}

	/**
	 * Does what it says, save a selection of boards to a prefix
	 *
	 * @throws \Elk_Exception
	 */
	public function action_saveboards()
	{
		checkSession();

		$data = (array) $this->_req->post;

		// We are saving board selection, the rest stays the same
		$topicPrefix = topicprefixLoadId($data['pid']);
		$data['prefix_style'] = $topicPrefix['style'];
		$id = $this->_savePrefix($data);

		$this->sendJson($id);
	}

	/**
	 * Send back the Json response
	 *
	 * @param $id
	 * @throws \Elk_Exception
	 */
	private function sendJson($id)
	{
		global $context, $db_show_debug;

		// Make room for ajax
		Template_Layers::instance()->removeAll();
		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
		$context['json_data'] = array(
			'success' => (bool) $id,
		);

		// No darn debugs here!!!
		$db_show_debug = false;
	}

	/**
	 * Prepares the prefix style picker template which is then displayed in a
	 * popup box.  Called via JS/ajax
	 *
	 * @throws \Elk_Exception
	 */
	public function action_picker()
	{
		global $context, $settings;

		require_once(SUBSDIR . '/Prefix/StylePicker.class.php');

		// Fetch our existing styles so we populate the template
		$prefix_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;
		$prefixManager = new TopicPrefix();
		$styles = $prefixManager->styleToArray($prefixManager->getStyle($prefix_id, $settings['theme_dir']));

		// Load all known style attributes and set their current value if any
		$picker = new StylePicker('Prefix');
		$context['style_picker_elements'] = $picker->getAttributes();

		foreach ($styles as $key => $val)
		{
			if (isset($context['style_picker_elements'][$key]))
			{
				$context['style_picker_elements'][$key]['value'] = $val;
			}
		}

		// Time to cough this back up for display in the popup
		loadTemplate('TopicPrefix');
		Template_Layers::instance()->removeAll();
		$context['sub_template'] = 'prefix_picker_popup';
	}

	/**
	 * Remember kids, you can pick your boards but not your parents.  THis is the
	 * board selection list for use in the ajax call
	 *
	 * @throws \Elk_Exception
	 */
	public function action_pickboards()
	{
		global $context;

		$prefix_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;

		$px_manager = new TopicPrefix();
		if (!empty($prefix_id))
		{
			$context['topicprefix'] = $px_manager->getPrefixDetails($prefix_id);
		}

		$this->getBoards();

		// For the ajax template
		Template_Layers::instance()->removeAll();
		$this->_loadTemplate();
		$context['sub_template'] = 'prefix_boardspicker_popup';
	}

	/**
	 * Saves/updates the topic prefix name via ajax
	 *
	 * @throws \Elk_Exception
	 */
	public function action_savenames()
	{
		global $context, $db_show_debug;

		checkSession();
		$id = false;
		$data = (array) $this->_req->post;

		// This function changes the name only so we use existing data for the rest
		if (!empty($data['pid']))
		{
			$topicPrefix = topicprefixLoadId($data['pid']);
			$data['brd'] = $topicPrefix['boards'];
			$data['prefix_style'] = $topicPrefix['style'];

			$id = $this->_savePrefix($data);
		}

		$this->sendJson($id);
	}

	/**
	 * Save the prefix style (from the eyedropper) editor (via ajax)
	 *
	 * @throws \Elk_Exception
	 */
	public function action_savestyles()
	{
		checkSession();

		require_once(SUBSDIR . '/Prefix/StylePicker.class.php');
		require_once(SUBSDIR . '/DataValidator.class.php');

		$data = (array) $this->_req->post;
		$picker = new StylePicker('Prefix');
		$validator = new Data_Validator();

		// Set all our prefix styles
		$result = $picker->validate($data, $validator);
		$data['prefix_style'] = '';
		foreach ($result as $key => $val)
		{
			if (!empty($val))
			{
				$data['prefix_style'] .= $key . ': ' . $val . ";\n\t";
			}
		}

		// We are saving css styles, the rest stays the same
		$topicPrefix = topicprefixLoadId($data['pid']);
		$data['brd'] = $topicPrefix['boards'];
		$id = $this->_savePrefix($data);

		$this->sendJson($id);
	}

	/**
	 * Default action, show a list of prefixes in the system with edit / add options
	 *
	 * @throws \Elk_Exception
	 */
	public function action_list()
	{
		global $context, $scripturl, $txt;

		$this->_loadTemplate();

		$context['sub_template'] = 'manage_topicprefix';
		$context['topicprefix_action'] = $scripturl . '?action=admin;area=postsettings;sa=prefix';
		$context['topicprefix_addnew_url'] = $scripturl . '?action=admin;area=postsettings;sa=prefix;do=editboards';
		addJavascriptVar(array(
			'prefix_style_header' => $txt['prefix_style_header'],
			'prefix_boards_header' => $txt['choose_board'],
			'prefix_save_button' => $txt['save'],
		), true);

		$px_manager = new TopicPrefix();
		$context['topicprefix'] = $px_manager->loadPrefixes(null, null, true);
	}
}
