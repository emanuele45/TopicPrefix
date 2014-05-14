<?php
/**
 * Topics Prefix
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

global $hooks, $mod_name;
$hooks = array(
	array(
		'integrate_messageindex_listing',
		'topicprefix_showprefix',
		'SUBSDIR/TopicPrefix.subs.php',
	),
	array(
		'integrate_action_post_after',
		'topicprefix_loadprefixes',
		'SUBSDIR/TopicPrefix.subs.php',
	),
	array(
		'integrate_create_topic',
		'topicprefix_createtopic',
		'SUBSDIR/TopicPrefix.subs.php',
	),
	array(
		'integrate_before_modify_post',
		'topicprefix_modifytopic',
		'SUBSDIR/TopicPrefix.subs.php',
	),
);
$mod_name = 'Topics Prefix';

// ---------------------------------------------------------------------------------------------------------------------
define('SMF_INTEGRATION_SETTINGS', serialize(array(
	'integrate_menu_buttons' => 'install_menu_button',)));

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('ELK'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as ElkArte\'s index.php.');

if (ELK == 'SSI')
{
	// Let's start the main job
	install_mod();
	// and then let's throw out the template! :P
	obExit(null, null, true);
}
else
{
	setup_hooks();
}

function install_mod ()
{
	global $context, $mod_name;

	$context['mod_name'] = $mod_name;
	$context['sub_template'] = 'install_script';
	$context['page_title_html_safe'] = 'Install script of the mod: ' . $mod_name;
	if (isset($_GET['action']))
		$context['uninstalling'] = $_GET['action'] == 'uninstall' ? true : false;
	$context['html_headers'] .= '
	<style type="text/css">
    .buttonlist ul {
      margin:0 auto;
			display:table;
		}
	</style>';

	// Sorry, only logged in admins...
	isAllowedTo('admin_forum');

	if (isset($context['uninstalling']))
		setup_hooks();
}

function setup_hooks ()
{
	global $context, $hooks, $smcFunc;

	$integration_function = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';
	foreach ($hooks as $hook)
		$integration_function($hook[0], $hook[1], $hook[2]);

	if (empty($context['uninstalling']))
	{
		updateSettings(array('prefix_style' => '<span class="topicprefix">{prefix_link}</span>&nbsp;'));

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
					'name' => 'reports',
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
						'auto' => true,
				),
				array(
						'name' => 'prefix',
						'type' => 'varchar',
						'size' => 30,
						'default' => ''
				),
			),
			array(
				array(
					'name' => 'reports',
					'type' => 'primary',
					'columns' => array('id_prefix'),
				),
			)
		);
	}


	$context['installation_done'] = true;
}

function install_menu_button (&$buttons)
{
	global $boardurl, $context;

	$context['sub_template'] = 'install_script';
	$context['current_action'] = 'install';

	$buttons['install'] = array(
		'title' => 'Installation script',
		'show' => allowedTo('admin_forum'),
		'href' => $boardurl . '/install.php',
		'active_button' => true,
		'sub_buttons' => array(
		),
	);
}

function template_install_script ()
{
	global $boardurl, $context;

	echo '
	<div class="tborder login"">
		<div class="cat_bar">
			<h3 class="catbg">
				Welcome to the install script of the mod: ' . $context['mod_name'] . '
			</h3>
		</div>
		<span class="upperframe"><span></span></span>
		<div class="roundframe centertext">';
	if (!isset($context['installation_done']))
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
	else
		echo '<strong>Database adaptation successful!</strong>';

	echo '
		</div>
		<span class="lowerframe"><span></span></span>
	</div>';
}
?>