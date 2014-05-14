<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * Part of the code in this file is derived from:
 *
 * ElkArte Forum
 * ElkArte Forum contributors
 * BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 0.0.1
 */

if (!defined('ELK'))
	die('No access...');

class Prefix_Controller extends Action_Controller
{

	public function pre_dispatch()
	{
		loadLanguage('TopicPrefix');
		require_once(SUBSDIR . '/TopicPrefix.subs.php');

		$this->_base_linktree();
	}

	/**
	 * Default (sub)action for ?action=prefix
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		$this->action_prefixlist();
	}

	public function action_prefixlist()
	{
		global $context, $txt, $scripturl, $modSettings;

		$sort_methods = array(
			'id' => 'id_prefix',
			'name' => 'prefix',
			'count' => 'num_topics',
		);

		loadTemplate('TopicPrefix');

		$context['page_title'] = $txt['topicprefix_pagetitle_list'];
		$context['sub_template'] = 'topicprefix_list';

		$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
		$limit = $modSettings['search_results_per_page'];
		$sort = isset($_GET['sort']) && isset($sort_methods[$_GET['sort']]) ? $sort_methods[$_GET['sort']] : 'id_prefix';

		$this->_base_linktree();
		$context['prefixes'] = topicprefix_getAllPrefixes($start, $limit, $sort, 'DESC');
		$num_prefixes = topicprefix_counAllPrefixes();
		foreach ($context['prefixes'] as $prefix_id => $prefix)
		{
			$context['prefixes'][$prefix_id] += array(
				'url' => $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id,
				'link' => '<a href="' . $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '">' . $prefix['prefix'] . '</a>',
			);
		}

		if (isset($_GET['sort']))
			$context['page_index'] = constructPageIndex($scripturl . '?action=prefix;sa=prefixlist;sort=' . $_GET['sort'] . (isset($_GET['desc']) ? ';desc' : ''), $_GET['start'], $num_prefixes, $limit, true);
		else
			$context['page_index'] = constructPageIndex($scripturl . '?action=prefix;sa=prefixlist;', $_GET['start'], $num_prefixes, $limit, true);
	}

	/**
	 * Show the list of topics in this board, along with any sub-boards.
	 * @uses MessageIndex template topic_listing sub template
	 */
	public function action_prefixedtopics()
	{
		global $txt, $scripturl, $modSettings, $context;
		global $options, $settings, $user_info;

		loadTemplate('MessageIndex');
		loadJavascriptFile('topic.js');

		// First of all we are going to deal with an id, otherwise default action.
		$prefix_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		if (empty($prefix_id))
			return $this->action_index();

		$prefix_info = topicprefix_getPrefixDetails($prefix_id);

		if (empty($prefix_info))
			return $this->action_index();

		$context['name'] = $prefix_info['text'];
		$context['sub_template'] = 'topic_listing';
		$template_layers = Template_Layers::getInstance();

		// View all the topics, or just a few?
		$context['topics_per_page'] = empty($modSettings['disableCustomPerPage']) && !empty($options['topics_per_page']) ? $options['topics_per_page'] : $modSettings['defaultMaxTopics'];
		$context['messages_per_page'] = empty($modSettings['disableCustomPerPage']) && !empty($options['messages_per_page']) ? $options['messages_per_page'] : $modSettings['defaultMaxMessages'];
		$maxindex = isset($_REQUEST['all']) && !empty($modSettings['enableAllMessages']) ? $prefix_info['count'] : $context['topics_per_page'];

		if (!empty($_REQUEST['start']) && (!is_numeric($_REQUEST['start']) || $_REQUEST['start'] % $context['messages_per_page'] != 0))
			$context['robot_no_index'] = true;

		// We only know these.
		if (isset($_REQUEST['sort']) && !in_array($_REQUEST['sort'], array('subject', 'starter', 'last_poster', 'replies', 'views', 'likes', 'first_post', 'last_post')))
			$_REQUEST['sort'] = 'last_post';

		// Make sure the starting place makes sense and construct the page index.
		if (isset($_REQUEST['sort']))
			$context['page_index'] = constructPageIndex($scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.%1$d;sort=' . $_REQUEST['sort'] . (isset($_REQUEST['desc']) ? ';desc' : ''), $_REQUEST['start'], $prefix_info['count'], $maxindex, true);
		else
			$context['page_index'] = constructPageIndex($scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.%1$d', $_REQUEST['start'], $prefix_info['count'], $maxindex, true);

		$context['start'] = &$_REQUEST['start'];

		// Set a canonical URL for this page.
		$context['canonical_url'] = $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . $context['start'];

		$context['links'] += array(
			'prev' => $_REQUEST['start'] >= $context['topics_per_page'] ? $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . ($_REQUEST['start'] - $context['topics_per_page']) : '',
			'next' => $_REQUEST['start'] + $context['topics_per_page'] < $prefix_info['count'] ? $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . ($_REQUEST['start'] + $context['topics_per_page']) : '',
		);

		$context['page_info'] = array(
			'current_page' => $_REQUEST['start'] / $context['topics_per_page'] + 1,
			'num_pages' => floor(($prefix_info['count'] - 1) / $context['topics_per_page']) + 1
		);

		if (isset($_REQUEST['all']) && !empty($modSettings['enableAllMessages']) && $maxindex > $modSettings['enableAllMessages'])
		{
			$maxindex = $modSettings['enableAllMessages'];
			$_REQUEST['start'] = 0;
		}

		$context['page_title'] = strip_tags(sprintf($txt['topicprefix_pagetitle'], $prefix_info['text'], (int) $context['page_info']['current_page']));

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.0',
			'name' => strip_tags(sprintf($txt['topicprefix_linktree'], $prefix_info['text']))
		);

		// Set the variables up for the template.
		$context['can_mark_notify'] = allowedTo('mark_notify') && !$user_info['is_guest'];
		$context['can_post_new'] = allowedTo('post_new') || ($modSettings['postmod_active'] && allowedTo('post_unapproved_topics'));
		$context['can_post_poll'] = !empty($modSettings['pollMode']) && allowedTo('poll_post') && $context['can_post_new'];
		$context['can_moderate_forum'] = allowedTo('moderate_forum');
		$context['can_approve_posts'] = allowedTo('approve_posts');

		// And now, what we're here for: topics!
		require_once(SUBSDIR . '/MessageIndex.subs.php');

		// Known sort methods.
		$sort_methods = messageIndexSort();

		// They didn't pick one, default to by last post descending.
		if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
		{
			$context['sort_by'] = 'last_post';
			$ascending = isset($_REQUEST['asc']);
		}
		// Otherwise default to ascending.
		else
		{
			$context['sort_by'] = $_REQUEST['sort'];
			$ascending = !isset($_REQUEST['desc']);
		}
		$sort_column = $sort_methods[$context['sort_by']];

		$context['sort_direction'] = $ascending ? 'up' : 'down';
		$context['sort_title'] = $ascending ? $txt['sort_desc'] : $txt['sort_asc'];

		// Trick
		$txt['starter'] = $txt['started_by'];

		foreach ($sort_methods as $key => $val)
			$context['topics_headers'][$key] = array(
				'url' => $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . $context['start'] . ';sort=' . $key . ($context['sort_by'] == $key && $context['sort_direction'] == 'up' ? ';desc' : ''),
				'sort_dir_img' => $context['sort_by'] == $key ? '<img class="sort" src="' . $settings['images_url'] . '/sort_' . $context['sort_direction'] . '.png" alt="" title="' . $context['sort_title'] . '" />' : '',
			);

		// Calculate the fastest way to get the topics.
		$start = (int) $_REQUEST['start'];
		if ($start > ($prefix_info['count'] - 1) / 2)
		{
			$ascending = !$ascending;
			$fake_ascending = true;
			$maxindex = $prefix_info['count'] < $start + $maxindex + 1 ? $prefix_info['count'] - $start : $maxindex;
			$start = $prefix_info['count'] < $start + $maxindex + 1 ? 0 : $prefix_info['count'] - $start - $maxindex;
		}
		else
			$fake_ascending = false;

		// Setup the default topic icons...
		$context['icon_sources'] = MessageTopicIcons();

		$topic_ids = array();
		$context['topics'] = array();

		// Set up the query options
		$indexOptions = array(
			'include_sticky' => !empty($modSettings['enableStickyTopics']),
			'only_approved' => $modSettings['postmod_active'] && !allowedTo('approve_posts'),
			'previews' => !empty($modSettings['message_index_preview']) ? (empty($modSettings['preview_characters']) ? -1 : $modSettings['preview_characters']) : 0,
			'include_avatars' => !empty($settings['avatars_on_indexes']),
			'ascending' => $ascending,
			'fake_ascending' => $fake_ascending
		);

		// Allow integration to modify / add to the $indexOptions
		call_integration_hook('integrate_prefixedtopics_topics', array(&$sort_column, &$indexOptions));

		$topics_info = topicprefix_getPrefixedTopics($prefix_id, $user_info['id'], $start, $maxindex, $context['sort_by'], $sort_column, $indexOptions);

		// Prepare for links to guests (for search engines)
		$context['pageindex_multiplier'] = empty($modSettings['disableCustomPerPage']) && !empty($options['messages_per_page']) ? $options['messages_per_page'] : $modSettings['defaultMaxMessages'];

		// Begin 'printing' the message index for current board.
		foreach ($topics_info as $row)
		{
			$topic_ids[] = $row['id_topic'];

			// Do they want message previews?
			if (!empty($modSettings['message_index_preview']))
			{
				// Limit them to $modSettings['preview_characters'] characters
				$row['first_body'] = strip_tags(strtr(parse_bbc($row['first_body'], false, $row['id_first_msg']), array('<br />' => "\n", '&nbsp;' => ' ')));
				$row['first_body'] = shorten_text($row['first_body'], !empty($modSettings['preview_characters']) ? $modSettings['preview_characters'] : 128, true);

				// No reply then they are the same, no need to process it again
				if ($row['num_replies'] == 0)
					$row['last_body'] == $row['first_body'];
				else
				{
					$row['last_body'] = strip_tags(strtr(parse_bbc($row['last_body'], false, $row['id_last_msg']), array('<br />' => "\n", '&nbsp;' => ' ')));
					$row['last_body'] = shorten_text($row['last_body'], !empty($modSettings['preview_characters']) ? $modSettings['preview_characters'] : 128, true);
				}

				// Censor the subject and message preview.
				censorText($row['first_subject']);
				censorText($row['first_body']);

				// Don't censor them twice!
				if ($row['id_first_msg'] == $row['id_last_msg'])
				{
					$row['last_subject'] = $row['first_subject'];
					$row['last_body'] = $row['first_body'];
				}
				else
				{
					censorText($row['last_subject']);
					censorText($row['last_body']);
				}
			}
			else
			{
				$row['first_body'] = '';
				$row['last_body'] = '';
				censorText($row['first_subject']);

				if ($row['id_first_msg'] == $row['id_last_msg'])
					$row['last_subject'] = $row['first_subject'];
				else
					censorText($row['last_subject']);
			}

			// Decide how many pages the topic should have.
			if ($row['num_replies'] + 1 > $context['messages_per_page'])
			{
				// We can't pass start by reference.
				$start = -1;
				$pages = constructPageIndex($scripturl . '?topic=' . $row['id_topic'] . '.%1$d', $start, $row['num_replies'] + 1, $context['messages_per_page'], true, array('prev_next' => false, 'all' => !empty($modSettings['enableAllMessages']) && $row['num_replies'] + 1 < $modSettings['enableAllMessages']));
			}
			else
				$pages = '';

			// We need to check the topic icons exist...
			if (!empty($modSettings['messageIconChecks_enable']))
			{
				if (!isset($context['icon_sources'][$row['first_icon']]))
					$context['icon_sources'][$row['first_icon']] = file_exists($settings['theme_dir'] . '/images/post/' . $row['first_icon'] . '.png') ? 'images_url' : 'default_images_url';

				if (!isset($context['icon_sources'][$row['last_icon']]))
					$context['icon_sources'][$row['last_icon']] = file_exists($settings['theme_dir'] . '/images/post/' . $row['last_icon'] . '.png') ? 'images_url' : 'default_images_url';
			}
			else
			{
				if (!isset($context['icon_sources'][$row['first_icon']]))
					$context['icon_sources'][$row['first_icon']] = 'images_url';

				if (!isset($context['icon_sources'][$row['last_icon']]))
					$context['icon_sources'][$row['last_icon']] = 'images_url';
			}

			// 'Print' the topic info.
			$context['topics'][$row['id_topic']] = array(
				'id' => $row['id_topic'],
				'first_post' => array(
					'id' => $row['id_first_msg'],
					'member' => array(
						'username' => $row['first_member_name'],
						'name' => $row['first_display_name'],
						'id' => $row['first_id_member'],
						'href' => !empty($row['first_id_member']) ? $scripturl . '?action=profile;u=' . $row['first_id_member'] : '',
						'link' => !empty($row['first_id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['first_id_member'] . '" title="' . $txt['profile_of'] . ' ' . $row['first_display_name'] . '" class="preview">' . $row['first_display_name'] . '</a>' : $row['first_display_name']
					),
					'time' => standardTime($row['first_poster_time']),
					'html_time' => htmlTime($row['first_poster_time']),
					'timestamp' => forum_time(true, $row['first_poster_time']),
					'subject' => $row['first_subject'],
					'preview' => trim($row['first_body']),
					'icon' => $row['first_icon'],
					'icon_url' => $settings[$context['icon_sources'][$row['first_icon']]] . '/post/' . $row['first_icon'] . '.png',
					'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0',
					'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.0">' . $row['first_subject'] . '</a>'
				),
				'last_post' => array(
					'id' => $row['id_last_msg'],
					'member' => array(
						'username' => $row['last_member_name'],
						'name' => $row['last_display_name'],
						'id' => $row['last_id_member'],
						'href' => !empty($row['last_id_member']) ? $scripturl . '?action=profile;u=' . $row['last_id_member'] : '',
						'link' => !empty($row['last_id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['last_id_member'] . '">' . $row['last_display_name'] . '</a>' : $row['last_display_name']
					),
					'time' => standardTime($row['last_poster_time']),
					'html_time' => htmlTime($row['last_poster_time']),
					'timestamp' => forum_time(true, $row['last_poster_time']),
					'subject' => $row['last_subject'],
					'preview' => trim($row['last_body']),
					'icon' => $row['last_icon'],
					'icon_url' => $settings[$context['icon_sources'][$row['last_icon']]] . '/post/' . $row['last_icon'] . '.png',
					'href' => $scripturl . '?topic=' . $row['id_topic'] . ($user_info['is_guest'] ? ('.' . (!empty($options['view_newest_first']) ? 0 : ((int) (($row['num_replies']) / $context['pageindex_multiplier'])) * $context['pageindex_multiplier']) . '#msg' . $row['id_last_msg']) : (($row['num_replies'] == 0 ? '.0' : '.msg' . $row['id_last_msg']) . '#new')),
					'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . ($user_info['is_guest'] ? ('.' . (!empty($options['view_newest_first']) ? 0 : ((int) (($row['num_replies']) / $context['pageindex_multiplier'])) * $context['pageindex_multiplier']) . '#msg' . $row['id_last_msg']) : (($row['num_replies'] == 0 ? '.0' : '.msg' . $row['id_last_msg']) . '#new')) . '" ' . ($row['num_replies'] == 0 ? '' : 'rel="nofollow"') . '>' . $row['last_subject'] . '</a>'
				),
				'default_preview' => trim($row[!empty($modSettings['message_index_preview']) && $modSettings['message_index_preview'] == 2 ? 'last_body' : 'first_body']),
				'is_sticky' => !empty($modSettings['enableStickyTopics']) && !empty($row['is_sticky']),
				'is_locked' => !empty($row['locked']),
				'is_poll' => !empty($modSettings['pollMode']) && $row['id_poll'] > 0,
				'is_hot' => !empty($modSettings['useLikesNotViews']) ? $row['num_likes'] >= $modSettings['hotTopicPosts'] : $row['num_replies'] >= $modSettings['hotTopicPosts'],
				'is_very_hot' => !empty($modSettings['useLikesNotViews']) ? $row['num_likes'] >= $modSettings['hotTopicVeryPosts'] : $row['num_replies'] >= $modSettings['hotTopicVeryPosts'],
				'is_posted_in' => false,
				'icon' => $row['first_icon'],
				'icon_url' => $settings[$context['icon_sources'][$row['first_icon']]] . '/post/' . $row['first_icon'] . '.png',
				'subject' => $row['first_subject'],
				'new' => $row['new_from'] <= $row['id_msg_modified'],
				'new_from' => $row['new_from'],
				'newtime' => $row['new_from'],
				'new_href' => $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['new_from'] . '#new',
				'redir_href' => !empty($row['id_redirect_topic']) ? $scripturl . '?topic=' . $row['id_topic'] . '.0;noredir' : '',
				'pages' => $pages,
				'replies' => comma_format($row['num_replies']),
				'views' => comma_format($row['num_views']),
				'likes' => comma_format($row['num_likes']),
				'approved' => $row['approved'],
				'unapproved_posts' => $row['unapproved_posts'],
			);

			if (!empty($settings['avatars_on_indexes']))
				$context['topics'][$row['id_topic']]['last_post']['member']['avatar'] = determineAvatar($row);

			determineTopicClass($context['topics'][$row['id_topic']]);
		}

		// Allow addons to add to the $context['topics']
		call_integration_hook('integrate_messageindex_listing', array($topics_info));

		// Fix the sequence of topics if they were retrieved in the wrong order. (for speed reasons...)
		if ($fake_ascending)
			$context['topics'] = array_reverse($context['topics'], true);

		if (!empty($modSettings['enableParticipation']) && !$user_info['is_guest'] && !empty($topic_ids))
		{
			$topics_participated_in = topicsParticipation($user_info['id'], $topic_ids);
			foreach ($topics_participated_in as $participated)
			{
				$context['topics'][$participated['id_topic']]['is_posted_in'] = true;
				$context['topics'][$participated['id_topic']]['class'] = 'my_' . $context['topics'][$participated['id_topic']]['class'];
			}
		}

		$context['jump_to'] = array(
			'label' => addslashes(un_htmlspecialchars($txt['jump_to'])),
			'board_name' => addslashes(un_htmlspecialchars($txt['select_destination'])),
			'child_level' => 0,
		);
		$context['current_board'] = 0;

		// Is Quick Moderation active/needed?
		if (!empty($options['display_quick_mod']) && !empty($context['topics']))
		{
			$context['can_markread'] = $context['user']['is_logged'];
			$context['can_lock'] = allowedTo('lock_any');
			$context['can_sticky'] = allowedTo('make_sticky') && !empty($modSettings['enableStickyTopics']);
			$context['can_move'] = allowedTo('move_any');
			$context['can_remove'] = allowedTo('remove_any');
			$context['can_merge'] = allowedTo('merge_any');

			// Ignore approving own topics as it's unlikely to come up...
			$context['can_approve'] = $modSettings['postmod_active'] && allowedTo('approve_posts') && !empty($board_info['unapproved_topics']);

			// Can we restore topics?
			$context['can_restore'] = false;

			// Set permissions for all the topics.
			foreach ($context['topics'] as $t => $topic)
			{
				$started = $topic['first_post']['member']['id'] == $user_info['id'];
				$context['topics'][$t]['quick_mod'] = array(
					'lock' => allowedTo('lock_any') || ($started && allowedTo('lock_own')),
					'sticky' => allowedTo('make_sticky') && !empty($modSettings['enableStickyTopics']),
					'move' => allowedTo('move_any') || ($started && allowedTo('move_own')),
					'modify' => allowedTo('modify_any') || ($started && allowedTo('modify_own')),
					'remove' => allowedTo('remove_any') || ($started && allowedTo('remove_own')),
					'approve' => $context['can_approve'] && $topic['unapproved_posts']
				);
				$context['can_lock'] |= ($started && allowedTo('lock_own'));
				$context['can_move'] |= ($started && allowedTo('move_own'));
				$context['can_remove'] |= ($started && allowedTo('remove_own'));
			}

			$context['can_quick_mod'] = false;
		}

		// If there are children, but no topics and no ability to post topics...
		$context['no_topic_listing'] = false;
		$template_layers->add('topic_listing');
/*
		addJavascriptVar(array('notification_board_notice' => $context['is_marked_notify'] ? $txt['notification_disable_board'] : $txt['notification_enable_board']), true);

		// Build the message index button array.
		$context['normal_buttons'] = array(
			'new_topic' => array('test' => 'can_post_new', 'text' => 'new_topic', 'image' => 'new_topic.png', 'lang' => true, 'url' => $scripturl . '?action=post;board=' . $context['current_board'] . '.0', 'active' => true),
			'notify' => array('test' => 'can_mark_notify', 'text' => $context['is_marked_notify'] ? 'unnotify' : 'notify', 'image' => ($context['is_marked_notify'] ? 'un' : ''). 'notify.png', 'lang' => true, 'custom' => 'onclick="return notifyboardButton(this);"', 'url' => $scripturl . '?action=notifyboard;sa=' . ($context['is_marked_notify'] ? 'off' : 'on') . ';board=' . $context['current_board'] . '.' . $context['start'] . ';' . $context['session_var'] . '=' . $context['session_id']),
		);

		// They can only mark read if they are logged in and it's enabled!
		if (!$user_info['is_guest'] && $settings['show_mark_read'])
			$context['normal_buttons']['markread'] = array('text' => 'mark_read_short', 'image' => 'markread.png', 'lang' => true, 'url' => $scripturl . '?action=markasread;sa=board;board=' . $context['current_board'] . '.0;' . $context['session_var'] . '=' . $context['session_id'], 'custom' => 'onclick="return markboardreadButton(this);"');

		// Allow adding new buttons easily.
		call_integration_hook('integrate_messageindex_buttons');
		*/
	}

	private function _base_linktree()
	{
		global $context, $txt, $scripturl;

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=prefix;sa=prefixedtopics',
			'name' => $txt['topicprefix_pagetitle_list']
		);
	}
}