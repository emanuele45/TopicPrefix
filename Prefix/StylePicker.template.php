<?php

/**
 * Style Picker library
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.0.0
 */

function template_profile_style_picker()
{
	global $context, $txt;

	foreach ($context['style_picker_elements'] as $name => $values)
	{
		echo '
			<dt>
				<strong><label for="style_picker_vals_', $name, '">', isset($txt['style_picker_' . $name]) ? $txt['style_picker_' . $name] : $name, '</label></strong>
				<input type="hidden" name="style_picker_picker" value="1" />';
		if (isset($txt['style_picker_' . $name . '_desc']))
			echo '
				<br />
				<span class="smalltext">', $txt['style_picker_' . $name . '_desc'], '</span>';
		echo '
			</dt>
			<dd>', template_style_picker_builder($values['type'], $name, $values), '
			</dd>';
	}
}

function template_style_picker_builder($type, $name, $values)
{
	global $txt;

	$value = $values['value'];

	switch ($type)
	{
		case 'text':
			echo '
				<input type="text" id="style_picker_vals_', $name, '" name="style_picker_vals[', $name, ']" value="', $value, '" />';
			break;
		case 'color':
			echo '
				<label for="theme_default_', $name, '">', $txt['style_picker_theme_def'], '</label>
				<input type="checkbox" ', empty($value) ? ' checked="checked"' : '', 'id="theme_default_', $name, '" name="style_picker_vals[default_', $name, ']" value="1" />
				<input type="color" id="style_picker_vals_', $name, '" name="style_picker_vals[', $name, ']" class="toggleCheck" data-name="', $name, '" value="', $value, '" />';
			break;
		case 'select':
			echo '
				<select id="style_picker_vals_', $name, '" name="style_picker_vals[', $name, ']">';
			foreach ($values['values'] as $key => $val)
				echo '
					<option value="', $key, '"', $value == $val ? ' selected="selected"' : '', '>', $val, '</option>';
			echo '
				</select>';
			break;
	}
}