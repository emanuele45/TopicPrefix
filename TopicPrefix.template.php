<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.2
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

function template_boardprefixes_above()
{
	global $context, $txt;
	echo '
	<div id="prefix_board">
		<span class="intro">
			', $txt['topicprefix_click_here'], '
		</span>
		<span class="generalinfo">', implode(' ', $context['prefixes_board_specific']), '
		</span>
	</div>';
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
							<dt>', $txt['topicprefix_prefix_text'], '</dt><dd>' . $txt['choose_board'] . '</dd>';

	$deny_boards_access = $modSettings['deny_boards_access'];
	$modSettings['deny_boards_access'] = false;
	foreach ($context['topicprefix'] as $prefix_id => $prefix)
	{
		echo '
							<dt>
								<input class="prefix_edit" type="text" name="prefix[', $prefix_id, ']" value="', $prefix['text'], '" />
								<a class="edit linkbutton" href="', $prefix['edit_url'], '">', $txt['edit'], '</a>
							</dt>
							<dd>';

		if (empty($prefix['boards']))
			echo $txt['no_boards'];
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
		echo '
							</dd>';
	}
	$modSettings['deny_boards_access'] = $deny_boards_access;

	echo '
							<dt>
								<a class="edit linkbutton" href="', $context['topicprefix_addnew_url'], '">', $txt['add_new'], '</a>
							</dt><dd></dd>
						</dl>
					</div>
				</div>
			</form>
		</div>';
}

function template_prefixeditboards()
{
	global $context, $txt, $modSettings;

	echo '
		<div class="managetopicprefix" id="admincenter">
			<form id="admin_form_wrapper" name="editprefix" action="', $context['topicprefix_action'], '" method="post" accept-charset="UTF-8">
				<h3 class="category_header">', $txt['topicprefix_manage_header'], '</h3>
				<div class="windowbg2">
					<div class="content">
					 <dl class="settings">
							<dt>', $txt['topicprefix_change_text'], '</dt>
							<dd>
								<input type="text" name="prefix_name" value="', $context['topicprefix']['text'], '" />
							</dd>
							<dt>', $txt['topicprefix_style'], '<br />
							<span class="smalltext">', $txt['topicprefix_style_desc'], '</span></dt>
							<dd>
								<textarea name="prefix_style">', $context['topicprefix']['style'], '</textarea>
							</dd>
						</dl>
						<fieldset id="pick_boards">';

	template_pick_boards('editprefix', 'brd', true);

	echo '
						</fieldset>
						<input type="submit" value="', $txt['save'], '" class="right_submit" />
					</div>
				</div>';
				
				
				if (!empty($context['topicprefix']['id']))
					echo '<input type="hidden" name="pid" value="', $context['topicprefix']['id'] , '" />';
				
				echo '
				<input type="hidden" name="', $context['admin-editprefix_token_var'], '" value="', $context['admin-editprefix_token'], '" />
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			</form>
		</div>';
}

function template_prefix_picker_popup()
{
	echo '
	<dl class="settings">';
	template_profile_style_picker();
	echo '
	</dl>';
}

function template_prefix_boardspicker_popup()
{
	global $context;

	echo '
	<form id="admin_form_wrapper" name="editprefix">
		<fieldset id="pick_boards">';

	template_pick_boards('editprefix', 'brd', true);

	echo '
		</fieldset>
	</form>
	<script>';
	foreach ($context['javascript_inline'] as $val)
		echo implode("\n\t\t", $context['javascript_inline']['defer']);
	echo '</script>';
}