<?php

/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.5
 */

/**
 * Our selection of hook based integration functions
 *
 * Class Topic_Prefix_Integrate
 */
class Topic_Prefix_Integrate
{
	/**
	 * Get the prefix's that can be used on this board and create the
	 * style spans that can be used in the template.  Used when showing the
	 * message index of a board.
	 *
	 * hook integrate_messageindex_listing called from MessageIndex.controller and
	 * from our own Prefix.controller.
	 *
	 * @param array $topicsinfo
	 * @throws \Elk_Exception
	 */
	public static function messageindex_listing($topicsinfo)
	{
		global $context, $board, $settings;

		// Prepare to show any topics with prefixes
		require_once(SUBSDIR . '/TopicPrefix.subs.php');
		topicprefix_showprefix($topicsinfo);

		// Show available prefixes for this board
		loadLanguage('TopicPrefix');
		$px_manager = new TopicPrefix();
		$prefixes = $px_manager->loadPrefixes(null, $board, false);

		// Only show the no prefix option when we are in a filtered prefix view
		if ($context['current_subaction'] === null)
		{
			$prefixes = array_slice($prefixes, 1, null, true);
		}

		if (count($prefixes) !== 0)
		{
			// Theme setting for who's viewing this board?
			if (!empty($settings['display_who_viewing']))
			{
				if (empty($board))
				{
					$settings['display_who_viewing'] = false;
				}
				else
				{
					require_once(SUBSDIR . '/Who.subs.php');
					formatViewers($board, 'board');
				}
			}

			loadTemplate('TopicPrefix');
			Template_Layers::instance()->addAfter('boardprefixes', 'topic_listing');

			$context['prefixes_board_specific'] = array();
			foreach ($prefixes as $id => $prefix)
			{
				$context['prefixes_board_specific'][] = topicprefix_prefix_marktup(array('id_prefix' => $id, 'prefix' => $prefix['text']), $board);
			}
		}
	}

	/**
	 * Add easy topic prefixing to the quick moderation actions
	 *
	 * hook integrate_quick_mod_actions called from MessageIndex.controller
	 */
	public static function quick_mod_actions()
	{
		global $context, $board;

		$context['qmod_actions'][] = 'addprefix';

		$context['can_addprefix'] = allowedTo('moderate_forum');

		loadLanguage('TopicPrefix');
		loadJavascriptFile('TopicPrefixQuick.js');
		$px_manager = new TopicPrefix();
		$available_prefixes = $px_manager->loadPrefixes(null, $board);
// _debug($available_prefixes);
		if (count($available_prefixes) > 1)
		{
			$quick_prefixes = 'var quick_prefixes = [';
			foreach ($available_prefixes as $id => $value)
			{
				$quick_prefixes .= '{"id": ' . $id . ', "text": ' . JavaScriptEscape($value['text']) . '},';
			}
		}
		$quick_prefixes = substr($quick_prefixes, 0, -1) . ']';
		addInlineJavascript($quick_prefixes);
	}

	/**
	 * Injects the prefix button for a given topic in the topic header bar
	 *
	 * hook integrate_display_message_list called from display controller
	 */
	public static function display_message_list()
	{
		global $context, $topic, $txt;

		// See if this is a prefixed topic
		loadLanguage('TopicPrefix');
		$px_manager = new TopicPrefix();
		$prefixes = $px_manager->getTopicPrefixes(array($topic));

		if (!empty($prefixes))
		{
			require_once(SUBSDIR . '/TopicPrefix.subs.php');
			loadCSSFile('TopicPrefix.css');
// 			$context['num_views_text'] = sprintf($txt['topicprefix_linktree'], topicprefix_prefix_marktup($prefixes[$topic])) . ' ' . $context['num_views_text'];
			$txt['topic'] = topicprefix_prefix_marktup($prefixes[$topic]) . ' ' . $txt['topic'];
		}
	}

	/**
	 * Populates the available prefix list and add the selection area for post template use
	 *
	 * hook integrate_action_post_after called from the dispatcher after post controller exit
	 *
	 * @throws \Elk_Exception
	 */
	public static function post_after()
	{
		global $context;

		$px_manager = new TopicPrefix();
		$available_prefixes = $px_manager->loadAll();

		if (count($available_prefixes) > 1)
		{
			loadTemplate('TopicPrefix');
			loadCSSFile('TopicPrefix.css');
			Template_Layers::instance()->addAfter('pickprefix', 'postarea');
			$context['available_prefixes'] = $available_prefixes;

			// Show the prefix preview next to the selection box
			addInlineJavascript('
			function showprefix()
			{
				var choice = document.forms.postmodify.post_in_board.options[document.forms.postmodify.post_in_board.selectedIndex].value,
					text = document.forms.postmodify.post_in_board.options[document.forms.postmodify.post_in_board.selectedIndex].text;

				document.getElementById("prefix").className = "prefix_id_" + choice;
				document.getElementById("prefix").innerHTML = choice === "0" ? "" : text;
			}
			
			showprefix();', true);
		}
	}

	/**
	 * Update the topicPrefix table if this new topic has a prefixed selected or
	 * unselected
	 *
	 * hook integrate_create_topic called from post.subs
	 *
	 * @param $msgOptions
	 * @param $topicOptions
	 * @param $posterOptions
	 */
	public static function create_topic($msgOptions, $topicOptions, $posterOptions)
	{
		$prefix_id = isset($_POST['prefix']) ? (int) $_POST['prefix'] : 0;

		$prefix = new TopicPrefix();
		$prefix->updateTopicPrefix($topicOptions['id'], $prefix_id);
	}

	/**
	 * If we are modifying a post, and this is the first post in the topic, show the
	 * prefix selection box
	 *
	 * hook integrate_before_modify_post called from post.subs
	 *
	 * @param $topics_columns
	 * @param $update_parameters
	 * @param $msgOptions
	 * @param $topicOptions
	 * @param $posterOptions
	 */
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

	/**
	 * Adds the prefix menu item under the post settings area
	 *
	 * hook integrate_admin_areas add items to the admin menu
	 *
	 * @param $admin_areas
	 * @param $menuOptions
	 */
	public static function admin_areas(&$admin_areas, &$menuOptions)
	{
		global $txt;

		loadLanguage('TopicPrefix');
		$admin_areas['layout']['areas']['postsettings']['subsections']['prefix'] = array($txt['topicprefix_pickprefixes'], 'manage_prefixes');
	}

	/**
	 * Adds our prefix menu pick action to the manage posts controller.
	 *
	 * hook integrate_sa_manage_posts called from MangePosts.controller
	 *
	 * @param array $subActions
	 */
	public static function sa_manage_posts(&$subActions)
	{
		$subActions['prefix'] = array(
			'function' => 'action_index',
			'file' => 'ManagePrefix.controller.php',
			'controller' => 'ManagePrefix_Controller',
			'permission' => 'manage_prefixes'
		);
	}
}
