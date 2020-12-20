<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.5
 */

global $hooks, $mod_name;

// Hooks there are a plenty!
$hooks = array(
	array(
		'integrate_messageindex_listing',
		'Topic_Prefix_Integrate::messageindex_listing',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	
	array(
		'integrate_quick_mod_actions',
		'Topic_Prefix_Integrate::quick_mod_actions',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),	

	array(
		'integrate_quick_mod_actions',
		'Topic_Prefix_Integrate::quick_mod_actions',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	array(
		'integrate_action_post_after',
		'Topic_Prefix_Integrate::post_after',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	array(
		'integrate_display_message_list',
		'Topic_Prefix_Integrate::display_message_list',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	array(
		'integrate_create_topic',
		'Topic_Prefix_Integrate::create_topic',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	array(
		'integrate_before_modify_post',
		'Topic_Prefix_Integrate::before_modify_post',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	array(
		'integrate_admin_areas',
		'Topic_Prefix_Integrate::admin_areas',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	array(
		'integrate_sa_manage_posts',
		'Topic_Prefix_Integrate::sa_manage_posts',
		'SOURCEDIR/TopicPrefix.integrate.php',
	),
	
	
	
);

$mod_name = 'Topics Prefix';

// ---------------------------------------------------------------------------------------------------------------------
define('ELK_INTEGRATION_SETTINGS', serialize(array(
	'integrate_menu_buttons' => 'install_menu_button',)));

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('ELK'))
{
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as ElkArte\'s index.php.');
}

if (ELK === 'SSI')
{
	// Let's start the main job
	install_addon();

	// and then let's throw out the template! :P
	obExit(null, null, true);
}
else
{
	setup_hooks();
}

/**
 * Install function when running via SSI
 */
function install_addon()
{
	global $context, $mod_name;

	$context['mod_name'] = $mod_name;
	$context['sub_template'] = 'install_script';
	$context['page_title_html_safe'] = 'Install script for "' . $mod_name . '" Addon';

	if (isset($_GET['action']))
	{
		$context['uninstalling'] = $_GET['action'] == 'uninstall' ? true : false;
	}

	$context['html_headers'] .= '
	<style type="text/css">
    .buttonlist ul {
		margin: 0 auto;
		display: table;
	}
	</style>';

	// Sorry, only logged in admins...
	isAllowedTo('admin_forum');

	if (isset($context['uninstalling']))
	{
		setup_hooks();
	}
}

/**
 * Add or Remove the prefix hooks
 */
function setup_hooks()
{
	global $context, $hooks;

	// Add or remove the hooks
	$integration_function = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';
	foreach ($hooks as $hook)
	{
		$integration_function($hook[0], $hook[1], $hook[2]);
	}

	// Install the prefix tables
	if (empty($context['uninstalling']))
	{
		updateSettings(array('prefix_style' => '<span class="topicprefix {prefix_class}">{prefix_link}</span>&nbsp;'));

		$db_table = db_table();

		$db_table->db_create_table(
			'{db_prefix}topic_prefix',
			array(
				array(
					'name' => 'id_prefix',
					'type' => 'SMALLINT',
					'unsigned' => true,
					'default' => 0
				),
				array(
					'name' => 'id_topic',
					'type' => 'mediumint',
					'unsigned' => true,
					'default' => 0
				),
			),
			array(
				array(
					'name' => 'id_topic_prefix',
					'type' => 'unique',
					'columns' => array('id_prefix', 'id_topic'),
				),
			)
		);

		$db_table->db_create_table(
			'{db_prefix}topic_prefix_text',
			array(
				array(
					'name' => 'id_prefix',
					'type' => 'SMALLINT',
					'auto' => true,
				),
				array(
					'name' => 'prefix',
					'type' => 'varchar',
					'size' => 30,
					'default' => ''
				),
				array(
					'name' => 'id_boards',
					'type' => 'text'
				),
			),
			array(
				array(
					'name' => 'id_prefix',
					'type' => 'primary',
					'columns' => array('id_prefix'),
				),
			)
		);
	}

	$context['installation_done'] = true;
}

/**
 * A button, to install/uninstall this baby
 *
 * @param $buttons
 */
function install_menu_button(&$buttons)
{
	global $boardurl, $context;

	$context['sub_template'] = 'install_script';
	$context['current_action'] = 'install';

	$buttons['install'] = array(
		'title' => 'Installation script',
		'show' => allowedTo('admin_forum'),
		'href' => $boardurl . '/install.php',
		'active_button' => true,
		'sub_buttons' => array(),
	);
}

/**
 * The install/uninstall template
 */
function template_install_script()
{
	global $boardurl, $context;

	echo '
	<h3 class="category_header">
		Welcome to the installation for the addon "' . $context['mod_name'] . '"
	</h3>
	<div class="well centertext">';

	if (!isset($context['installation_done']))
	{
		echo '
		<strong>Please select the action you want to perform:</strong>
		<div class="buttonlist">
			<ul>
				<li>
					<a class="active" href="' . $boardurl . '/install.php?action=install">
						<span>Install</span>
					</a>
				</li>
				<li>
					<a class="active" href="' . $boardurl . '/install.php?action=uninstall">
						<span>Uninstall</span>
					</a>
				</li>
			</ul>
		</div>';
	}
	else
	{
		echo '<strong>Database adaptation successful!</strong>';
	}

	echo '
	</div>';
}
