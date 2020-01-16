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
 * For a given set of topics, load any prefixes they may have and prepare
 * the template context to show the formatted prefix
 *
 * precondition on $context['topics'] being populated via Topic_Util::prepareContext
 *
 * @param array $topicsinfo
 */
function topicprefix_showprefix($topicsinfo)
{
	global $context;

	$px_manager = new TopicPrefix();
	$prefixes = $px_manager->getTopicPrefixes(array_keys($topicsinfo));
	$has_prefix = false;

	foreach ($context['topics'] as $topic => $values)
	{
		if (isset($prefixes[$topic]))
		{
			$has_prefix = true;
			$prefix_markup = topicprefix_prefix_marktup($prefixes[$topic]);

			$context['topics'][$topic]['first_post']['link'] = $prefix_markup . $context['topics'][$topic]['first_post']['link'];
			$context['topics'][$topic]['subject'] = $prefix_markup . $context['topics'][$topic]['subject'];
		}
	}

	if ($has_prefix)
	{
		loadCSSFile('TopicPrefix.css');
	}
}

/**
 * Used to replace the prefix_style template with current prefix data. Prefix_style
 * is installed by default in $modSettings as
 * '<span class="topicprefix {prefix_class}">{prefix_link}</span>&nbsp;'
 *
 * @param array $prefix_info
 * @param null|int $board
 * @return string
 */
function topicprefix_prefix_marktup($prefix_info, $board = null)
{
	global $settings, $modSettings, $scripturl;

	if (!isset($settings['prefix_style']))
	{
		$prefix_style = $modSettings['prefix_style'];
	}
	else
	{
		$prefix_style = $settings['prefix_style'];
	}

	$find = array(
		'{prefix}',
		'{prefix_link}',
		'{prefix_class}',
	);

	// Set the no prefix link back to the board index
	$sa_link = $prefix_info['id_prefix'] === 0
		? '?board=' . $board . '.0'
		: '?action=prefix;sa=prefixedtopics;id=' . $prefix_info['id_prefix'] . ($board === null ? '' : ';board=' . $board);

	$replace = array(
		$prefix_info['prefix'],
		'<a href="' . $scripturl . $sa_link . '">' . $prefix_info['prefix'] . '</a>',
		'prefix_id_' . $prefix_info['id_prefix'],
	);

	return str_replace($find, $replace, $prefix_style);
}

/**
 * Load a prefix details by id
 *
 * @param int|null $prefix_id
 * @return array|bool
 */
function topicprefixLoadId($prefix_id = null)
{
	global $settings;

	$prefixManager = new TopicPrefix();
	$topicPrefix = $prefixManager->getPrefixDetails($prefix_id);

	if (!empty($prefix_id))
	{
		$topicPrefix['style'] = $prefixManager->getStyle($prefix_id, $settings['theme_dir']);
	}

	return $topicPrefix;
}
