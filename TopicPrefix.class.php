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

class TopicPrefix
{
	public function loadAll()
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

		// If we are editing a message, we may want to know the old prefix
		if (isset($_REQUEST['msg']))
		{
			$prefix = $this->getTopicPrefixes($topic);
		}

		$context['available_prefixes'] = $this->loadPrefixes(isset($prefix['id_prefix']) ? $prefix['id_prefix'] : null, $board);
	}

	public function updateTopicPrefix($topic, $prefix_id)
	{
		$db = database();

		$request = $db->query('', '
			SELECT id_prefix
			FROM {db_prefix}topic_prefix
			WHERE id_topic = {int:this_topic}',
			array(
				'this_topic' => $topic,
			)
		);
		// @todo in order to support multi-prefix this should be expanded
		list ($current_prefix) = $db->fetch_row($request);
		$db->free_result($request);


		// If the prefix is empty, just cleanup any potential mess and live happy!
		if (empty($prefix_id))
		{
			$db->query('', '
				DELETE FROM {db_prefix}topic_prefix
				WHERE id_topic = {int:this_topic}
					AND id_prefix = {int:this_prefix}',
				array(
					'this_topic' => $topic,
					'this_prefix' => $current_prefix,
				)
			);

			return;
		}

		// If the record doesn't exist it's time to create it
		if ($db->num_rows($request) == 0)
		{
			$db->insert('',
				'{db_prefix}topic_prefix',
				array(
					'id_prefix' => 'int',
					'id_topic' => 'int',
				),
				array(
					$prefix_id,
					$topic
				),
				array('id_prefix', 'id_topic')
			);
		}
		// If we already have one, then we have to change it
		// (provided the new one is different)
		else
		{
			if ($current_prefix != $prefix_id)
			{
				$db->query('', '
					UPDATE {db_prefix}topic_prefix
					SET id_prefix = {int:new_prefix}
					WHERE id_topic = {int:this_topic}
						AND id_prefix = {int:this_prefix}',
					array(
						'this_topic' => $topic,
						'this_prefix' => $current_prefix,
						'new_prefix' => $prefix_id,
					)
				);
			}
		}
	}

	public function loadPrefixes($board = null)
	{
		global $txt;

		$db = database();
		$boards = (array) $board;
		$boards_used = array();

		if ($this->_currentTopics !== null)
			$this->_getTopicPrefixes();

		$request = $db->query('', '
			SELECT id_prefix, prefix, id_boards
			FROM {db_prefix}topic_prefix_text',
			array()
		);

		$prefixes = array(0 => array(
			'selected' => true,
			'text' => $txt['topicprefix_noprefix'],
			'id_boards' => array(),
		));
		while ($row = $db->fetch_assoc($request))
		{
			$id_boards = array();
			if (!empty($row['id_boards']))
			{
				// I could use find_in_set, but I may also change my mind later...
				$id_boards = explode(',', $row['id_boards']);
				if (!empty($boards) && count(array_intersect($boards, $id_boards)) == 0)
					continue;

				$boards_used = array_unique(array_merge($boards_used, $id_boards));
			}

			$prefixes[$row['id_prefix']] = array(
				'selected' => in_array($row['id_prefix'], $this->_prefixes),
				'text' => $row['prefix'],
				'id_boards' => $id_boards,
			);

			if ($default == $row['id_prefix'])
				$prefixes[0]['selected'] = false;
		}
		$db->free_result($request);

		if ($board === null && !empty($boards_used))
		{
			require_once(SUBSDIR . '/Boards.subs.php');
			$boardsdetail = fetchBoardsInfo(array('boards' => $boards_used));

			foreach ($prefixes as $key => $value)
			{
				$bdet = array();
				foreach ($value['id_boards'] as $id)
				{
					$bdet[] = $boardsdetail[$id];
				}
				$prefixes[$key]['boards'] = $bdet;
			}
		}

		return $prefixes;
	}

	public function setTopic($topics)
	{
		$this->_is_array = is_array($this->_currentTopics);

		if (!$this->_is_array)
			$this->_currentTopics = array($this->_currentTopics);

		$this->_currentTopics = $topics;
	}

	public function getTopicPrefixes($topics)
	{
		$db = database();

		$is_array = is_array($topics);
		if (empty($topics))
			return array();

		if (!$is_array)
			$topics = array($topics);

		$request = $db->query('', '
			SELECT p.id_topic, p.id_prefix, pt.prefix
			FROM {db_prefix}topic_prefix AS p
				LEFT JOIN {db_prefix}topic_prefix_text AS pt ON (p.id_prefix = pt.id_prefix)
			WHERE p.id_topic IN ({array_int:topics})',
			array(
				'topics' => $topics,
			)
		);

		$prefixes = array();
		while ($row = $db->fetch_assoc($request))
			$prefixes[$row['id_topic']] = $row;
		$db->free_result($request);

		if ($is_array)
			return $prefixes;
		else
			return array_shift($prefixes);
	}

	protected function _getTopicPrefixes()
	{
		$db = database();

		if (empty($this->_currentTopics))
			return array();

		$request = $db->query('', '
			SELECT id_topic, id_prefix
			FROM {db_prefix}topic_prefix
			WHERE id_topic IN ({array_int:topics})',
			array(
				'topics' => $this->_currentTopics,
			)
		);

		$this->_prefixes = array();
		while ($row = $db->fetch_assoc($request))
		{
			if (!isset($this->_prefixes[$row['id_topic']]))
				$this->_prefixes[$row['id_topic']] = array();

			$this->_prefixes[$row['id_topic']][] = $row['id_prefix'];
		}
		$db->free_result($request);
	}

	public function getPrefixDetails($prefix_id)
	{
		$db = database();

		$request = $db->query('', '
			SELECT prefix
			FROM {db_prefix}topic_prefix_text
			WHERE id_prefix = {int:prefix}',
			array(
				'prefix' => $prefix_id
			)
		);
		list ($text) = $db->fetch_row($request);
		$db->free_result($request);

		// An empty prefix cannot exists (well, a prefix 0 could), so out of here!
		if (empty($text) && $text == '')
			return false;

		$request = $db->query('', '
			SELECT COUNT(*)
			FROM {db_prefix}topic_prefix
			WHERE id_prefix = {int:prefix}',
			array(
				'prefix' => $prefix_id
			)
		);
		list ($count) = $db->fetch_row($request);
		$db->free_result($request);

		return array('text' => $text, 'count' => $count);
	}

	public function getPrefixedTopics($prefix_id, $id_member, $start, $limit, $sort_by, $sort_column, $indexOptions)
	{
		$db = database();
		$topics = array();

		$request = $db->query('', '
			SELECT t.id_topic
			FROM {db_prefix}topic_prefix AS p
				LEFT JOIN {db_prefix}topics AS t ON (p.id_topic = t.id_topic)
				LEFT JOIN {db_prefix}boards AS b ON (t.id_board = b.id_board)' . ($sort_by === 'last_poster' ? '
				INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)' : (in_array($sort_by, array('starter', 'subject')) ? '
				INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)' : '')) . ($sort_by === 'starter' ? '
				LEFT JOIN {db_prefix}members AS memf ON (memf.id_member = mf.id_member)' : '') . ($sort_by === 'last_poster' ? '
				LEFT JOIN {db_prefix}members AS meml ON (meml.id_member = ml.id_member)' : '') . '
			WHERE {query_see_board}
				AND p.id_prefix = {int:current_prefix}' . (!$indexOptions['only_approved'] ? '' : '
				AND (t.approved = {int:is_approved}' . ($id_member == 0 ? '' : ' OR t.id_member_started = {int:current_member}') . ')') . '
			ORDER BY ' . ($indexOptions['include_sticky'] ? 't.is_sticky' . ($indexOptions['fake_ascending'] ? '' : ' DESC') . ', ' : '') . $sort_column . ($indexOptions['ascending'] ? '' : ' DESC') . '
			LIMIT {int:start}, {int:maxindex}',
			array(
				'current_prefix' => $prefix_id,
				'current_member' => $id_member,
				'is_approved' => 1,
				'id_member_guest' => 0,
				'start' => $start,
				'maxindex' => $limit,
			)
		);
		$topic_ids = array();
		while ($row = $db->fetch_assoc($request))
			$topic_ids[] = $row['id_topic'];
		$db->free_result($request);

		// And now, all you ever wanted on message index...
		// and some you wish you didn't! :P
		if (!empty($topic_ids))
		{
			// If empty, no preview at all
			if (empty($indexOptions['previews']))
				$preview_bodies = '';
			// If -1 means everything
			elseif ($indexOptions['previews'] === -1)
				$preview_bodies = ', ml.body AS last_body, mf.body AS first_body';
			// Default: a SUBSTRING
			else
				$preview_bodies = ', SUBSTRING(ml.body, 1, ' . ($indexOptions['previews'] + 256) . ') AS last_body, SUBSTRING(mf.body, 1, ' . ($indexOptions['previews'] + 256) . ') AS first_body';

			$request = $db->query('substring', '
				SELECT
					t.id_topic, t.num_replies, t.locked, t.num_views, t.num_likes, t.is_sticky, t.id_poll, t.id_previous_board,
					' . ($id_member == 0 ? '0' : 'IFNULL(lt.id_msg, IFNULL(lmr.id_msg, -1)) + 1') . ' AS new_from,
					t.id_last_msg, t.approved, t.unapproved_posts, t.id_redirect_topic, t.id_first_msg,
					ml.poster_time AS last_poster_time, ml.id_msg_modified, ml.subject AS last_subject, ml.icon AS last_icon,
					ml.poster_name AS last_member_name, ml.id_member AS last_id_member, ml.smileys_enabled AS last_smileys,
					IFNULL(meml.real_name, ml.poster_name) AS last_display_name,
					mf.poster_time AS first_poster_time, mf.subject AS first_subject, mf.icon AS first_icon,
					mf.poster_name AS first_member_name, mf.id_member AS first_id_member, mf.smileys_enabled AS first_smileys,
					IFNULL(memf.real_name, mf.poster_name) AS first_display_name
					' . $preview_bodies . '
					' . (!empty($indexOptions['include_avatars']) ? ' ,meml.avatar ,IFNULL(a.id_attach, 0) AS id_attach, a.filename, a.attachment_type, meml.email_address' : '') .
					(!empty($indexOptions['custom_selects']) ? ' ,' . implode(',', $indexOptions['custom_selects']) : '') . '
				FROM {db_prefix}topic_prefix AS p
					LEFT JOIN {db_prefix}topics AS t ON (p.id_topic = t.id_topic)
					INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
					INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
					LEFT JOIN {db_prefix}members AS meml ON (meml.id_member = ml.id_member)
					LEFT JOIN {db_prefix}members AS memf ON (memf.id_member = mf.id_member)' . ($id_member == 0 ? '' : '
					LEFT JOIN {db_prefix}log_topics AS lt ON (lt.id_topic = t.id_topic AND lt.id_member = {int:current_member})
					LEFT JOIN {db_prefix}log_mark_read AS lmr ON (lmr.id_board = t.id_board AND lmr.id_member = {int:current_member})') . (!empty($indexOptions['include_avatars']) ? '
					LEFT JOIN {db_prefix}attachments AS a ON (a.id_member = ml.id_member AND a.id_member != 0)' : '') . '
				WHERE p.id_prefix = {int:current_prefix}' . (!$indexOptions['only_approved'] ? '' : '
					AND (t.approved = {int:is_approved}' . ($id_member == 0 ? '' : ' OR t.id_member_started = {int:current_member}') . ')') . '
				ORDER BY ' . ($indexOptions['include_sticky'] ? 'is_sticky' . ($indexOptions['fake_ascending'] ? '' : ' DESC') . ', ' : '') . $sort_column . ($indexOptions['ascending'] ? '' : ' DESC') . '
				LIMIT {int:start}, ' . '{int:maxindex}',
				array(
					'current_prefix' => $prefix_id,
					'current_member' => $id_member,
					'topic_list' => $topic_ids,
					'is_approved' => 1,
					'find_set_topics' => implode(',', $topic_ids),
					'start' => $start,
					'maxindex' => $limit,
				)
			);

			// Lets take the results
			while ($row = $db->fetch_assoc($request))
				$topics[$row['id_topic']] = $row;

			$db->free_result($request);
		}

		return $topics;

	}

	function getAllPrefixes($start, $limit, $sort, $sort_dir)
	{
		$db = database();

		$request = $db->query('', '
			SELECT p.id_prefix, pt.prefix, COUNT(p.id_topic) as num_topics
			FROM {db_prefix}topics AS t
				INNER JOIN {db_prefix}boards AS b ON (t.id_board = b.id_board AND {query_see_board})
				LEFT JOIN {db_prefix}topic_prefix AS p ON (t.id_topic = p.id_topic)
				LEFT JOIN {db_prefix}topic_prefix_text AS pt ON (pt.id_prefix = p.id_prefix)
			GROUP BY p.id_prefix
			HAVING num_topics > 0
			ORDER BY {raw:sort} {raw:dir}
			LIMIT {int:start}, {int:limit}',
			array(
				'sort' => $sort,
				'dir' => $sort_dir,
				'start' => $start,
				'limit' => $limit,
			)
		);
		$return = array();
		while ($row = $db->fetch_assoc($request))
			$return[$row['id_prefix']] = $row;
		$db->free_result($request);

		return $return;
	}

	function countAllPrefixes()
	{
		$db = database();

		$request = $db->query('', '
			SELECT COUNT(*)
			FROM {db_prefix}topic_prefix_text',
			array()
		);
		list ($count) = $db->num_rows($request);
		$db->free_result($request);

		return $count;
	}
}