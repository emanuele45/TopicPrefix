/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.5
 */

/**
 * Sets up all the js events for edit and save board-specific permission
 * profiles
 */
$(document).ready(function ()
{
	var $selector = $('<select name="prefix_id">'),
		generated = false,
		addingprefix = false;

	$('.qaction').on('change', function ()
	{
		var $element = $(this),
			$input = $element.parent().find('input');
		var original_evt = $input.attr('onclick');
		$input.prop('onclick', null).off('click');
		$input.click(function (e) {
			if (addingprefix === true)
			{
				e.preventDefault();
				var topics = [];
				$('#quickModForm').find('input[name*=topics]').each(function(idx, val) {
					if (val.checked === true)
					{
						topics.push(val.value);
					}
				});

				var data = {
					topics: topics,
					prefix_id: $selector.val()
				};
				data[elk_session_var] = elk_session_id;
				$.ajax({
					type: "POST",
					url: elk_scripturl + "?action=prefix;sa=addprefix;api;xml;" + elk_session_var + '=' + elk_session_id,
					beforeSend: ajax_indicator(true),
					data: data,
					context: document.body
				})
				.done(function(request) {
					location.reload(true);
				})
				.always(function(request) {
					ajax_indicator(false);
				});
			}
			else
			{
				eval(original_evt);
			}
		});

		if (generated == false)
		{
			$(quick_prefixes).each(function(idx, val) {
				$selector.append($('<option value="' + val['id'] + '">' + val['text'] + '</option>'));
			});
			$element.after($selector);
			$selector.hide();
			generated = true;
		}
		if ($(this).val() == 'addprefix')
		{
			$selector.show();
			addingprefix = true;
		}
		else
		{
			$selector.hide();
			addingprefix = false;
		}
	});
});
