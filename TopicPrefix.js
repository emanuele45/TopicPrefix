/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.2
 */

/**
 * Sets up all the js events for edit and save board-specific permission
 * profiles
 */
$(document).ready(function() {
	var sIcon = elk_images_url + '/helptopics.png',
		sAjax_indicator = '<div class="centertext"><i class="fa fa-spinner fa-spin"></i></div>';
	var $icon = $('<i class="fa fa-floppy-o fa-lg success"></i>').click(function(e) {
		var $icon = $(this);

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
			.done(function(resp) {
				// json response from the server says success?
				if (resp.success === true) {
					$icon.removeClass('softalert').addClass('success');
				}
				// Some failure trying to process the request
				else
					alert(resp.error);
			})
			.fail(function(err, textStatus, errorThrown) {
				alert(err);
			});
		}
	});
	var $style = $('<i class="fa fa-eyedropper fa-lg success"></i>').click(function(e) {
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
			cache: false
		})
		.done(function(resp) {
			var $content = $(resp),
			$demo = $('<span class="demotext" />').html($stylebtn.data('value'));

			stylePicker.body($content);

			$content.find('.smalltext').each(function() {
				var $text = $(this),
					$elem = $text.parent();
				$elem.find('*').first()
					.before($('<img class="prefixhelp" />')
						.attr('src', sIcon)
						.click(function() {
							$text.slideToggle();
						}));
			}).hide();
			$content.after($('<input class="right_submit" />')
				.attr('type', 'submit')
				.val('Save')
				.click(function() {
					var values = {style_picker_vals: {}};
					$content.find('select, input:not(:hidden)').each(function () {
						values.style_picker_vals[$(this).attr('name').replace(/\w+\[(.*)\]/, '$1')] = $(this).val();
					});
					values[elk_session_var] = elk_session_id;
					values['pid'] = $stylebtn.data('pid');

					// Saves the styles applied
					$.ajax({
						url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=savestyles;xml;api=json',
						type: 'POST',
						dataType: 'json',
						data: values,
						cache: false
					})
					.done(function(resp) {
						// json response from the server says success?
						if (resp.success === true)
							stylePicker.hide();
						// Some failure trying to process the request
						else
							alert(resp.error);
					})
					.fail(function(err, textStatus, errorThrown) {
						alert(err);
					})
				})
			).after(
				$('<span class="prefixdemo" />').append($demo)
			);
			$content.find('select, input:not(:hidden)').each(function() {
				$(this).on('change', function() {
					var style = '', defaults = [];
					$content.find('select, input:not(:hidden)').each(function () {
						var $elem = $(this),
							name = $elem.attr('name').replace(/\w+\[(.*)\]/, '$1');
						if (name.substr(0, 8) == 'default_')
						{
							defaults[name.substr(8)] = $elem.prop('checked');
							return;
						}
						if ((!defaults.hasOwnProperty(name) || (defaults.hasOwnProperty(name) && defaults[name] == false)) && ($elem.val() != '' && $elem.val() != 0))
						{
							if ($elem.is('input'))
								style += name + ':' + $elem.val() + ';';
							else
								style += name + ':' + $elem.children("option").filter(":selected").text() + ';';
						}
						$demo.attr('style', style);
					});
				});
			});
			$content.find('select').change();
		})
		.fail(function(err, textStatus, errorThrown) {
			alert(err);
		});
	});
	var $boards = $('<i class="fa fa-edit fa-lg success"></i>').click(function(e) {
		var $boardsbtn = $(this),
			stylePicker = new smc_Popup({
				heading: prefix_style_header,
				content: sAjax_indicator,
				icon: sIcon,
				custom_class: 'prefixpopup'
			});

		// Load the body of the popup to show the stylers
		$.ajax({
			url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=pickboards;xml;api=xml',
			type: 'POST',
			dataType: 'html',
			data: {pid: $boardsbtn.data('pid')},
			cache: false
		})
		.done(function(resp) {
			var $content = $(resp);

			stylePicker.body($content);

			$content.after($('<input class="right_submit" />')
				.attr('type', 'submit')
				.val('Save')
				.click(function() {
					var values = {};
					$content.find('input').each(function () {
						if ($(this).prop('checked'))
							values[$(this).attr('name')] = $(this).val();
					});
					values[elk_session_var] = elk_session_id;
					values['pid'] = $boardsbtn.data('pid');

					// Saves the boards
					$.ajax({
						url: elk_scripturl + '?action=admin;area=postsettings;sa=prefix;do=saveboards;xml;api=json',
						type: 'POST',
						dataType: 'json',
						data: values,
						cache: false
					})
					.done(function(resp) {
						// json response from the server says success?
						if (resp.success === true)
							stylePicker.hide();
						// Some failure trying to process the request
						else
							alert(resp.error);
					})
					.fail(function(err, textStatus, errorThrown) {
						alert(err);
					})
				})
			);
		})
		.fail(function(err, textStatus, errorThrown) {
			alert(err);
		});
	});

	$('.prefix_edit').each(function() {
		var name = $(this).attr('name').replace(/[^\d]/g, ''),
			sValue = $(this).val();
		$(this)
		.after($boards.clone(true).data('pid', name).data('value', sValue))
		.after($style.clone(true).data('pid', name).data('value', sValue))
		.after($icon.clone(true).data('pid', name).data('value', sValue))
		.on('change.elkarte', function(e) {
			var $icon = $(this).next();

			$icon.removeClass('success').addClass('softalert');
			e.preventDefault();
		})
		.on('keyup.elkarte', function(e) {
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
