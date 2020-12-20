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
	var sIcon = elk_images_url + '/helptopics.png',
		sAjax_indicator = '<div class="centertext"><i class="icon icon-spin i-spinner"></i></div>';

	// The save icon, looks like a floppy disk ... what ?
	var $icon = $('<i class="icon i-save success"></i>').click(function (e)
	{
		var $icon = $(this);

		// Save if the icon has changed state
		if ($icon.hasClass('softalert'))
		{
			var values = {
				pid: $icon.data('pid'),
				prefix_name: $icon.data('value')
			};
			values[elk_session_var] = elk_session_id;

			$.ajax({
				url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=savenames;xml;api=json',
				type: 'POST',
				dataType: 'json',
				data: values,
				cache: false
			})
			.done(function (resp)
			{
				// json response from the server says success?
				if (resp.success === true)
				{
					$icon.removeClass('softalert').addClass('success');
				}
				// Some failure trying to process the request
				else
				{
					alert(resp.error);
				}
			})
			.fail(function (err, textStatus, errorThrown)
			{
				if ('console' in window)
				{
					window.console.error('Error:', textStatus, errorThrown.name);
					window.console.error(err.responseText);
				}
				else
					alert(err.responseText);
			});
		}
	});

	// The eyedropper style editor icon
	var $style = $('<i class="icon i-eyedropper success"></i>').click(function (e)
	{
		var $stylebtn = $(this),
			stylePicker = new smc_Popup({
				heading: prefix_style_header,
				content: sAjax_indicator,
				icon: sIcon,
				custom_class: 'prefixpopup'
			});

		// Load the body of the popup to show the stylers
		$.ajax({
			url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=picker;xml;api=xml',
			type: 'POST',
			dataType: 'html',
			data: {pid: $stylebtn.data('pid')},
		})
		.done(function (resp)
		{
			var $content = $(resp),
				$demo = $('<span class="demotext" />').html($stylebtn.data('value'));

			stylePicker.body($content);

			// Define our help icons
			$content.find('.smalltext').each(function ()
			{
				var $text = $(this),
					$elem = $text.parent();

				$elem.find('*').first()
					.before($('<img class="prefixhelp" />')
						.attr('src', sIcon)
						.click(function ()
						{
							$text.slideToggle();
						})
					);
			}).hide();

			// A submit button will come in handy
			$content.last().after($('<input class="right_submit" />')
				.attr('type', 'submit')
				.val(prefix_save_button)
				.click(function ()
				{
					var values = {style_picker_vals: {}};

					// Load the data that we will post
					$content.find('select, input:not(:hidden)').each(function ()
					{
						if ($(this).is(':checkbox'))
							values.style_picker_vals[$(this).attr('name').replace(/\w+\[(.*)\]/, '$1')] = Number($(this).is(':checked'));
						else
							values.style_picker_vals[$(this).attr('name').replace(/\w+\[(.*)\]/, '$1')] = $(this).val();
					});
					values[elk_session_var] = elk_session_id;
					values['pid'] = $stylebtn.data('pid');
					values['prefix_name'] = $stylebtn.data('value');
					console.log(values);
/*
style_picker_vals:
background: "#80ff80"
border-color: "#000000"
border-radius: "4px"
border-style: "4"
border-width: "1px"
box-shadow: ""
color: "#000000"
default_background: 0
default_border-color: 1
default_color: 1
font-size: "6"
margin: ""
padding: "2px"
text-shadow: ""
*/
					// Saves the styles applied
					$.ajax({
						url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=savestyles;xml;api=json',
						type: 'POST',
						dataType: 'json',
						data: values,
						cache: false
					})
					.done(function (resp)
					{
						// json response from the server says success?
						if (resp.success === true)
						{
							stylePicker.hide();
						}
						// Some failure trying to process the request
						else
						{
							alert(resp.error);
						}
					})
					.fail(function (err, textStatus, errorThrown)
					{
						if ('console' in window)
						{
							window.console.error('Error:', textStatus, errorThrown.name);
							window.console.error(err.responseText);
						}
						else
							alert(err.responseText);
					})
				})
			).after(
				$('<span class="prefixdemo" />').append($demo)
			);

			// Update our example text when they enter/change style values
			$content.find('select, input:not(:hidden)').each(function ()
			{
				$(this).on('change', function ()
				{
					var style = '', defaults = [];
					$content.find('select, input:not(:hidden)').each(function ()
					{
						var $elem = $(this),
							name = $elem.attr('name').replace(/\w+\[(.*)\]/, '$1');

						if (name.substr(0, 8) === 'default_')
						{
							defaults[name.substr(8)] = $elem.prop('checked');
							return;
						}

						if ((!defaults.hasOwnProperty(name) || (defaults.hasOwnProperty(name) && defaults[name] == false)) && ($elem.val() != '' && $elem.val() != 0))
						{
							if ($elem.is('input'))
							{
								style += name + ':' + $elem.val() + ';';
							}
							else
							{
								style += name + ':' + $elem.children("option").filter(":selected").text() + ';';
							}
						}
						$demo.attr('style', style);
					});
				});
			});
			$content.find('select').change();
		})
		.fail(function (err, textStatus, errorThrown)
		{
			if ('console' in window)
			{
				window.console.error('Error:', textStatus, errorThrown.name);
				window.console.error(err.responseText);
			}
			else
				alert(err.responseText);
		});
	});

	// The board picker button
	var $boards = $('<i class="icon i-checkbox success"></i>').click(function (e)
	{
		var $boardsbtn = $(this),
			stylePicker = new smc_Popup({
				heading: prefix_boards_header,
				content: sAjax_indicator,
				icon: sIcon,
				custom_class: 'prefixpopup'
			});

		// Load the body of the popup to show the board selection
		$.ajax({
			url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=pickboards;xml;api=xml',
			type: 'POST',
			dataType: 'html',
			data: {pid: $boardsbtn.data('pid')},
			cache: false
		})
		.done(function (resp)
		{
			var $content = $(resp);

			stylePicker.body($content);

			// Add a way to save the form
			$content.last().after($('<input class="right_submit" />')
				.attr('type', 'submit')
				.val(prefix_save_button)
				.click(function ()
				{
					// Load the values to post
					var values = {};
					$content.find('input').each(function ()
					{
						if ($(this).prop('checked'))
						{
							values[$(this).attr('name')] = $(this).val();
						}
					});
					values[elk_session_var] = elk_session_id;
					values['pid'] = $boardsbtn.data('pid');
					values['prefix_name'] = $boardsbtn.data('value');

					// Saves the boards
					$.ajax({
						url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=saveboards;xml;api=json',
						type: 'POST',
						dataType: 'json',
						data: values,
						cache: false
					})
					.done(function (resp)
					{
						// json response from the server says success?
						if (resp.success === true)
						{
							stylePicker.hide();
						}
						// Some failure trying to process the request
						else
						{
							alert(resp.error);
						}
					})
					.fail(function (err, textStatus, errorThrown)
					{
						// Failure in save request
						if ('console' in window)
						{
							window.console.error('Error:', textStatus, errorThrown.name);
							window.console.error(err.responseText);
						}
						else
							alert(err.responseText);
					})
				})
			);
		})
		.fail(function (err, textStatus, errorThrown)
		{
			// Failure in fetching the form to display
			if ('console' in window)
			{
				window.console.error('Error:', textStatus, errorThrown.name);
				window.console.error(err.responseText);
			}
			else
				alert(err.responseText);
		});
	});

	// For each topicprefix, add the editing icons + onchange event for the name
	$('.prefix_edit').each(function ()
	{
		var name = $(this).attr('name').replace(/[^\d]/g, ''),
			sValue = $(this).val();

		$(this)
			.after($boards.clone(true).data('pid', name).data('value', sValue))
			.after($style.clone(true).data('pid', name).data('value', sValue))
			.after($icon.clone(true).data('pid', name).data('value', sValue))
			.on('change', function (e)
			{
				var $icon = $(this).next();

				$icon.removeClass('success').addClass('softalert');
				$icon.data('value', $(this).val());
				e.preventDefault();
			})
			.on('keyup', function (e)
			{
				var $icon = $(this).next();

				$icon.removeClass('success').addClass('softalert');
				e.preventDefault();
			});
	});
});

// Hide the popup
smc_Popup.prototype.body = function (body)
{
	$(this.popup_body).find('.popup_content').html(body);

	return false;
};
