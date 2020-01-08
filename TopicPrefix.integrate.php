<?php

/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.2
 */

spl_autoload_register(array('Topic_Prefix_Integrate', 'autoload'));

class Topic_Prefix_Integrate
{
	public static function autoload($class)
	{
		switch ($class)
		{
			case 'TopicPrefix':
				require_once(SUBSDIR . '/TopicPrefix.class.php');
				break;
			case 'TopicPrefix_TcCRUD':
				require_once(SUBSDIR . '/TopicPrefixTcCRUD.class.php');
				break;
			case 'TopicPrefix_PxCRUD':
				require_once(SUBSDIR . '/TopicPrefixPxCRUD.class.php');
				break;
		}
	}

	public static function messageindex_listing($topicsinfo)
	{
		global $context, $board;

		require_once(SUBSDIR . '/TopicPrefix.subs.php');

		topicprefix_showprefix($topicsinfo);

		$px_manager = new TopicPrefix();
		$prefixes = $px_manager->loadPrefixes(null, $board, true);
		if (!empty($prefixes))
		{
			loadTemplate('TopicPrefix');
			loadLanguage('TopicPrefix');
			Template_Layers::getInstance()->addAfter('boardprefixes', 'topic_listing');

			$context['prefixes_board_specific'] = array();
			foreach ($prefixes as $id => $prefix)
				$context['prefixes_board_specific'][] = topicprefix_prefix_marktup(array('id_prefix' => $id, 'prefix' => $prefix['text']), $board);
		}
	}
	
	public static function quick_mod_actions()
	{
		global $context, $topic, $txt, $board;

		$context['qmod_actions'][] = 'addprefix';
		
		$context['can_addprefix'] = allowedTo('moderate_forum');
		
		$px_manager = new TopicPrefix();


		$available_prefixes = $px_manager->loadPrefixes(isset($prefix['id_prefix']) ? $prefix['id_prefix'] : null, $board);

		if (count($available_prefixes) > 1)
			$context['available_prefixes'] = $available_prefixes;

		
	}
	
	
	

	public static function display_message_list()
	{
		global $context, $topic, $txt;

		$px_manager = new TopicPrefix();
		$prefixes = $px_manager->getTopicPrefixes(array($topic));

		if (!empty($prefixes))
		{
			require_once(SUBSDIR . '/TopicPrefix.subs.php');
			loadCSSFile('TopicPrefix.css');
			loadLanguage('TopicPrefix');

			$context['num_views_text'] = sprintf($txt['topicprefix_linktree'], topicprefix_prefix_marktup($prefixes[$topic])) . ' ' . $context['num_views_text'];
		}
	}

	public static function post_after()
	{
		global $context, $topic, $board;

		// This has a meaning only for first posts
		// that means new topics or editing the first message
		if (!$context['is_first_post'])
			return;

		// All the template stuff
		loadTemplate('TopicPrefix');
		loadLanguage('TopicPrefix');
		Template_Layers::getInstance()->addAfter('pickprefix', 'postarea');

		$px_manager = new TopicPrefix();

		// If we are editing a message, we may want to know the old prefix
		if (isset($_REQUEST['msg']))
		{
			$prefix = $px_manager->getTopicPrefixes($topic);
		}

		$available_prefixes = $px_manager->loadPrefixes(isset($prefix['id_prefix']) ? $prefix['id_prefix'] : null, $board);

		if (count($available_prefixes) > 1)
			$context['available_prefixes'] = $available_prefixes;
	}

	public static function create_topic($msgOptions, $topicOptions, $posterOptions)
	{
		$prefix_id = isset($_POST['prefix']) ? (int) $_POST['prefix'] : 0;

		$prefix = new TopicPrefix();
		$prefix->updateTopicPrefix($topicOptions['id'], $prefix_id);
	}

	public static function before_modify_post($topics_columns, $update_parameters, $msgOptions, $topicOptions, $posterOptions)
	{
		$prefix_id = isset($_POST['prefix']) ? (int) $_POST['prefix'] : 0;
		$msgInfo = basicMessageInfo($_REQUEST['msg'], true, true);

		// Update the prefix, but only if it is the first message in the topic
		if ($msgInfo['id_first_msg'] == $msgOptions['id'])
		{
			$prefix = new TopicPrefix();
			$prefix->updateTopicPrefix($topicOptions['id'], $prefix_id);
		}
	}

	public static function admin_areas(&$admin_areas, &$menuOptions)
	{
		global $txt;

		loadLanguage('TopicPrefix');
		$admin_areas['layout']['areas']['postsettings']['subsections']['prefix'] = array($txt['topicprefix_pickprefixes'], 'manage_prefixes');
	}

	public static function sa_manage_posts(&$subActions)
	{
		global $txt;

		$subActions['prefix'] = array(
			'function' => 'action_index',
			'file' => 'ManagePrefix.controller.php',
			'controller' => 'ManagePrefix_Controller',
			'permission' => 'manage_prefixes'
		);
	}
}