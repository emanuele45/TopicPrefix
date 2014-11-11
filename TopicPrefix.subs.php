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

			$context['topics'][$topic]['first_post']['link'] =  $prefix_markup . $context['topics'][$topic]['first_post']['link'];
			$context['topics'][$topic]['subject'] = $prefix_markup . $context['topics'][$topic]['subject'];
		}
	}

	if ($has_prefix)
		loadCSSFile('TopicPrefix.css');
}

function topicprefix_prefix_marktup($prefix_info, $board = null)
{
	global $settings, $modSettings, $scripturl;

	if (!isset($settings['prefix_style']))
		$prefix_style = $modSettings['prefix_style'];
	else
		$prefix_style = $settings['prefix_style'];

	$find = array(
		'{prefix}',
		'{prefix_link}',
		'{prefix_class}',
	);

	$replace = array(
		$prefix_info['prefix'],
		'<a href="' . $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefix_info['id_prefix'] . ($board === null ? '' : ';board=' . $board) . '">' . $prefix_info['prefix'] . '</a>',
		'prefix_id_' . $prefix_info['id_prefix'],
	);
	return str_replace($find, $replace, $prefix_style);
}