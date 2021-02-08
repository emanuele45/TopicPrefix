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
 * copyright:    2011 Simple Machines (http://www.simplemachines.org)
 * license:        BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 0.0.5
 */

class Prefix_Controller extends Action_Controller
{
	/** @var \TopicPrefix */
	protected $px_manager;

	/**
	 * All the stuff we always need
	 */
	public function pre_dispatch()
	{
		loadLanguage('TopicPrefix');
		require_once(SUBSDIR . '/TopicPrefix.class.php');

		$this->px_manager = new TopicPrefix();
		$this->_base_linktree();

		parent::pre_dispatch();
	}

	/**
	 * Build the base breadcrumb for prefixed-topic listings
	 */
	private function _base_linktree()
	{
		global $context, $txt, $scripturl, $board;

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=prefix;sa=prefixedtopics;board=' . $board,
			'name' => $txt['topicprefix_pagetitle_list']
		);
	}

	/**
	 * Default (sub)action for ?action=prefix
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		global $context;

		// Our short subActions array
		$subActions = array(
			'prefixlist' => array($this, 'action_prefixlist'),
			'prefixedtopics' => array($this, 'action_prefixedtopics'),
			'addprefix' => array($this, 'action_addprefix'),
		);

		$action = new Action();
		$subAction = $action->initialize($subActions, 'prefixlist');

		// Any Final page details
		$context['sub_action'] = $subAction;

		// Now go!
		$action->dispatch($subAction);
	}

	public function action_addprefix()
	{
		checkSession();

		$prefix = new TopicPrefix();
		$prefix_id = $this->_req->getPost('prefix_id', 'intval', 0);

		foreach ($this->_req->getPost('topics') as $topic)
		{
			$prefix->updateTopicPrefix((int) $topic, $prefix_id);
		}
		// I'm currently too lazy to implement any return... sorry. O:-)
		die();
	}

	/**
	 * Callback for createList()
	 *
	 * @param int $start The item to start with (for pagination purposes)
	 * @param int $items_per_page The number of items to show per page
	 * @param string $sort A string indicating how to sort the results
	 * @return array
	 */
	public function list_loadTopicPrefix($start, $items_per_page, $sort)
	{
		$data = $this->px_manager->getAllPrefixes($start, $items_per_page, $sort);

		$return = array();
		foreach ($data as $row)
		{
			$return[] = array(
				'id_prefix' => $row['id_prefix'],
				'prefix' => $row['prefix'],
				'num_topics' => $row['num_topics'],
			);
		}

		return $return;
	}

	/**
	 * Callback for createList()
	 */
	public function list_getNumPrefixes()
	{
		return $this->px_manager->countAllPrefixes();
	}

	/**
	 * Show the list of topics in this board, along with any sub-boards.
	 *
	 * This is mostly a copy of action_messageindex() from MessageIndex.controller but
	 * in place of a topic listing from a board, we instead provide a top listing from
	 * a certain prefix.
	 *
	 * @uses MessageIndex template topic_listing sub template
	 */
	public function action_prefixedtopics()
	{
		global $txt, $scripturl, $modSettings, $context;
		global $options, $settings, $user_info, $board;

		loadTemplate('MessageIndex');
		loadJavascriptFile('topic.js');

		// First of all we are going to deal with an id, otherwise default action.
		$prefix_id = $this->_req->getQuery('id', 'intval', 0);
		if (empty($prefix_id))
		{
			$this->action_prefixlist();

			return;
		}

		// And the prefix ID, it best be valid
		$prefix_info = $this->px_manager->getPrefixDetails($prefix_id);
		if (empty($prefix_info))
		{
			$this->action_prefixlist();

			return;
		}

		// How many topics do we have in total
		$prefix_info['count'] = $this->px_manager->tm->getCountBoardPrefix($prefix_id);

		$context['name'] = $prefix_info['text'];
		$context['sub_template'] = 'topic_listing';
		$template_layers = Template_Layers::instance();

		// View all the topics, or just a few?
		$context['topics_per_page'] = empty($modSettings['disableCustomPerPage']) && !empty($options['topics_per_page']) ? $options['topics_per_page'] : $modSettings['defaultMaxTopics'];
		$context['messages_per_page'] = empty($modSettings['disableCustomPerPage']) && !empty($options['messages_per_page']) ? $options['messages_per_page'] : $modSettings['defaultMaxMessages'];
		$maxindex = isset($this->_req->query->all) && !empty($modSettings['enableAllMessages']) ? $prefix_info['count'] : $context['topics_per_page'];

		// Right, let's only index normal stuff!
		$session_name = session_name();
		foreach ($this->_req->query as $k => $v)
		{
			// Don't index a sort result etc.
			if (!in_array($k, array('start', $session_name)))
			{
				$context['robot_no_index'] = true;
			}
		}

		if (!empty($this->_req->query->start) && (!is_numeric($this->_req->query->start) || $this->_req->query->start % $context['messages_per_page'] != 0))
		{
			$context['robot_no_index'] = true;
		}

		// And now, what we're here for: topics!
		require_once(SUBSDIR . '/MessageIndex.subs.php');

		// Known sort methods.
		$sort_methods = messageIndexSort();
		$default_sort_method = 'last_post';

		// We only know these.
		if (isset($this->_req->query->sort) && !isset($sort_methods[$this->_req->query->sort]))
		{
			$this->_req->query->sort = $default_sort_method;
		}

		// Make sure the starting place makes sense and construct the page index.
		if (isset($this->_req->query->sort))
		{
			$sort_string = ';sort=' . $this->_req->query->sort . (isset($this->_req->query->desc) ? ';desc' : '');
		}
		else
		{
			$sort_string = '';
		}

		$context['page_index'] = constructPageIndex($scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . $sort_string, $this->_req->query->start, $prefix_info['count'], $maxindex);
		$context['start'] = &$this->_req->query->start;

		// Set a canonical URL for this page.
		$context['canonical_url'] = $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . $context['start'];

		$context['links'] += array(
			'prev' => $this->_req->query->start >= $context['topics_per_page'] ? $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . ($this->_req->query->start - $context['topics_per_page']) : '',
			'next' => $this->_req->query->start + $context['topics_per_page'] < $prefix_info['count'] ? $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . ($this->_req->query->start + $context['topics_per_page']) : '',
		);

		$context['page_info'] = array(
			'current_page' => $this->_req->query->start / $context['topics_per_page'] + 1,
			'num_pages' => floor(($prefix_info['count'] - 1) / $context['topics_per_page']) + 1
		);

		if (isset($this->_req->query->all) && !empty($modSettings['enableAllMessages']) && $maxindex > $modSettings['enableAllMessages'])
		{
			$maxindex = $modSettings['enableAllMessages'];
			$this->_req->query->start = 0;
		}

		$context['page_title'] = strip_tags(sprintf($txt['topicprefix_pagetitle'], $prefix_info['text'], (int) $context['page_info']['current_page']));

		// Show which prefix we are sorted by in the breadcrumbs
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

		// They didn't pick one, default to by last post descending.
		if (!isset($this->_req->query->sort) || !isset($sort_methods[$this->_req->query->sort]))
		{
			$context['sort_by'] = $default_sort_method;
			$ascending = isset($this->_req->query->asc);
		}
		// Otherwise sort by user selection and default to ascending.
		else
		{
			$context['sort_by'] = $this->_req->query->sort;
			$ascending = !isset($this->_req->query->desc);
		}

		$sort_column = $sort_methods[$context['sort_by']];

		$context['sort_direction'] = $ascending ? 'up' : 'down';
		$context['sort_title'] = $ascending ? $txt['sort_desc'] : $txt['sort_asc'];

		// Trick
		$txt['starter'] = $txt['started_by'];

		foreach ($sort_methods as $key => $val)
		{
			switch ($key)
			{
				case 'subject':
				case 'starter':
				case 'last_poster':
					$sorticon = 'alpha';
					break;
				default:
					$sorticon = 'numeric';
			}

			$context['topics_headers'][$key] = array(
				'url' => $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_id . '.' . $context['start'] . ';sort=' . $key . ($context['sort_by'] == $key && $context['sort_direction'] === 'up' ? ';desc' : ''),
				'sort_dir_img' => $context['sort_by'] == $key ? '<i class="icon icon-small i-sort-' . $sorticon . '-' . $context['sort_direction'] . '" title="' . $context['sort_title'] . '"><s>' . $context['sort_title'] . '</s></i>' : '',
			);
		}

		// Calculate the fastest way to get the topics.
		$start = (int) $this->_req->query->start;
		if ($start > ($prefix_info['count'] - 1) / 2)
		{
			$ascending = !$ascending;
			$fake_ascending = true;
			$maxindex = $prefix_info['count'] < $start + $maxindex + 1 ? $prefix_info['count'] - $start : $maxindex;
			$start = $prefix_info['count'] < $start + $maxindex + 1 ? 0 : $prefix_info['count'] - $start - $maxindex;
		}
		else
		{
			$fake_ascending = false;
		}

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
			'fake_ascending' => $fake_ascending,
			'board' => !empty($board) ? $board : null,
		);

		// Allow integration to modify / add to the $indexOptions
		call_integration_hook('integrate_prefixedtopics_topics', array(&$sort_column, &$indexOptions));

		$topics_info = $this->px_manager->getPrefixedTopics($prefix_id, $user_info['id'], $start, $maxindex, $context['sort_by'], $sort_column, $indexOptions);

		$context['topics'] = Topic_Util::prepareContext($topics_info, false, !empty($modSettings['preview_characters']) ? $modSettings['preview_characters'] : 128);

		// Allow addons to add to the $context['topics']
		call_integration_hook('integrate_messageindex_listing', array($topics_info));

		// Fix the sequence of topics if they were retrieved in the wrong order. (for speed reasons...)
		if ($fake_ascending)
		{
			$context['topics'] = array_reverse($context['topics'], true);
		}

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
	}

	/**
	 * Generates a listing of all the prefixed topics on the system
	 *
	 * @throws \Elk_Exception
	 */
	public function action_prefixlist()
	{
		global $context, $txt, $scripturl;

		// Build the list
		$listOptions = array(
			'id' => 'prefix_list',
			'title' => $txt['topicprefix_pagetitle_list'],
			'items_per_page' => 25,
			'base_href' => $scripturl . '?action=prefix;sa=prefixedtopics',
			'default_sort_col' => 'id_prefix',
			'get_items' => array(
				'function' => array($this, 'list_loadTopicPrefix'),
			),
			'get_count' => array(
				'function' => array($this, 'list_getNumPrefixes'),
			),
			'no_items_label' => $txt['topic_alert_none'],
			'columns' => array(
				'id_prefix' => array(
					'header' => array(
						'value' => $txt['prefix_name'],
					),
					'data' => array(
						'function' => function ($row) {
							return '<span class="prefix_id_' . $row['id_prefix'] . '">' . $row['prefix'] . '</span>';
						},
					),
					'sort' => array(
						'default' => 'prefix',
						'reverse' => 'prefix DESC',
					),
				),
				'num_topics' => array(
					'header' => array(
						'value' => $txt['prefix_num_topics'],
					),
					'data' => array(
						'db' => 'num_topics',
					),
					'sort' => array(
						'default' => 'num_topics',
						'reverse' => 'num_topics DESC',
					),
				),
				'prefix_link' => array(
					'header' => array(
						'value' => $txt['prefix_link'],
					),
					'data' => array(
						'function' => function ($row) {
							global $scripturl;

							return '<a href="' . $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $row['id_prefix'] . '" rel="nofollow">' . $row['prefix'] . '</a>';
						},
					),
				),
			)
		);

		createList($listOptions);

		// Time for the template
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'prefix_list';
		$context['page_title'] = $txt['topicprefix_pagetitle_list'];
	}
}
