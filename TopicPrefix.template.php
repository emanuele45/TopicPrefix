<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

function template_pickprefix_above()
{
	global $context, $txt;

	if (empty($context['available_prefixes']))
		return;

	// The post header... important stuff
	echo '
						<dl id="post_header">
							<dt class="clear_left">
								<label for="post_in_board">', $txt['topicprefix_pickprefix'], '</label>
							</dt>
							<dd>
								<select name="prefix">';

	foreach ($context['available_prefixes'] as $id => $prefix)
		echo '
									<option value="', $id, '"', $prefix['selected'] ? ' selected="selected"' : '', '>', $prefix['text'], '</option>';

	echo '
								</select>
							</dd>
						</dl>';
}

function template_topicprefix_list()
{
	global $context, $txt;

}

function template_manage_topicprefix()
{
	global $context, $txt, $modSettings;

	echo '
		<div class="managetopicprefix" id="admincenter">
			<form id="admin_form_wrapper" action="', $context['topicprefix_action'], '" method="post" accept-charset="UTF-8">
				<h3 class="category_header">', $txt['topicprefix_manage_header'], '</h3>
				<div class="windowbg2">
					<div class="content">
						<dl class="settings">
							<dt>prefix</dt><dd>' . $txt['choose_board'] . '</dd>';

	$deny_boards_access = $modSettings['deny_boards_access'];
	$modSettings['deny_boards_access'] = false;
	foreach ($context['topicprefix'] as $prefix_id => $prefix)
	{
		echo '
							<dt><input type="text" name="prefix[', $prefix_id, ']" value="', $prefix['text'], '" /></dt>
							<dd>';
		if (empty($prefix['boards']))
			echo 'all boards';
		else
		{
			$count = count($prefix['boards']);
			$pos = 0;
			foreach ($prefix['boards'] as $board)
			{
				$pos++;
				echo $board['name'], $count == $pos ? '' : ', ';
			}
		}

		echo ' <a href="#">change</a>
							</dd>';
	}
	$modSettings['deny_boards_access'] = $deny_boards_access;

	echo '
						</dl>
					</div>
				</div>
			</form>
		</div>';
}