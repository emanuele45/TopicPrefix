<?php

/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

class Topic_Prefix_Integrate
{
	public static function messageindex_listing($topicsinfo)
	{
		require_once(SUBSDIR . '/TopicPrefix.subs.php');
		topicprefix_showprefix($topicsinfo);
	}

	public static function post_after()
	{
		global $context, $topic;

		// This has a meaning only for first posts
		// that means new topics or editing the first message
		if (!$context['is_first_post'])
			return;

		// All the template stuff
		loadTemplate('TopicPrefix');
		loadLanguage('TopicPrefix');
		Template_Layers::getInstance()->addAfter('pickprefix', 'postarea');
		require_once(SUBSDIR . '/TopicPrefix.subs.php');

		// If we are editing a message, we may want to know the old prefix
		if (isset($_REQUEST['msg']))
		{
			$prefix = topicprefix_getTopicPrefixes($topic);
		}

		$context['available_prefixes'] = topicprefix_getPrefixes(isset($prefix['id_prefix']) ? $prefix['id_prefix'] : null);
	}

	public static function create_topic($msgOptions, $topicOptions, $posterOptions)
	{
		$prefix_id = isset($_POST['prefix']) ? (int) $_POST['prefix'] : 0;

		require_once(SUBSDIR . '/TopicPrefix.subs.php');
		topicprefix_updateTopicPrefix($topicOptions['id'], $prefix_id);
	}

	public static function before_modify_post($topics_columns, $update_parameters, $msgOptions, $topicOptions, $posterOptions)
	{
		$prefix_id = isset($_POST['prefix']) ? (int) $_POST['prefix'] : 0;
		$msgInfo = basicMessageInfo($_REQUEST['msg'], true, true);

		// Update the prefix, but only if it is the first message in the topic
		if ($msgInfo['id_first_msg'] == $msgOptions['id'])
		{
			require_once(SUBSDIR . '/TopicPrefix.subs.php');
			topicprefix_updateTopicPrefix($topicOptions['id'], $prefix_id);
		}
	}
}