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