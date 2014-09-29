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

function topicprefix_showprefix($topicsinfo)
{
	global $context, $settings, $modSettings, $scripturl;

	if (!isset($settings['prefix_style']))
		$prefix_style = $modSettings['prefix_style'];
	else
		$prefix_style = $settings['prefix_style'];

	require_once(SUBSDIR . '/TopicPrefix.class.php');
	$px_manager = new TopicPrefix();
	$prefixes = $px_manager->getTopicPrefixes(array_keys($topicsinfo));
	$has_prefix = false;
	foreach ($context['topics'] as $topic => $values)
	{
		if (isset($prefixes[$topic]))
		{
			$has_prefix = true;
			$find = array(
				'{prefix}',
				'{prefix_link}',
			);
			$replace = array(
				$prefixes[$topic]['prefix'],
				'<a href="' . $scripturl . '?action=prefix;sa=prefixedtopics;id=' . $prefixes[$topic]['id_prefix'] . '">' . $prefixes[$topic]['prefix'] . '</a>',
			);
			$prefix_markup = str_replace($find, $replace, $prefix_style);
			$context['topics'][$topic]['first_post']['link'] = 
			$prefix_markup . $context['topics'][$topic]['first_post']['link'];
			$context['topics'][$topic]['subject'] = $prefix_markup . $context['topics'][$topic]['subject'];
		}
	}

	if ($has_prefix)
		loadCSSFile('TopicPrefix.css');
}