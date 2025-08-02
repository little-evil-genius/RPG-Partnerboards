<?php
/**
 * RPG-Partnerboards  - by little.evil.genius
 * https://github.com/little-evil-genius/RPG-Partnerboards
 * https://storming-gates.de/member.php?action=profile&uid=1712
*/

// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// HOOKS
$plugins->add_hook('admin_rpgstuff_action_handler', 'partnerboards_admin_rpgstuff_action_handler');
$plugins->add_hook('admin_rpgstuff_permissions', 'partnerboards_admin_rpgstuff_permissions');
$plugins->add_hook('admin_rpgstuff_menu', 'partnerboards_admin_rpgstuff_menu');
$plugins->add_hook('admin_load', 'partnerboards_admin_manage');
$plugins->add_hook('admin_rpgstuff_update_stylesheet', 'partnerboards_admin_update_stylesheet');
$plugins->add_hook('admin_rpgstuff_update_plugin', 'partnerboards_admin_update_plugin');
$plugins->add_hook('admin_config_settings_change', 'partnerboards_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'partnerboards_settings_peek');
$plugins->add_hook('newthread_start', 'partnerboards_newthread_start');
$plugins->add_hook('datahandler_post_validate_thread', 'partnerboards_validate_newthread');
$plugins->add_hook('newthread_do_newthread_end', 'partnerboards_do_newthread');
$plugins->add_hook('editpost_end', 'partnerboards_editpost');
$plugins->add_hook('datahandler_post_validate_post', 'partnerboards_validate_editpost');
$plugins->add_hook('editpost_do_editpost_end', 'partnerboards_do_editpost');
$plugins->add_hook('class_moderation_delete_thread_start', 'partnerboards_delete_thread');
$plugins->add_hook('class_moderation_delete_post_start', 'partnerboards_delete_post');
$plugins->add_hook('editpost_deletepost', 'partnerboards_deletepost');
$plugins->add_hook('forumdisplay_thread_end', 'partnerboards_forumdisplay_thread');
$plugins->add_hook('showthread_start', 'partnerboards_showthread_start');
$plugins->add_hook('modcp_nav', 'partnerboards_modcp_nav');
$plugins->add_hook('modcp_start', 'partnerboards_modcp');
$plugins->add_hook('global_intermediate', 'partnerboards_global');
$plugins->add_hook("build_forumbits_forum", "partnerboards_forumbit");
$plugins->add_hook("misc_start", "partnerboards_misc");
 
// Die Informationen, die im Pluginmanager angezeigt werden
function partnerboards_info(){
	return array(
		"name"		=> "RPG-Partnerboards",
		"description"	=> "Durch dieses Plugin können Felder im ACP erstellt werden, welche bei einer Partnerschaftsanfrage ausgefüllt werden müssen. Im ModCP gibt es eine Übersicht vom Partnerbereich.",
		"website"	=> "https://github.com/little-evil-genius/RPG-Partnerboards",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0.1",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function partnerboards_install(){
    
    global $db, $lang;

    // SPRACHDATEI
    $lang->load("partnerboards");

    // RPG Stuff Modul muss vorhanden sein
    if (!file_exists(MYBB_ADMIN_DIR."/modules/rpgstuff/module_meta.php")) {
		flash_message($lang->partnerboards_error_rpgstuff, 'error');
		admin_redirect('index.php?module=config-plugins');
	}

    // DATENBANKTABELLE
    partnerboards_database();

    // VERZEICHNIS ERSTELLEN
    partnerboards_directories();

    // EINSTELLUNGEN HINZUFÜGEN
    $maxdisporder = $db->fetch_field($db->query("SELECT MAX(disporder) FROM ".TABLE_PREFIX."settinggroups"), "MAX(disporder)");
    $setting_group = array(
        'name'          => 'partnerboards',
        'title'         => 'RPG-Partnerboards',
        'description'   => 'Einstellungen für das verwalten der RPG-Partnerboards',
        'disporder'     => $maxdisporder+1,
        'isdefault'     => 0
    );
    $db->insert_query("settinggroups", $setting_group);
    partnerboards_settings();
    rebuild_settings();

    // TEMPLATES ERSTELLEN
	// Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "partnerboards",
        "title" => $db->escape_string("RPG-Partnerboards"),
    );
    $db->insert_query("templategroups", $templategroup);
    // Templates 
    partnerboards_templates();
    
    // STYLESHEET HINZUFÜGEN
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    // Funktion
    $css = partnerboards_stylesheet();
    $sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "partnerboards.css"), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	} 
}

// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function partnerboards_is_installed(){

    global $db, $mybb;

    if ($db->table_exists("partnerboards_fields")) {
        return true;
    }
    return false;
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function partnerboards_uninstall(){
    
	global $db, $cache;

    //DATENBANKEN LÖSCHEN
    if($db->table_exists("partnerboards"))
    {
        $db->drop_table("partnerboards");
    }
    if($db->table_exists("partnerboards_fields"))
    {
        $db->drop_table("partnerboards_fields");
    }

    // TEMPLATGRUPPE LÖSCHEN
    $db->delete_query("templategroups", "prefix = 'partnerboards'");

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'partnerboards%'");
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'partnerboards%'");
    $db->delete_query('settinggroups', "name = 'partnerboards'");
    rebuild_settings();

    // STYLESHEET ENTFERNEN
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	$db->delete_query("themestylesheets", "name = 'partnerboards.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function partnerboards_activate(){

    require MYBB_ROOT."/inc/adminfunctions_templates.php";

    // VARIABLEN EINFÜGEN
    find_replace_templatesets('newthread', '#'.preg_quote('{$posticons}').'#', '{$newthread_partnerboards} {$posticons}');
    find_replace_templatesets('editpost', '#'.preg_quote('{$posticons}').'#', '{$edit_partnerboards} {$posticons}');
    find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('{$thread[\'multipage\']}').'#', '{$thread[\'multipage\']} {$partnerboards_forumdisplay}');
	find_replace_templatesets('showthread', '#'.preg_quote('<tr><td id="posts_container">').'#', '{$partnerboards_showthread} <tr><td id="posts_container">');
    find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch} {$nav_partnerboards}');
    find_replace_templatesets('index_boardstats', '#'.preg_quote('{$forumstats}').'#', '{$forumstats} {$partnerboards_index}');
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function partnerboards_deactivate(){

    require MYBB_ROOT."/inc/adminfunctions_templates.php";

    // VARIABLEN ENTFERNEN
	find_replace_templatesets("newthread", "#".preg_quote('{$newthread_partnerboards}')."#i", '', 0);
	find_replace_templatesets("editpost", "#".preg_quote('{$edit_partnerboards}')."#i", '', 0);
	find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$partnerboards_forumdisplay}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$partnerboards_showthread}')."#i", '', 0);
	find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_partnerboards}')."#i", '', 0);
	find_replace_templatesets("index_boardstats", "#".preg_quote('{$partnerboards_index}')."#i", '', 0);
}

#####################################
### THE BIG MAGIC - THE FUNCTIONS ###
#####################################

// ADMIN BEREICH - KONFIGURATION //

// action handler fürs acp konfigurieren
function partnerboards_admin_rpgstuff_action_handler(&$actions) {
	$actions['partnerboards'] = array('active' => 'partnerboards', 'file' => 'partnerboards');
}

// Benutzergruppen-Berechtigungen im ACP
function partnerboards_admin_rpgstuff_permissions(&$admin_permissions) {
	global $lang;
	
    $lang->load('partnerboards');

	$admin_permissions['partnerboards'] = $lang->partnerboards_permission;

	return $admin_permissions;
}

// im Menü einfügen
function partnerboards_admin_rpgstuff_menu(&$sub_menu) {
    
	global $lang;
	
    $lang->load('partnerboards');

	$sub_menu[] = [
		"id" => "partnerboards",
		"title" => $lang->partnerboards_nav,
		"link" => "index.php?module=rpgstuff-partnerboards"
	];
}

// die Seiten
function partnerboards_admin_manage() {

	global $mybb, $db, $lang, $page, $run_module, $action_file, $cache;

    if ($page->active_action != 'partnerboards') {
		return false;
	}

	$lang->load('partnerboards');

	if ($run_module == 'rpgstuff' && $action_file == 'partnerboards') {

        $select_list = array(
            "text" => $lang->partnerboards_type_text,
            "textarea" => $lang->partnerboards_type_textarea,
            "select" => $lang->partnerboards_type_select,
            "multiselect" => $lang->partnerboards_type_multiselect,
            "radio" => $lang->partnerboards_type_radio,
            "checkbox" => $lang->partnerboards_type_checkbox,
            "date" => $lang->partnerboards_type_date,
            "url" => $lang->partnerboards_type_url,
            "upload" => $lang->partnerboards_type_upload
        );

		// Add to page navigation
		$page->add_breadcrumb_item($lang->partnerboards_breadcrumb_main, "index.php?module=rpgstuff-partnerboards");

		// ÜBERSICHT
        if ($mybb->get_input('action') == "" || !$mybb->get_input('action')) {

			if ($mybb->request_method == "post" && $mybb->get_input('do') == "save_sort") {

                if(!is_array($mybb->get_input('disporder', MyBB::INPUT_ARRAY))) {
                    flash_message($lang->partnerboards_error_sort, 'error');
                    admin_redirect("index.php?module=rpgstuff-partnerboards");
                }

                foreach($mybb->get_input('disporder', MyBB::INPUT_ARRAY) as $field_id => $order) {
        
                    $update_sort = array(
                        "disporder" => (int)$order    
                    );

                    $db->update_query("partnerboards_fields", $update_sort, "pbfid = '".(int)$field_id."'");
                }

                flash_message($lang->partnerboards_overview_sort_flash, 'success');
                admin_redirect("index.php?module=rpgstuff-partnerboards");
            }

			$page->output_header($lang->partnerboards_overview_header);

			// Tabs bilden
            // Übersichtsseite Button
			$sub_tabs['overview'] = [
				"title" => $lang->partnerboards_tabs_overview,
				"link" => "index.php?module=rpgstuff-partnerboards",
				"description" => $lang->partnerboards_tabs_overview_desc
			];
            // Neue Ankündigung
            $sub_tabs['add'] = [
				"title" => $lang->partnerboards_tabs_add,
				"link" => "index.php?module=rpgstuff-partnerboards&amp;action=add"
			];

			$page->output_nav_tabs($sub_tabs, 'overview');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}

            // Übersichtsseite
			$form = new Form("index.php?module=rpgstuff-partnerboards", "post", "", 1);
            echo $form->generate_hidden_field("do", 'save_sort');
			$form_container = new FormContainer($lang->partnerboards_overview_container);
            $form_container->output_row_header($lang->partnerboards_overview_container_field, array('style' => 'text-align: left;'));
            $form_container->output_row_header($lang->partnerboards_overview_container_require, array('style' => 'text-align: center; width: 10%;'));
            $form_container->output_row_header($lang->partnerboards_overview_container_sort, array('style' => 'text-align: center; width: 5%;'));
            $form_container->output_row_header($lang->partnerboards_overview_container_options, array('style' => 'text-align: center; width: 10%;'));
			
            // Alle Felder
			$query_fields = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields 
            ORDER BY disporder ASC, title ASC
            ");

            while ($field = $db->fetch_array($query_fields)) {

                // Title + Beschreibung
                $form_container->output_cell('<strong><a href="index.php?module=rpgstuff-partnerboards&amp;action=edit&amp;pbfid='.$field['pbfid'].'">'.htmlspecialchars_uni($field['title']).'</a></strong> <small>'.htmlspecialchars_uni($field['identification']).'</small><br><small>'.htmlspecialchars_uni($field['description']).'</small>');
                
                // Pflichtfeld?
                if ($field['required'] == 1) {
                    $form_container->output_cell($lang->partnerboards_overview_yes, array("class" => "align_center"));
                } else {
                    $form_container->output_cell($lang->partnerboards_overview_no, array("class" => "align_center"));
                }

                // Sortierung
                $form_container->output_cell($form->generate_numeric_field("disporder[{$field['pbfid']}]", $field['disporder'], array('style' => 'width: 80%; text-align: center;', 'min' => 0)), array("class" => "align_center"));

                // Optionen
				$popup = new PopupMenu("partnerboards_".$field['pbfid'], "Optionen");	
                $popup->add_item(
                    $lang->partnerboards_overview_options_edit,
                    "index.php?module=rpgstuff-partnerboards&amp;action=edit&amp;pbfid=".$field['pbfid']
                );
                $popup->add_item(
                    $lang->partnerboards_overview_options_delete,
                    "index.php?module=rpgstuff-partnerboards&amp;action=delete&amp;pbfid=".$field['pbfid']."&amp;my_post_key={$mybb->post_code}", 
					"return AdminCP.deleteConfirmation(this, '".$lang->partnerboards_overview_options_delete_notice."')"
                );
                $form_container->output_cell($popup->fetch(), array('style' => 'text-align: center; width: 10%;'));

                $form_container->construct_row();
            }

			// keine Felder bisher
			if($db->num_rows($query_fields) == 0){
                $form_container->output_cell($lang->partnerboards_overview_none, array("colspan" => 4, 'style' => 'text-align: center;'));
                $form_container->construct_row();
			}

            $form_container->end();

            if($db->num_rows($query_fields) > 0){
                $buttons = array($form->generate_submit_button($lang->partnerboards_overview_sort_button));
                $form->output_submit_wrapper($buttons);
            }

            $form->end();
            $page->output_footer();
			exit;
        }

		// NEUES FELD
        if ($mybb->get_input('action') == "add") {

			if ($mybb->request_method == "post") {

                if(empty($mybb->get_input('identification'))){
                    $errors[] = $lang->partnerboards_error_identification;
                }
                if(empty($mybb->get_input('title'))){
                    $errors[] = $lang->partnerboards_error_title;
                }
                if(empty($mybb->get_input('description'))) {
                    $errors[] = $lang->partnerboards_error_description;
                }
                if(($mybb->get_input('fieldtype') == "select" AND $mybb->get_input('fieldtype') == "multiselect" AND $mybb->get_input('fieldtype') == "radio" AND $mybb->get_input('fieldtype') == "checkbox") AND empty($mybb->get_input('selectoptions'))) {
                    $errors[] = $lang->partnerboards_error_selectoptions;
                }
                if($mybb->get_input('fieldtype') == "upload") {
                    if(empty($mybb->get_input('upload_extensions'))){
                        $errors[] = $lang->partnerboards_error_upload_extensions;
                    }
                    if(empty($mybb->get_input('upload_graphicdims'))){
                        $errors[] = $lang->partnerboards_error_upload_graphicdims;
                    }
                    if ($mybb->get_input('upload_bytesize') === null || $mybb->get_input('upload_bytesize') === '') {
                        $errors[] = $lang->partnerboards_error_upload_bytesize;
                    }
                }

                if(empty($errors)) {

                    if (!empty($mybb->get_input('selectoptions'))) {
                        $options = preg_replace("#(\r\n|\r|\n)#s", "\n", trim($mybb->get_input('selectoptions')));
                        if($mybb->get_input('fieldtype') != "text" AND $mybb->get_input('fieldtype') != "textarea"){
                            $selectoptions = $options;
                        } else {
                            $selectoptions = "";
                        }
                    } else {
                        $selectoptions = "";
                    }

                    if ($mybb->get_input('editableby') == 'all') {
                        $editableby_value = -1;
                    } elseif ($mybb->get_input('editableby') == 'custom') {
                        if (isset($mybb->input['select']['editableby']) && is_array($mybb->input['select']['editableby'])) {
                            foreach ($mybb->input['select']['editableby'] as &$val) {
                                $val = (int)$val;
                            }
                            unset($val);
                            
                            $editableby_value = implode(',', $mybb->input['select']['editableby']);
                        } else {
                            $editableby_value = '';
                        }
                    } else {
                        $editableby_value = '';
                    }

                    $insert_partnerboardsfield = array(
                        "identification" => $db->escape_string($mybb->get_input('identification')),
                        "title" => $db->escape_string($mybb->get_input('title')),
                        "description" => $db->escape_string($mybb->get_input('description')),
                        "type" => $db->escape_string($mybb->get_input('fieldtype')),
                        "options" => $selectoptions,
                        "upload_extensions" => $mybb->get_input('upload_extensions'),
                        "upload_graphicdims" => $mybb->get_input('upload_graphicdims'),
                        "upload_bytesize" => (int)$mybb->get_input('upload_bytesize'),
                        "required" => (int)$mybb->get_input('required'),
                        "disporder" => (int)$mybb->get_input('disporder'),
                        "editableby" => $db->escape_string($editableby_value),
                        "allowhtml" => (int)$mybb->get_input('allowhtml'),
                        "allowmycode" => (int)$mybb->get_input('allowmycode')
                    );
                    $ifid = $db->insert_query("partnerboards_fields", $insert_partnerboardsfield);

                    if ($mybb->get_input('type') == "date") {
                        $fieldtype = "DATE";
                    } else  {
                        $fieldtype = "TEXT";
                    }
        
                    $db->write_query("ALTER TABLE ".TABLE_PREFIX."partnerboards ADD {$db->escape_string($mybb->get_input('identification'))} {$fieldtype}");
        
                    // Log admin action
                    log_admin_action($ifid, $mybb->input['title']);
        
                    flash_message($lang->partnerboards_add_flash, 'success');
                    admin_redirect("index.php?module=rpgstuff-partnerboards");
                }
            }

            $page->add_breadcrumb_item($lang->partnerboards_breadcrumb_add);
			$page->output_header($lang->partnerboards_add_header);

			// Tabs bilden
            // Übersichtsseite Button
			$sub_tabs['overview'] = [
				"title" => $lang->partnerboards_tabs_overview,
				"link" => "index.php?module=rpgstuff-partnerboards"
			];
            // Neues Feld
            $sub_tabs['add'] = [
				"title" => $lang->partnerboards_tabs_add,
				"link" => "index.php?module=rpgstuff-partnerboards&amp;action=add",
				"description" => $lang->partnerboards_tabs_add_desc
			];

			$page->output_nav_tabs($sub_tabs, 'add');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}
    
            // Build the form
            $form = new Form("index.php?module=rpgstuff-partnerboards&amp;action=add", "post", "", 1);

            $form_container = new FormContainer($lang->partnerboards_add_container);
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
    
            // Identifikator
            $form_container->output_row(
				$lang->partnerboards_container_identification,
				$lang->partnerboards_container_identification_desc,
				$form->generate_text_box('identification', $mybb->get_input('identification'), array('id' => 'identification')), 'identification'
			);
    
            // Titel
            $form_container->output_row(
				$lang->partnerboards_container_title,
                '',
				$form->generate_text_box('title', $mybb->get_input('title'), array('id' => 'title')), 'title'
			);

            // Kurzbeschreibung
            $form_container->output_row(
				$lang->partnerboards_container_description,
                '',
				$form->generate_text_box('description', $mybb->get_input('description'), array('id' => 'description')), 'description'
			);

            // Feldtyp
            $form_container->output_row(
				$lang->partnerboards_container_type, 
				$lang->partnerboards_container_type_desc,
                $form->generate_select_box('fieldtype', $select_list, $mybb->get_input('fieldtype'), array('id' => 'fieldtype')), 'fieldtype'
            );    
    
            // Auswahlmöglichkeiten
            $form_container->output_row(
				$lang->partnerboards_container_selectoptions, 
				$lang->partnerboards_container_selectoptions_desc,
                $form->generate_text_area('selectoptions', $mybb->get_input('selectoptions')), 
                'selectoptions',
                array('id' => 'row_selectoptions')
			);

            // UPLOAD FUNKTION
            // Dateitypen
            $form_container->output_row(
                $lang->partnerboards_container_graphicextensions,
                $lang->partnerboards_container_graphicextensions_desc,
                $form->generate_text_box('upload_extensions', $mybb->get_input('upload_extensions')), 
                'upload_extensions',
                array('id' => 'row_upload_extensions')
            );

            // Grafikgröße
            $form_container->output_row(
                $lang->partnerboards_container_graphicdims,
                $lang->partnerboards_container_graphicdims_desc,
                $form->generate_text_box('upload_graphicdims', $mybb->get_input('upload_graphicdims')), 
                'upload_graphicdims',
                array('id' => 'row_upload_graphicdims')
            );

            // Dateigröße
            if ($mybb->get_input('upload_bytesize') === null || $mybb->get_input('upload_bytesize') === '') {
                $upload_bytesize = 2048;
            } else {
                $upload_bytesize = $mybb->get_input('upload_bytesize');
            }
            $form_container->output_row(
                $lang->partnerboards_container_graphicbytesize,
                $lang->partnerboards_container_graphicbytesize_desc,
                $form->generate_numeric_field('upload_bytesize', $upload_bytesize, array('id' => 'upload_bytesize', 'min' => 0)), 
                'upload_bytesize',
                array('id' => 'row_upload_bytesize')
            );

            // Sortierung
            $form_container->output_row(
				$lang->partnerboards_container_disporder, 
				$lang->partnerboards_container_disporder_desc,
                $form->generate_numeric_field('disporder', $mybb->get_input('disporder'), array('id' => 'disporder', 'min' => 0)), 'disporder'
			);

            // Pflichtfeld?
            $form_container->output_row(
                $lang->partnerboards_container_require, 
                $lang->partnerboards_container_require_desc, 
                $form->generate_yes_no_radio('required', $mybb->get_input('required'))
            );

            // Sichtbar für?
            $selected_values = [];
            if($mybb->get_input('editableby') == 'custom') {
                if(isset($mybb->input['select']['editableby']) && is_array($mybb->input['select']['editableby'])) {
                    $selected_values = $mybb->input['select']['editableby'];
                    foreach($selected_values as &$value) {
                        $value = (int)$value;
                    }
                    unset($value);
                }
            }

            $group_checked = array('all' => '', 'custom' => '', 'none' => '');
            if($mybb->get_input('editableby') == 'all') {
                $group_checked['all'] = 'checked="checked"';
            } elseif($mybb->get_input('editableby') == 'custom') {
                $group_checked['custom'] = 'checked="checked"';
            } else {
                $group_checked['none'] = 'checked="checked"';
            }

            print_selection_javascript();
            $select_code = "
            <dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
            <dt><label style=\"display: block;\"><input type=\"radio\" name=\"editableby\" value=\"all\" {$group_checked['all']} class=\"editableby_forums_groups_check\" onclick=\"checkAction('editableby');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_groups}</strong></label></dt>
            <dt><label style=\"display: block;\"><input type=\"radio\" name=\"editableby\" value=\"custom\" {$group_checked['custom']} class=\"editableby_forums_groups_check\" onclick=\"checkAction('editableby');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_groups}</strong></label></dt>
            <dd style=\"margin-top: 4px;\" id=\"editableby_forums_groups_custom\" class=\"editableby_forums_groups\">
            <table cellpadding=\"4\">
            <tr>
            <td valign=\"top\"><small>{$lang->groups_colon}</small></td>
            <td>".$form->generate_group_select('select[editableby][]', $selected_values, array('id' => 'editableby', 'multiple' => true, 'size' => 5))."</td>
            </tr>
            </table>
            </dd>
            <dt><label style=\"display: block;\"><input type=\"radio\" name=\"editableby\" value=\"none\" {$group_checked['none']} class=\"editableby_forums_groups_check\" onclick=\"checkAction('editableby');\" style=\"vertical-align: middle;\" /> <strong>{$lang->none}</strong></label></dt>
            </dl>
            <script type=\"text/javascript\">
            checkAction('editableby');
            </script>";
            $form_container->output_row($lang->partnerboards_container_editableby, $lang->partnerboards_container_editableby_desc, $select_code, '', array(), array('id' => 'row_editableby'));

            // Parser Optionen
            $parser_options = array(
                $form->generate_check_box('allowhtml', 1, $lang->partnerboards_container_parse_allowhtml, array('checked' => $mybb->get_input('allowhtml'), 'id' => 'allowhtml')),
                $form->generate_check_box('allowmycode', 1, $lang->partnerboards_container_parse_allowmycode, array('checked' => $mybb->get_input('allowmycode'), 'id' => 'allowmycode'))
            );
            $form_container->output_row($lang->partnerboards_container_parseroptions, '', implode('<br />', $parser_options), '', array(), array('id' => 'row_parser_options'));           
    
            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->partnerboards_add_button);
            $form->output_submit_wrapper($buttons);

            $form->end();

            echo '<script type="text/javascript" src="./jscripts/peeker.js?ver=1821"></script>
            <script type="text/javascript">
                $(function() {
                        new Peeker($("#fieldtype"), $("#row_parser_options"), /text|textarea/, false);
                        new Peeker($("#fieldtype"), $("#row_selectoptions"), /select|multiselect|radio|checkbox/, false);
                        new Peeker($("#fieldtype"), $("#row_upload_extensions, #row_upload_graphicdims, #row_upload_bytesize"), /upload/, false);
                        // Add a star to the extra row since the "extra" is required if the box is shown
                        add_star("row_selectoptions");
                });
            </script>';

            $page->output_footer();
            exit;
        }

		// FELD BEARBEITEN
		if ($mybb->get_input('action') == "edit") {

            // Get the data
            $pbfid = $mybb->get_input('pbfid', MyBB::INPUT_INT);
            $partnerboardsfield_query = $db->simple_select("partnerboards_fields", "*", "pbfid = '".$pbfid."'");
            $field = $db->fetch_array($partnerboardsfield_query);

            if ($mybb->request_method == "post") {

                if(empty($mybb->get_input('title'))){
                    $errors[] = $lang->partnerboards_error_title;
                }
                if(empty($mybb->get_input('description'))) {
                    $errors[] = $lang->partnerboards_error_description;
                }
                if(($mybb->get_input('fieldtype') == "select" AND $mybb->get_input('fieldtype') == "multiselect" AND $mybb->get_input('fieldtype') == "radio" AND $mybb->get_input('fieldtype') == "checkbox") AND empty($mybb->get_input('selectoptions'))) {
                    $errors[] = $lang->partnerboards_error_selectoptions;
                }
                if($mybb->get_input('fieldtype') == "upload") {
                    if(empty($mybb->get_input('upload_extensions'))){
                        $errors[] = $lang->partnerboards_error_upload_extensions;
                    }
                    if(empty($mybb->get_input('upload_graphicdims'))){
                        $errors[] = $lang->partnerboards_error_upload_graphicdims;
                    }
                    if($mybb->get_input('upload_bytesize') === null || $mybb->get_input('upload_bytesize') === ''){
                        $errors[] = $lang->partnerboards_error_upload_bytesize;
                    }
                }

                if(empty($errors)) {

                    if (!empty($mybb->get_input('selectoptions'))) {
                        $options = preg_replace("#(\r\n|\r|\n)#s", "\n", trim($mybb->get_input('selectoptions')));
                        if($mybb->get_input('fieldtype') != "text" AND $mybb->get_input('fieldtype') != "textarea"){
                            $selectoptions = $options;
                        } else {
                            $selectoptions = "";
                        }
                    } else {
                        $selectoptions = "";
                    }

                    if ($mybb->get_input('editableby') == 'all') {
                        $editableby_value = -1;
                    } elseif ($mybb->get_input('editableby') == 'custom') {
                        if (isset($mybb->input['select']['editableby']) && is_array($mybb->input['select']['editableby'])) {
                            foreach ($mybb->input['select']['editableby'] as &$val) {
                                $val = (int)$val;
                            }
                            unset($val);
                            
                            $editableby_value = implode(',', $mybb->input['select']['editableby']);
                        } else {
                            $editableby_value = '';
                        }
                    } else {
                        $editableby_value = '';
                    }

                    $update_partnerboardsfield = array(
                        "title" => $db->escape_string($mybb->get_input('title')),
                        "description" => $db->escape_string($mybb->get_input('description')),
                        "type" => $db->escape_string($mybb->get_input('fieldtype')),
                        "options" => $selectoptions,
                        "upload_extensions" => $mybb->get_input('upload_extensions'),
                        "upload_graphicdims" => $mybb->get_input('upload_graphicdims'),
                        "upload_bytesize" => (int)$mybb->get_input('upload_bytesize'),
                        "required" => (int)$mybb->get_input('required'),
                        "disporder" => (int)$mybb->get_input('disporder'),
                        "editableby" => $db->escape_string($editableby_value),
                        "allowhtml" => (int)$mybb->get_input('allowhtml'),
                        "allowmycode" => (int)$mybb->get_input('allowmycode')
                    );
                    $db->update_query("partnerboards_fields", $update_partnerboardsfield, "pbfid='".$mybb->get_input('pbfid')."'");

                    // Log admin action
                    log_admin_action($mybb->get_input('pbfid'), $mybb->get_input('title'));
        
                    flash_message($lang->partnerboards_edit_flash, 'success');
                    admin_redirect("index.php?module=rpgstuff-partnerboards");
                }
            }

            $page->add_breadcrumb_item($lang->partnerboards_breadcrumb_edit);
            $page->output_header($lang->partnerboards_edit_header);

			// Tabs bilden
            // Feld bearbeiten
            $sub_tabs['edit'] = [
				"title" => $lang->partnerboards_tabs_edit,
				"link" => "index.php?module=rpgstuff-partnerboards&amp;action=edit&amp;pbfid=".$pbfid,
				"description" => $lang->partnerboards_tabs_edit_desc
			];
			$page->output_nav_tabs($sub_tabs, 'edit');

            // Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
				$title = $mybb->get_input('title');
				$description = $mybb->get_input('description');
				$fieldtype = $mybb->get_input('fieldtype');
				$selectoptions = $mybb->get_input('selectoptions');
				$required = $mybb->get_input('required');
				$disporder = $mybb->get_input('disporder');
				$allow_html = $mybb->get_input('allowhtml');
				$allow_mybb = $mybb->get_input('allowmycode');
                $editableby = $mybb->get_input('editableby');
                $upload_extensions = $mybb->get_input('upload_extensions');
                $upload_graphicdims = $mybb->get_input('upload_graphicdims');
                $upload_bytesize = $mybb->get_input('upload_bytesize');

                if ($mybb->get_input('editableby') == 'custom') {
                    if(isset($mybb->input['select']['editableby']) && is_array($mybb->input['select']['editableby'])) {
                        $selected_values = $mybb->input['select']['editableby'];
                        foreach($selected_values as &$value) {
                            $value = (int)$value;
                        }
                        unset($value);
                    }
                } else {
                    $selected_values = [];
                }
			} else {
				$title = $field['title'];
				$description = $field['description'];
				$fieldtype = $field['type'];
				$selectoptions = $field['options'];
				$required = $field['required'];
				$disporder = $field['disporder'];
				$allow_html = $field['allowhtml'];
				$allow_mybb = $field['allowmycode'];
                $upload_extensions = $field['upload_extensions'];
                $upload_graphicdims = $field['upload_graphicdims'];
                $upload_bytesize = $field['upload_bytesize'];

                if ($field['editableby'] == -1) {
                    $editableby = 'all';
                    $selected_values = [];
                } elseif (!empty($field['editableby'])) {
                    $editableby = 'custom';
                    $selected_values = explode(',', $field['editableby']);
                } else {
                    $editableby = 'none';
                    $selected_values = [];
                }
            }

            // Build the form
            $form = new Form("index.php?module=rpgstuff-partnerboards&amp;action=edit", "post", "", 1);

            $form_container = new FormContainer($lang->sprintf($lang->partnerboards_edit_container, $field['title']));
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
            echo $form->generate_hidden_field("pbfid", $pbfid);
    
            // Titel
            $form_container->output_row(
				$lang->partnerboards_container_title,
                '',
				$form->generate_text_box('title', $title, array('id' => 'title')), 'title'
			);

            // Kurzbeschreibung
            $form_container->output_row(
				$lang->partnerboards_container_description,
                '',
				$form->generate_text_box('description', $description, array('id' => 'description')), 'description'
			);

            // Feldtyp
            $form_container->output_row(
				$lang->partnerboards_container_type, 
				$lang->partnerboards_container_type_desc,
                $form->generate_select_box('fieldtype', $select_list, $fieldtype, array('id' => 'fieldtype')), 'fieldtype'
            );    
    
            // Auswahlmöglichkeiten
            $form_container->output_row(
				$lang->partnerboards_container_selectoptions, 
				$lang->partnerboards_container_selectoptions_desc,
                $form->generate_text_area('selectoptions', $selectoptions), 
                'selectoptions',
                array('id' => 'row_selectoptions')
			);

            // UPLOAD FUNKTION
            // Dateitypen
            $form_container->output_row(
                $lang->partnerboards_container_graphicextensions,
                $lang->partnerboards_container_graphicextensions_desc,
                $form->generate_text_box('upload_extensions', $upload_extensions), 
                'upload_extensions',
                array('id' => 'row_upload_extensions')
            );

            // Grafikgröße
            $form_container->output_row(
                $lang->partnerboards_container_graphicdims,
                $lang->partnerboards_container_graphicdims_desc,
                $form->generate_text_box('upload_graphicdims', $upload_graphicdims), 
                'upload_graphicdims',
                array('id' => 'row_upload_graphicdims')
            );

            // Dateigröße
            $form_container->output_row(
                $lang->partnerboards_container_graphicbytesize,
                $lang->partnerboards_container_graphicbytesize_desc,
                $form->generate_numeric_field('upload_bytesize', $upload_bytesize, array('id' => 'upload_bytesize', 'min' => 0)), 
                'upload_bytesize',
                array('id' => 'row_upload_bytesize')
            );

            // Sortierung
            $form_container->output_row(
				$lang->partnerboards_container_disporder, 
				$lang->partnerboards_container_disporder_desc,
                $form->generate_numeric_field('disporder', $disporder, array('id' => 'disporder', 'min' => 0)), 'disporder'
			);

            // Pflichtfeld?
            $form_container->output_row(
                $lang->partnerboards_container_require, 
                $lang->partnerboards_container_require_desc, 
                $form->generate_yes_no_radio('required', $required)
            );

            // Sichtbar für?
            $group_checked = array('all' => '', 'custom' => '', 'none' => '');
            if ($editableby == 'all') {
                $group_checked['all'] = 'checked="checked"';
            } elseif ($editableby == 'custom') {
                $group_checked['custom'] = 'checked="checked"';
            } else {
                $group_checked['none'] = 'checked="checked"';
            }

            print_selection_javascript();
            $select_code = "
            <dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
            <dt><label style=\"display: block;\"><input type=\"radio\" name=\"editableby\" value=\"all\" {$group_checked['all']} class=\"editableby_forums_groups_check\" onclick=\"checkAction('editableby');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_groups}</strong></label></dt>
            <dt><label style=\"display: block;\"><input type=\"radio\" name=\"editableby\" value=\"custom\" {$group_checked['custom']} class=\"editableby_forums_groups_check\" onclick=\"checkAction('editableby');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_groups}</strong></label></dt>
            <dd style=\"margin-top: 4px;\" id=\"editableby_forums_groups_custom\" class=\"editableby_forums_groups\">
            <table cellpadding=\"4\">
            <tr>
            <td valign=\"top\"><small>{$lang->groups_colon}</small></td>
            <td>".$form->generate_group_select('select[editableby][]', $selected_values, array('id' => 'editableby', 'multiple' => true, 'size' => 5))."</td>
            </tr>
            </table>
            </dd>
            <dt><label style=\"display: block;\"><input type=\"radio\" name=\"editableby\" value=\"none\" {$group_checked['none']} class=\"editableby_forums_groups_check\" onclick=\"checkAction('editableby');\" style=\"vertical-align: middle;\" /> <strong>{$lang->none}</strong></label></dt>
            </dl>
            <script type=\"text/javascript\">
            checkAction('editableby');
            </script>";
            $form_container->output_row($lang->partnerboards_container_editableby, $lang->partnerboards_container_editableby_desc, $select_code, '', array(), array('id' => 'row_editableby'));

            // Parser Optionen
            $parser_options = array(
                $form->generate_check_box('allowhtml', 1, $lang->partnerboards_container_parse_allowhtml, array('checked' => $allow_html, 'id' => 'allowhtml')),
                $form->generate_check_box('allowmycode', 1, $lang->partnerboards_container_parse_allowmycode, array('checked' => $allow_mybb, 'id' => 'allowmycode')),
            );
            $form_container->output_row($lang->partnerboards_container_parseroptions, '', implode('<br />', $parser_options), '', array(), array('id' => 'row_parser_options'));           
    
            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->partnerboards_edit_button);
            $form->output_submit_wrapper($buttons);
            $form->end();

            echo '<script type="text/javascript" src="./jscripts/peeker.js?ver=1821"></script>
            <script type="text/javascript">
                $(function() {
                        new Peeker($("#fieldtype"), $("#row_parser_options"), /text|textarea/, false);
                        new Peeker($("#fieldtype"), $("#row_selectoptions"), /select|multiselect|radio|checkbox/, false);
                        new Peeker($("#fieldtype"), $("#row_upload_extensions, #row_upload_graphicdims, #row_upload_bytesize"), /upload/, false);
                        // Add a star to the extra row since the "extra" is required if the box is shown
                        add_star("row_selectoptions");
                });
            </script>';

            $page->output_footer();
            exit;
        }

        // FELD LÖSCHEN
		if ($mybb->get_input('action') == "delete") {
            
            // Get the data
            $pbfid = $mybb->get_input('pbfid', MyBB::INPUT_INT);

			// Error Handling
			if (empty($pbfid)) {
				flash_message($lang->partnerboards_error_invalid, 'error');
				admin_redirect("index.php?module=rpgstuff-partnerboards");
			}

			// Cancel button pressed?
			if (isset($mybb->input['no']) && $mybb->input['no']) {
				admin_redirect("index.php?module=rpgstuff-partnerboards");
			}

			if ($mybb->request_method == "post") {

                // Spalte löschen bei den Themen löschen
                $identification = $db->fetch_field($db->simple_select("partnerboards_fields", "identification", "pbfid= '".$pbfid."'"), "identification");
                if ($db->field_exists($identification, "partnerboards")) {
                    $db->drop_column("partnerboards", $identification);
                }

                // Grafik-Element löschen
                $type = $db->fetch_field($db->simple_select("partnerboards_fields", "type", "pbfid= '".$pbfid."'"), "type");
                if ($type == "upload") {
                
                    require_once MYBB_ROOT."inc/functions_upload.php";
                    require_once MYBB_ROOT."inc/functions.php";

                    $folder = MYBB_ROOT."uploads/partnerboards/";
                    if (is_dir($folder)) {
                        $files = array_diff(scandir($folder), array('.', '..'));
                        foreach ($files as $file) {
                            $filePath = $folder . $file;
                            if (is_file($filePath) && strpos($file, $identification."_") === 0) {
                                delete_uploaded_file($filePath);
                            }
                        }
                    }
                }

                // Feld in der Feld DB löschen
                $db->delete_query('partnerboards_fields', "pbfid = '".$pbfid."'");

				flash_message($lang->partnerboards_delete_flash, 'success');
				admin_redirect("index.php?module=rpgstuff-partnerboards");
			} else {
				$page->output_confirm_action(
					"index.php?module=rpgstuff-partnerboards&amp;action=delete&amp;pbfid=".$pbfid,
					$lang->teamheader_manage_character_delete_notice
				);
			}
			exit;
        }
    }
}

// Stylesheet zum Master Style hinzufügen
function partnerboards_admin_update_stylesheet(&$table) {

    global $db, $mybb, $lang;
	
    $lang->load('rpgstuff_stylesheet_updates');

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // HINZUFÜGEN
    if ($mybb->input['action'] == 'add_master' AND $mybb->get_input('plugin') == "partnerboards") {

        $css = partnerboards_stylesheet();
        
        $sid = $db->insert_query("themestylesheets", $css);
        $db->update_query("themestylesheets", array("cachefile" => "partnerboards.css"), "sid = '".$sid."'", 1);
    
        $tids = $db->simple_select("themes", "tid");
        while($theme = $db->fetch_array($tids)) {
            update_theme_stylesheet_list($theme['tid']);
        } 

        flash_message($lang->stylesheets_flash, "success");
        admin_redirect("index.php?module=rpgstuff-stylesheet_updates");
    }

    // Zelle mit dem Namen des Themes
    $table->construct_cell("<b>".htmlspecialchars_uni("RPG-Partnerboards")."</b>", array('width' => '70%'));

    // Ob im Master Style vorhanden
    $master_check = $db->fetch_field($db->query("SELECT tid FROM ".TABLE_PREFIX."themestylesheets 
    WHERE name = 'partnerboards.css' 
    AND tid = 1
    "), "tid");
    
    if (!empty($master_check)) {
        $masterstyle = true;
    } else {
        $masterstyle = false;
    }

    if (!empty($masterstyle)) {
        $table->construct_cell($lang->stylesheets_masterstyle, array('class' => 'align_center'));
    } else {
        $table->construct_cell("<a href=\"index.php?module=rpgstuff-stylesheet_updates&action=add_master&plugin=partnerboards\">".$lang->stylesheets_add."</a>", array('class' => 'align_center'));
    }
    
    $table->construct_row();
}

// Plugin Update
function partnerboards_admin_update_plugin(&$table) {

    global $db, $mybb, $lang;
	
    $lang->load('rpgstuff_plugin_updates');

    // UPDATE
    if ($mybb->input['action'] == 'add_update' AND $mybb->get_input('plugin') == "partnerboards") {

        // Einstellungen überprüfen => Type = update
        partnerboards_settings('update');
        rebuild_settings();

        // Templates 
        partnerboards_templates('update');

        // Stylesheet
        $update_data = partnerboards_stylesheet_update();
        $update_stylesheet = $update_data['stylesheet'];
        $update_string = $update_data['update_string'];
        if (!empty($update_string)) {

            // Ob im Master Style die Überprüfung vorhanden ist
            $masterstylesheet = $db->fetch_field($db->query("SELECT stylesheet FROM ".TABLE_PREFIX."themestylesheets WHERE tid = 1 AND name = 'partnerboards.css'"), "stylesheet");
            $masterstylesheet = (string)($masterstylesheet ?? '');
            $update_string = (string)($update_string ?? '');
	    $pos = strpos($masterstylesheet, $update_string);
            if ($pos === false) { // nicht vorhanden 
            
                $theme_query = $db->simple_select('themes', 'tid, name');
                while ($theme = $db->fetch_array($theme_query)) {
        
                    $stylesheet_query = $db->simple_select("themestylesheets", "*", "name='".$db->escape_string('partnerboards.css')."' AND tid = ".$theme['tid']);
                    $stylesheet = $db->fetch_array($stylesheet_query);
        
                    if ($stylesheet) {

                        require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
        
                        $sid = $stylesheet['sid'];
            
                        $updated_stylesheet = array(
                            "cachefile" => $db->escape_string($stylesheet['name']),
                            "stylesheet" => $db->escape_string($stylesheet['stylesheet']."\n\n".$update_stylesheet),
                            "lastmodified" => TIME_NOW
                        );
            
                        $db->update_query("themestylesheets", $updated_stylesheet, "sid='".$sid."'");
            
                        if(!cache_stylesheet($theme['tid'], $stylesheet['name'], $updated_stylesheet['stylesheet'])) {
                            $db->update_query("themestylesheets", array('cachefile' => "css.php?stylesheet=".$sid), "sid='".$sid."'", 1);
                        }
            
                        update_theme_stylesheet_list($theme['tid']);
                    }
                }
            } 
        }

        // Datenbanktabellen & Felder
        partnerboards_database();

        // Collation prüfen und korrigieren
        $charset = 'utf8mb4';
        $collation = 'utf8mb4_unicode_ci';

        $collation_string = $db->build_create_table_collation();
        if (preg_match('/CHARACTER SET ([^\s]+)\s+COLLATE ([^\s]+)/i', $collation_string, $matches)) {
            $charset = $matches[1];
            $collation = $matches[2];
        }

        $databaseTables = [
            "partnerboards",
            "partnerboards_fields"
        ];

        foreach ($databaseTables as $databaseTable) {
            if ($db->table_exists($databaseTable)) {
                $table = TABLE_PREFIX.$databaseTable;

                $query = $db->query("SHOW TABLE STATUS LIKE '".$db->escape_string($table)."'");
                $table_status = $db->fetch_array($query);
                $actual_collation = strtolower($table_status['Collation'] ?? '');

                $actual_collation = str_replace(['utf8mb3', 'utf8mb4'], 'utf8', $actual_collation);
                $expected_collation = str_replace(['utf8mb3', 'utf8mb4'], 'utf8', $collation);

                if (!empty($collation) && $actual_collation !== $expected_collation) {
                    $db->query("ALTER TABLE {$table} CONVERT TO CHARACTER SET {$charset} COLLATE {$collation}");
                }
            }
        }

        // VERZEICHNIS ERSTELLEN
        partnerboards_directories();

        flash_message($lang->plugins_flash, "success");
        admin_redirect("index.php?module=rpgstuff-plugin_updates");
    }

    // Zelle mit dem Namen des Themes
    $table->construct_cell("<b>".htmlspecialchars_uni("RPG-Partnerboards")."</b>", array('width' => '70%'));

    // Überprüfen, ob Update erledigt
    $update_check = partnerboards_is_updated();

    if (!empty($update_check)) {
        $table->construct_cell($lang->plugins_actual, array('class' => 'align_center'));
    } else {
        $table->construct_cell("<a href=\"index.php?module=rpgstuff-plugin_updates&action=add_update&plugin=partnerboards\">".$lang->plugins_update."</a>", array('class' => 'align_center'));
    }
    
    $table->construct_row();
}

// ADMIN BEREICH - EINSTELLUNGEN //
function partnerboards_settings_change(){
    
    global $db, $mybb, $partnerboards_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='partnerboards'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $partnerboards_settings_peeker = ($mybb->get_input('gid') == $group['gid']) && ($mybb->request_method != 'post');
}
function partnerboards_settings_peek(&$peekers){

    global $partnerboards_settings_peeker;

    if ($partnerboards_settings_peeker) {
        $peekers[] = 'new Peeker($("#setting_partnerboards_indexdisplay"), $("#row_setting_partnerboards_sisterarea"), /^1/, false)';
        $peekers[] = 'new Peeker($("#setting_partnerboards_indexdisplay"), $("#row_setting_partnerboards_indexforumbit"), /^1|^2|^3/, false)';
        $peekers[] = 'new Peeker($(".setting_partnerboards_overview"), $("#row_setting_partnerboards_overview_permissions"),/1/,true)'; 
    }
}

// NEUES THEMA ERÖFFNEN - ANZEIGE
function partnerboards_newthread_start() {

    global $templates, $mybb, $lang, $fid, $post_errors, $db, $newthread_partnerboards, $indexdisplay_radiobuttons, $parser, $code_html, $partnerboards_rules;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];
    $indexdisplay_setting = $mybb->settings['partnerboards_indexdisplay'];
    $rules_setting = $mybb->settings['partnerboards_rules'];

    // zurück, wenn es nicht der Partner Bereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($fid, $relevant_forums)) return;

    require_once MYBB_ROOT."inc/class_parser.php";
    $parser = new postParser;
    $code_html = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 1,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );

    $partnerboards_rules = $parser->parse_message($rules_setting, $code_html);

    // Sprachdatei laden
    $lang->load('partnerboards');

    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {
        $indexdisplay = $mybb->get_input('indexdisplay');
        $own_partnerboardsfields = partnerboards_generate_fields(null, true);
    } else {

        // Entwurf bearbeiten
        if ($tid > 0) {
            // Infos aus der DB ziehen
            $draft = $db->fetch_array($db->simple_select('partnerboards', '*', 'tid = '.$tid));

            $indexdisplay = $draft['indexdisplay'];
            $own_partnerboardsfields = partnerboards_generate_fields($draft);

        } else {
            $indexdisplay = 0;
            $own_partnerboardsfields = partnerboards_generate_fields();
        }
    }

    if ($indexdisplay_setting == 3 AND $mybb->usergroup['canmodcp'] == '1') {
        $indexdisplay_radiobuttons =  partnerboards_generate_radiobuttons_indexdisplay($indexdisplay);
        eval("\$indexdisplay_setting = \"".$templates->get("partnerboards_newthread_indexdisplay")."\";");
    } else {
        $indexdisplay_radiobuttons = "";
        $indexdisplay_setting = "";
    }

    eval("\$newthread_partnerboards = \"".$templates->get("partnerboards_newthread")."\";");
}

// NEUES THEMA ERÖFFNEN - ÜBERPRÜFEN, OB ALLES AUSGEFÜLLT IST
function partnerboards_validate_newthread(&$dh) {

    global $mybb, $lang, $fid, $db;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    // zurück, wenn es nicht der Partner Bereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('partnerboards');

    // Abfrage der Felder, die als erforderlich markiert sind
    $fields_query = $db->query("SELECT identification, title, type, editableby FROM ".TABLE_PREFIX."partnerboards_fields WHERE required = 1");

    while ($field = $db->fetch_array($fields_query)) {
        
        if ($field['type'] == "multiselect" || $field['type'] == "checkbox") {
            $field_value = $mybb->get_input($field['identification'], MyBB::INPUT_ARRAY);
        } else {
            $field_value = $mybb->get_input($field['identification']);
        }

        if ($field['editableby'] != '') {
            if ($field['editableby'] == -1) {
                if ($field['type'] != "upload") {
                    if (empty($field_value)) {
                        $error_message = $lang->sprintf($lang->partnerboards_validate_field, $field['title']);
                        $dh->set_error($error_message);
                    }
                } else {
                    if(!empty($_FILES[$field['identification']]['name'])) {
            
                        $upload = $db->fetch_array($db->simple_select('partnerboards_fields', 'upload_extensions, upload_graphicdims, upload_bytesize', 'identification = "'.$field['identification'].'"'));
            
                        // Grafik-Größe
                        $imgDimensions = @getimagesize($_FILES[$field['identification']]['tmp_name']);
                        if(is_array($imgDimensions)){
                            // Höhe & Breite
                            $width = $imgDimensions[0]; 
                            $height = $imgDimensions[1];
                
                            // Überprüfung der Bildgröße
                            list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($upload['upload_graphicdims']));
                            if($width > $maxwidth || $height > $maxheight) {
                                $error_message = $lang->sprintf($lang->partnerboards_validate_upload_dims, $maxwidth, $maxheight);
                                $dh->set_error($error_message);
                            }
                        }

                        // Überprüfung der Dateigröße
                        if ($upload['upload_bytesize'] > 0) {
                            $max_size = $upload['upload_bytesize']*1024; 
                            if($_FILES[$field['identification']]['size'] > $max_size) {
                                $error_message = $lang->sprintf($lang->partnerboards_validate_upload_size, get_friendly_size($max_size));
                                $dh->set_error($error_message);
                            }
                        }
                        
                        // Überprüfung der Dateiendung 
                        // Dateityp ermittel (.png, .jpg, .gif)
                        $fileParts = explode(".", $_FILES[$field['identification']]['name']);
                        $imageFileType = end($fileParts);

                        $extensions_string = str_replace(", ", ",", strtolower($upload['upload_extensions']).",".strtoupper($upload['upload_extensions']));
                        $extensions_values = explode(",", $extensions_string);   
                        if(!in_array($imageFileType, $extensions_values)) {
                            $error_message = $lang->sprintf($lang->partnerboards_validate_upload_file, $imageFileType);
                            $dh->set_error($error_message); 
                        }
                    } else {
                        $error_message = $lang->sprintf($lang->partnerboards_validate_upload, $field['title']);
                        $dh->set_error($error_message); 
                    }
                }
            } else {
                $editableby_groups = explode(",", $field['editableby']);
                foreach ($editableby_groups as $group) {
                    if (($mybb->user['usergroup'] == $group) OR (in_array($group, explode(",", $mybb->user['additionalgroups'])))) {               
                        if ($field['type'] != "upload") {
                            if (empty($field_value)) {
                                $error_message = $lang->sprintf($lang->partnerboards_validate_field, $field['title']);
                                $dh->set_error($error_message);
                            }
                        } else {
                            if(!empty($_FILES[$field['identification']]['name'])) {
                    
                                $upload = $db->fetch_array($db->simple_select('partnerboards_fields', 'upload_extensions, upload_graphicdims, upload_bytesize', 'identification = "'.$field['identification'].'"'));
                    
                                // Grafik-Größe
                                $imgDimensions = @getimagesize($_FILES[$field['identification']]['tmp_name']);
                                if(is_array($imgDimensions)){
                                    // Höhe & Breite
                                    $width = $imgDimensions[0]; 
                                    $height = $imgDimensions[1];
                        
                                    // Überprüfung der Bildgröße
                                    list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($upload['upload_graphicdims']));
                                    if($width > $maxwidth || $height > $maxheight) {
                                        $error_message = $lang->sprintf($lang->partnerboards_validate_upload_dims, $maxwidth, $maxheight);
                                        $dh->set_error($error_message);
                                    }
                                }
        
                                // Überprüfung der Dateigröße
                                if ($upload['upload_bytesize'] > 0) {
                                    $max_size = $upload['upload_bytesize']*1024; 
                                    if($_FILES[$field['identification']]['size'] > $max_size) {
                                        $error_message = $lang->sprintf($lang->partnerboards_validate_upload_size, get_friendly_size($max_size));
                                        $dh->set_error($error_message);
                                    }
                                }
                                
                                // Überprüfung der Dateiendung 
                                // Dateityp ermittel (.png, .jpg, .gif)
                                $fileParts = explode(".", $_FILES[$field['identification']]['name']);
                                $imageFileType = end($fileParts);
        
                                $extensions_string = str_replace(", ", ",", strtolower($upload['upload_extensions']).",".strtoupper($upload['upload_extensions']));
                                $extensions_values = explode(",", $extensions_string);   
                                if(!in_array($imageFileType, $extensions_values)) {
                                    $error_message = $lang->sprintf($lang->partnerboards_validate_upload_file, $imageFileType);
                                    $dh->set_error($error_message); 
                                }
                            } else {
                                $error_message = $lang->sprintf($lang->partnerboards_validate_upload, $field['title']);
                                $dh->set_error($error_message); 
                            }
                        }
                    }
                }
            }
        }
    }
}

// NEUES THEMA ERÖFFNEN - SPEICHERN
function partnerboards_do_newthread() {

    global $mybb, $db, $fid, $tid;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    // zurück, wenn es nicht der Partner Bereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Mögliche gespeicherte Entwürfe löschen
    $db->delete_query("partnerboards", "tid = '".$mybb->get_input('tid', MyBB::INPUT_INT)."'");

    // SPEICHERN
    $new_partnerboard = array(
        'tid' => (int)$tid,
        'indexdisplay' => (int)$mybb->get_input('indexdisplay'),
    );

    // Abfrage der individuellen Felder
    $fields_query = $db->query("SELECT identification, type FROM ".TABLE_PREFIX."partnerboards_fields");
    
    while ($field = $db->fetch_array($fields_query)) {
        $identification = $field['identification'];
        $type = $field['type'];
    
        if ($type == 'multiselect' || $type == 'checkbox') {
            $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
            $value = implode(",", array_map('trim', $value));
        } else if ($type == 'upload') {
            $fileParts = explode(".", $_FILES[$field['identification']]['name']);
            $imageFileType = end($fileParts);
            $filename = $identification.'_tid'.$tid.'.'.$imageFileType;
            $value = $filename.'?dateline='.time();
            $folder_path = MYBB_ROOT.'uploads/partnerboards';
            
            move_uploaded_file($_FILES[$identification]['tmp_name'], $folder_path."/".$filename);
        } else {
            $value = $mybb->get_input($identification);
        }
    
        $new_partnerboard[$identification] = $db->escape_string($value);
    }

    $db->insert_query("partnerboards", $new_partnerboard);
}

// BEARBEITEN - ANZEIGE
function partnerboards_editpost() {

    global $templates, $mybb, $lang, $forum, $db, $thread, $pid, $post_errors, $edit_partnerboards, $parser, $code_html;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];
    $indexdisplay_setting = $mybb->settings['partnerboards_indexdisplay'];
    $rules_setting = $mybb->settings['partnerboards_rules'];

    // zurück, wenn es nicht der Partner Bereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($thread['fid'], $relevant_forums)) return;

    require_once MYBB_ROOT."inc/class_parser.php";
    $parser = new postParser;
    $code_html = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 1,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );

    $partnerboards_rules = $parser->parse_message($rules_setting, $code_html);

	// Thread ID
    $tid = $thread['tid'];

    // post isnt the first post in thread
    if ($thread['firstpost'] != $pid) return;

    // Sprachdatei laden
    $lang->load('partnerboards');

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {
        $indexdisplay = $mybb->get_input('indexdisplay');
        $own_partnerboardsfields = partnerboards_generate_fields(null, true, $tid);
    } else {
        // Infos aus der DB ziehen
        $draft = $db->fetch_array($db->simple_select('partnerboards', '*', 'tid = '.$tid));

        $indexdisplay = $draft['indexdisplay'];
        $own_partnerboardsfields = partnerboards_generate_fields($draft, null, $tid);
    }

    if ($indexdisplay_setting == 3 AND $mybb->usergroup['canmodcp'] == '1') {
        $indexdisplay_radiobuttons =  partnerboards_generate_radiobuttons_indexdisplay($indexdisplay);
        eval("\$indexdisplay_setting = \"".$templates->get("partnerboards_newthread_indexdisplay")."\";");
    } else {
        $indexdisplay_radiobuttons = "";
        $indexdisplay_setting = "";
    }

    eval("\$edit_partnerboards = \"".$templates->get("partnerboards_newthread")."\";");
}

// BEARBEITEN - ÜBERPRÜFEN, OB ALLES AUSGEFÜLLT IST
function partnerboards_validate_editpost(&$dh) {

    global $mybb, $lang, $fid, $pid, $thread, $db;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    // zurück, wenn es nicht der Partner Bereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($fid, $relevant_forums)) return;

    // post isnt the first post in thread
    if ($thread['firstpost'] != $pid) return;

    // Sprachdatei laden
    $lang->load('partnerboards');

    // Abfrage der Felder, die als erforderlich markiert sind
    $fields_query = $db->query("SELECT identification, title, type, editableby FROM ".TABLE_PREFIX."partnerboards_fields WHERE required = 1");

    while ($field = $db->fetch_array($fields_query)) {
        
        if ($field['type'] == "multiselect" || $field['type'] == "checkbox") {
            $field_value = $mybb->get_input($field['identification'], MyBB::INPUT_ARRAY);
        } else {
            $field_value = $mybb->get_input($field['identification']);
        }

        if ($field['editableby'] != '') {
            if ($field['editableby'] == -1) {
                if ($field['type'] != "upload") {
                    if (empty($field_value)) {
                        $error_message = $lang->sprintf($lang->partnerboards_validate_field, $field['title']);
                        $dh->set_error($error_message);
                    }
                } else {
                    if (!empty($mybb->get_input('delete_'.$field['identification']))) {
                        $error_message = $error_message = $lang->sprintf($lang->partnerboards_validate_upload_delete, $field['title']);
                        $dh->set_error($error_message); 
                    } else {
                        if(!empty($_FILES[$field['identification']]['name'])) {
                
                            $upload = $db->fetch_array($db->simple_select('partnerboards_fields', 'upload_extensions, upload_graphicdims, upload_bytesize', 'identification = "'.$field['identification'].'"'));
                
                            // Grafik-Größe
                            $imgDimensions = @getimagesize($_FILES[$field['identification']]['tmp_name']);
                            if(is_array($imgDimensions)){
                                // Höhe & Breite
                                $width = $imgDimensions[0]; 
                                $height = $imgDimensions[1];
                    
                                // Überprüfung der Bildgröße
                                list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($upload['upload_graphicdims']));
                                if($width > $maxwidth || $height > $maxheight) {
                                    $error_message = $lang->sprintf($lang->partnerboards_validate_upload_dims, $field['title'], $maxwidth, $maxheight);
                                    $dh->set_error($error_message);
                                }
                            }
    
                            // Überprüfung der Dateigröße
                            if ($upload['upload_bytesize'] > 0) {
                                $max_size = $upload['upload_bytesize']*1024; 
                                if($_FILES[$field['identification']]['size'] > $max_size) {
                                    $error_message = $lang->sprintf($lang->partnerboards_validate_upload_size, $field['title'], get_friendly_size($max_size));
                                    $dh->set_error($error_message);
                                }
                            }
                            
                            // Überprüfung der Dateiendung 
                            // Dateityp ermittel (.png, .jpg, .gif)
                            $fileParts = explode(".", $_FILES[$field['identification']]['name']);
                            $imageFileType = end($fileParts);
    
                            $extensions_string = str_replace(", ", ",", strtolower($upload['upload_extensions']).",".strtoupper($upload['upload_extensions']));
                            $extensions_values = explode(",", $extensions_string);   
                            if(!in_array($imageFileType, $extensions_values)) {
                                $error_message = $lang->sprintf($lang->partnerboards_validate_upload_file, $field['title'], $imageFileType);
                                $dh->set_error($error_message); 
                            }
                        } else {
                            $checkvalue = $db->fetch_field($db->simple_select('partnerboards', $field['identification'], 'tid = '.$thread['tid']), $field['identification']);
                            if (empty($checkvalue)) {
                                $error_message = $lang->sprintf($lang->partnerboards_validate_upload, $field['title']);
                                $dh->set_error($error_message); 
                            }
                        }
                    }
                }
            } else {
                $editableby_groups = explode(",", $field['editableby']);
                foreach ($editableby_groups as $group) {
                    if (($mybb->user['usergroup'] == $group) OR (in_array($group, explode(",", $mybb->user['additionalgroups'])))) {               
                        if ($field['type'] != "upload") {
                            if (empty($field_value)) {
                                $error_message = $lang->sprintf($lang->partnerboards_validate_field, $field['title']);
                                $dh->set_error($error_message);
                            }
                        } else {
                            if (!empty($mybb->get_input('delete_'.$field['identification']))) {
                                $error_message = $error_message = $lang->sprintf($lang->partnerboards_validate_upload_delete, $field['title']);
                                $dh->set_error($error_message); 
                            } else {
                                if(!empty($_FILES[$field['identification']]['name'])) {
                        
                                    $upload = $db->fetch_array($db->simple_select('partnerboards_fields', 'upload_extensions, upload_graphicdims, upload_bytesize', 'identification = "'.$field['identification'].'"'));
                        
                                    // Grafik-Größe
                                    $imgDimensions = @getimagesize($_FILES[$field['identification']]['tmp_name']);
                                    if(is_array($imgDimensions)){
                                        // Höhe & Breite
                                        $width = $imgDimensions[0]; 
                                        $height = $imgDimensions[1];
                            
                                        // Überprüfung der Bildgröße
                                        list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($upload['upload_graphicdims']));
                                        if($width > $maxwidth || $height > $maxheight) {
                                            $error_message = $lang->sprintf($lang->partnerboards_validate_upload_dims, $field['title'], $maxwidth, $maxheight);
                                            $dh->set_error($error_message);
                                        }
                                    }
            
                                    // Überprüfung der Dateigröße
                                    if ($upload['upload_bytesize'] > 0) {
                                        $max_size = $upload['upload_bytesize']*1024; 
                                        if($_FILES[$field['identification']]['size'] > $max_size) {
                                            $error_message = $lang->sprintf($lang->partnerboards_validate_upload_size, $field['title'], get_friendly_size($max_size));
                                            $dh->set_error($error_message);
                                        }
                                    }
                                    
                                    // Überprüfung der Dateiendung 
                                    // Dateityp ermittel (.png, .jpg, .gif)
                                    $fileParts = explode(".", $_FILES[$field['identification']]['name']);
                                    $imageFileType = end($fileParts);
            
                                    $extensions_string = str_replace(", ", ",", strtolower($upload['upload_extensions']).",".strtoupper($upload['upload_extensions']));
                                    $extensions_values = explode(",", $extensions_string);   
                                    if(!in_array($imageFileType, $extensions_values)) {
                                        $error_message = $lang->sprintf($lang->partnerboards_validate_upload_file, $field['title'], $imageFileType);
                                        $dh->set_error($error_message); 
                                    }
                                } else {
                                    $checkvalue = $db->fetch_field($db->simple_select('partnerboards', $field['identification'], 'tid = '.$thread['tid']), $field['identification']);
                                    if (empty($checkvalue)) {
                                        $error_message = $lang->sprintf($lang->partnerboards_validate_upload, $field['title']);
                                        $dh->set_error($error_message); 
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// BEARBEITEN - SPEICHERN
function partnerboards_do_editpost() {

    global $mybb, $db, $forum, $thread, $pid, $tid;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    // zurück, wenn es nicht der Partner Bereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($forum['fid'], $relevant_forums)) return;

    // post isnt the first post in thread
    if ($thread['firstpost'] != $pid) return;

    // Mögliche gespeicherte Entwürfe löschen
    $db->delete_query("partnerboards", "tid = '".$mybb->get_input('tid', MyBB::INPUT_INT)."'");

    // SPEICHERN
    $update_partner = array(
        'indexdisplay' => (int)$mybb->get_input('indexdisplay'),
    );

    // Abfrage der individuellen Felder
    $fields_query = $db->query("SELECT identification, type FROM ".TABLE_PREFIX."partnerboards_fields");
    
    while ($field = $db->fetch_array($fields_query)) {
        $identification = $field['identification'];
        $type = $field['type'];
    
        if ($type == 'multiselect' || $type == 'checkbox') {
            $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
            $value = implode(",", array_map('trim', $value));
        } else if ($type == 'upload') {
            require_once MYBB_ROOT."inc/functions_upload.php";
            require_once MYBB_ROOT."inc/functions.php";

            if (!empty($mybb->get_input('delete_'.$identification))) {
                $folder_path = MYBB_ROOT.'uploads/partnerboards';
                $filename = $db->fetch_field($db->simple_select('partnerboards', $identification, 'tid = '.$tid), $identification);
                $clean_filename = explode('?', $filename)[0];
                delete_uploaded_file($folder_path."/".$clean_filename);

                $value = "";
            } else {
                if(!empty($_FILES[$identification]['name'])) {
                    $fileParts = explode(".", $_FILES[$identification]['name']);
                    $imageFileType = end($fileParts);
                    $filename = $identification.'_tid'.$tid.'.'.$imageFileType;
                    $value = $filename.'?dateline='.time();
                    $folder_path = MYBB_ROOT.'uploads/partnerboards';
                    
                    move_uploaded_file($_FILES[$identification]['tmp_name'], $folder_path."/".$filename);
                } else {
                    $value = $db->fetch_field($db->simple_select('partnerboards', $identification, 'tid = '.$tid), $identification);
                }
            }
        } else {
            $value = $mybb->get_input($identification);
        }
    
        $update_partner[$identification] = $db->escape_string($value);
    }

    if ($db->num_rows($db->simple_select("partnerboards", "tid", "tid= '".$tid."'")) > 0) {
        $db->update_query("partnerboards", $update_partner, "tid='".$tid."'");
    } else {
        $update_partner = array(
            'tid' => (int)$tid
        );
        $db->insert_query("partnerboards", $update_partner);
    }
}

// THEMA WIRD GELÖSCHT -> MODERATIONS
function partnerboards_delete_thread($tid) {

    global $tid, $db, $mybb;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    $thread = get_thread($tid);

    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);

    if(in_array($thread['fid'], $relevant_forums)) {
        $db->delete_query("partnerboards", "tid = '".$tid."'");

        require_once MYBB_ROOT."inc/functions_upload.php";
        require_once MYBB_ROOT."inc/functions.php";
        
        $folder = MYBB_ROOT."uploads/partnerboards/";
        if (is_dir($folder)) {
            $files = array_diff(scandir($folder), array('.', '..'));
            foreach ($files as $file) {
                $filePath = $folder . $file;
                if (is_file($filePath) && preg_match("/_tid" . $tid . "\.[a-zA-Z0-9]+$/", $file)) {
                    delete_uploaded_file($filePath);
                }
            }    
        }
    }
}

// FIRST POST WIRD GELÖSCHT
function partnerboards_delete_post($pid) {

    global $tid, $db, $mybb, $pid;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    $post = get_post($pid);
    $thread = get_thread($post['tid']);

    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);

    if (($thread['firstpost'] == $pid) AND (in_array($post['fid'], $relevant_forums))) {
        $db->delete_query("partnerboards", "tid = '".$post['tid']."'");

        require_once MYBB_ROOT."inc/functions_upload.php";
        require_once MYBB_ROOT."inc/functions.php";
        
        $folder = MYBB_ROOT."uploads/partnerboards/";
        if (is_dir($folder)) {
            $files = array_diff(scandir($folder), array('.', '..'));
            foreach ($files as $file) {
                $filePath = $folder . $file;
                if (is_file($filePath) && preg_match("/_tid" . $post['tid'] . "\.[a-zA-Z0-9]+$/", $file)) {
                    delete_uploaded_file($filePath);
                }
            }    
        }
    }
}

// THEMA WIRD GELÖSCHT -> EDIT FIRST POST
function partnerboards_deletepost() {

    global $tid, $db, $mybb, $pid;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    $thread = get_thread($tid);

    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);

    if($mybb->get_input('delete', MyBB::INPUT_INT) == 1) {
        if (($thread['firstpost'] == $pid) AND (in_array($thread['fid'], $relevant_forums))) {
            $db->delete_query("partnerboards", "tid = '".$tid."'");

            require_once MYBB_ROOT."inc/functions_upload.php";
            require_once MYBB_ROOT."inc/functions.php";
            
            $folder = MYBB_ROOT."uploads/partnerboards/";
            if (is_dir($folder)) {
                $files = array_diff(scandir($folder), array('.', '..'));
                foreach ($files as $file) {
                    $filePath = $folder . $file;
                    if (is_file($filePath) && preg_match("/_tid" . $tid . "\.[a-zA-Z0-9]+$/", $file)) {
                        delete_uploaded_file($filePath);
                    }
                }    
            }
        }
    }
}

// FORUMDISPLAY
function partnerboards_forumdisplay_thread() {

    global $templates, $mybb, $lang, $db, $thread, $partnerboards_forumdisplay, $display_offpartner, $display_onlypartner;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    // CSS Variable zum Verstecken 
    $display_onlypartner = "style=\"display:none;\"";
    $display_offpartner = "";

    // Thread- und Foren-ID
    $tid = $thread['tid'];
    $fid = $thread['fid'];

    $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");
    while ($spalte = $db->fetch_array($spalten_query)) {
        $partnerboards[$spalte['identification']] = '';
    }

    $partnerboards_forumdisplay = "";

    // zurück, wenn es nicht der Partner Bereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('partnerboards');

    // CSS Variable zum Verstecken
    $display_offpartner = "style=\"display:none;\"";
    $display_onlypartner = "";

    $partnerboards_forumdisplay = "";

    // Infos aus der DB ziehen
    $info = $db->fetch_array($db->simple_select('partnerboards', '*', 'tid = ' . $tid));

    if (empty($info) && !is_array($info)) return;

    $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");

    $partnerboardsfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $title = "";
        $value = "";
        $allow_html = "";
        $allow_mybb = "";
        $type = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $title = $field['title'];
        $allow_html = $field['allowhtml'];
        $allow_mybb = $field['allowmycode'];
        $type = $field['type'];

        if ($type == "upload") {
            if (!empty($info[$identification])) {
                list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($field['upload_graphicdims']));
                $value = "<img src=\"uploads/partnerboards/".$info[$identification]."\" width=\"".$maxwidth."\" height=\"".$maxheight."\">";
            } else {
                $value = "";
            }
        } else {
            $value = partnerboards_parser_fields($info[$identification], $allow_html, $allow_mybb);
        }

        // Einzelne Variabeln
        $partnerboards[$identification] = $value;

        if (!empty($value)) {
            eval("\$partnerboardsfields .= \"" . $templates->get("partnerboards_forumdisplay_fields") . "\";");
        }
    }

    // Variable für alles
    eval("\$partnerboards_forumdisplay = \"" . $templates->get("partnerboards_forumdisplay") . "\";");
}

// SHOWTHREAD
function partnerboards_showthread_start() {
	
	global $mybb, $templates, $thread, $lang, $db, $display_offpartner, $display_onlypartner, $partnerboards_showthread;

    // EINSTELLUNGEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    // CSS Variable zum Verstecken 
    $display_offpartner = "";
    $display_onlypartner = "style=\"display:none;\"";

    // Thread- und Foren-ID
    $tid = $thread['tid'];
    $fid = $thread['fid'];

    $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");
    while ($spalte = $db->fetch_array($spalten_query)) {
        $partnerboards[$spalte['identification']] = '';
    }

    // zurück, wenn es nicht der Partnerbereich ist
    $partnerforums = $managementarea.",".$adoptedarea;
    $relevant_forums = partnerboards_get_relevant_forums($partnerforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('partnerboards');

    // CSS Variable zum Verstecken
    $display_offpartner = "style=\"display:none;\"";
    $display_onlypartner = "";

    // Infos aus der DB ziehen
    $info = $db->fetch_array($db->simple_select('partnerboards', '*', 'tid = ' . $tid));

    if (empty($info) && !is_array($info)) return;

    $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");

    $partnerboardsfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $title = "";
        $value = "";
        $allow_html = "";
        $allow_mybb = "";
        $type = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $title = $field['title'];
        $allow_html = $field['allowhtml'];
        $allow_mybb = $field['allowmycode'];
        $type = $field['type'];

        if ($type == "upload") {
            if (!empty($info[$identification])) {
                list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($field['upload_graphicdims']));
                $value = "<img src=\"uploads/partnerboards/".$info[$identification]."\" width=\"".$maxwidth."\" height=\"".$maxheight."\">";
            } else {
                $value = "";
            }
        } else {
            $value = partnerboards_parser_fields($info[$identification], $allow_html, $allow_mybb);
        }

        // Einzelne Variabeln
        $partnerboards[$identification] = $value;

        if (!empty($value)) {
            eval("\$partnerboardsfields .= \"" . $templates->get("partnerboards_showthread_fields") . "\";");
        }
    }

    // Variable für alles
    eval("\$partnerboards_showthread = \"" . $templates->get("partnerboards_showthread") . "\";");
}

// MODCP - NAVIGATION
function partnerboards_modcp_nav() {

    global $db, $mybb, $templates, $theme, $header, $headerinclude, $footer, $lang, $modcp_nav, $nav_partnerboards;

	// SPRACHDATEI
	$lang->load('partnerboards');

	eval("\$nav_partnerboards = \"".$templates->get ("partnerboards_modcp_nav")."\";");
}

// MODCP - LISTE
function partnerboards_modcp() {
   
    global $mybb, $templates, $lang, $theme, $header, $headerinclude, $footer, $db, $page, $modcp_nav;

	// EINSTELLUNGEN ZIEHEN
    $managementarea = $mybb->settings['partnerboards_managementarea'];
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];

    if ($mybb->get_input('action', MYBB::INPUT_STRING) !== 'partnerboards') {
        return;
    }

	// SPRACHDATEI
	$lang->load('partnerboards');

    if($mybb->get_input('action') == 'partnerboards') {

        // Add a breadcrumb
        add_breadcrumb($lang->nav_modcp, "modcp.php");
        add_breadcrumb($lang->partnerboards_modcp, "modcp.php?action=partnerboards");

        $relevant_forums_management = partnerboards_get_relevant_forums($managementarea);
        $relevant_forums_adoptedarea = partnerboards_get_relevant_forums($adoptedarea);

        // noch in der Verwaltung 
        $management_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (p.tid = t.tid) 
        WHERE fid IN (".implode(',', $relevant_forums_management).")
        ORDER BY t.dateline ASC
        ");

        $management_foren = "";
        while($allmanagement = $db->fetch_array($management_query)) {

            // Leer laufen lassen
            $subject = "";
            $tid = "";
            $pid = "";
            $topiclink = "";
            $postdate = "";
            $posteruid = "";
            $poster = "";
            $posterlink = "";

            // Mit Infos füllen
            $subject = $allmanagement['subject'];
            $tid = $allmanagement['tid'];
            $pid = $allmanagement['firstpost'];
            $topiclink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
            $postdate = my_date('relative', $allmanagement['dateline']);
            $posteruid = $allmanagement['uid'];
            $poster = $allmanagement['username'];

            if($posteruid == 0){
                $posterlink = $poster;
            } else {
                $posterlink = build_profile_link($poster, $posteruid);
            }
        
            $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");
        
            $partnerboard = [];
            $partnerboardsfields = "";
            while ($field = $db->fetch_array($fields_query)) {

                // Leer laufen lassen
                $identification = "";
                $title = "";
                $value = "";
                $allow_html = "";
                $allow_mybb = "";
                $type = "";
        
                // Mit Infos füllen
                $identification = $field['identification'];
                $title = $field['title'];
                $allow_html = $field['allowhtml'];
                $allow_mybb = $field['allowmycode'];
                $type = $field['type'];

                if ($type == "upload") {
                    if (!empty($allmanagement[$identification])) {
                        list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($field['upload_graphicdims']));
                        $value = "<img src=\"uploads/partnerboards/".$allmanagement[$identification]."\" width=\"".$maxwidth."\" height=\"".$maxheight."\">";
                    } else {
                        $value = "";
                    }
                } else {
                    $value = partnerboards_parser_fields($allmanagement[$identification], $allow_html, $allow_mybb);
                }
        
                // Einzelne Variabeln
                $partnerboard[$identification] = $value;

                if (!empty($value)) {
                    eval("\$partnerboardsfields .= \"" . $templates->get("partnerboards_modcp_fields") . "\";");
                }
            }

            eval("\$management_foren .= \"".$templates->get("partnerboards_modcp_forenbit")."\";");
        }
        if (empty($management_foren)) {
            $management_foren = $lang->partnerboards_modcp_none_management;
        }

        // angenommene Partnerforen
        $adopted_foren = "";
        foreach ($relevant_forums_adoptedarea as $fid) {

            $count_threads = $db->num_rows($db->simple_select("threads", "tid", "fid= '".$fid."'"));

            if ($count_threads <= 0) {
                continue;
            }
            
            $forumname = $db->fetch_field($db->simple_select("forums", "name", "fid= '".$fid."'"), "name");

            $adopted_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
            LEFT JOIN ".TABLE_PREFIX."threads t 
            ON (p.tid = t.tid) 
            WHERE fid = ".$fid."
            ORDER BY t.subject ASC
            ");

            $adopted_foren_bit = "";
            while($alladopted = $db->fetch_array($adopted_query)) {
    
                // Leer laufen lassen
                $subject = "";
                $tid = "";
                $pid = "";
                $topiclink = "";
                $postdate = "";
                $posteruid = "";
                $poster = "";
                $posterlink = "";
    
                // Mit Infos füllen
                $subject = $alladopted['subject'];
                $tid = $alladopted['tid'];
                $pid = $alladopted['firstpost'];
                $topiclink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
                $postdate = my_date('relative', $alladopted['dateline']);
                $posteruid = $alladopted['uid'];
                $poster = $alladopted['username'];
    
                if($posteruid == 0){
                    $posterlink = $poster;
                } else {
                    $posterlink = build_profile_link($poster, $posteruid);
                }
            
                $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");
            
                $partnerboard = [];
                $partnerboardsfields = "";
                while ($field = $db->fetch_array($fields_query)) {
    
                    // Leer laufen lassen
                    $identification = "";
                    $title = "";
                    $value = "";
                    $allow_html = "";
                    $allow_mybb = "";
                    $type = "";
            
                    // Mit Infos füllen
                    $identification = $field['identification'];
                    $title = $field['title'];
                    $allow_html = $field['allowhtml'];
                    $allow_mybb = $field['allowmycode'];
                    $type = $field['type'];
    
                    if ($type == "upload") {
                        if (!empty($alladopted[$identification])) {
                            list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($field['upload_graphicdims']));
                            $value = "<img src=\"uploads/partnerboards/".$alladopted[$identification]."\" width=\"".$maxwidth."\" height=\"".$maxheight."\">";
                        } else {
                            $value = "";
                        }
                    } else {
                        $value = partnerboards_parser_fields($alladopted[$identification], $allow_html, $allow_mybb);
                    }
            
                    // Einzelne Variabeln
                    $partnerboard[$identification] = $value;
    
                    if (!empty($value)) {
                        eval("\$partnerboardsfields .= \"" . $templates->get("partnerboards_modcp_fields") . "\";");
                    }
                }
    
                eval("\$adopted_foren_bit .= \"".$templates->get("partnerboards_modcp_forenbit")."\";");
            }

            eval("\$adopted_foren .= \"".$templates->get("partnerboards_modcp_partnerareas")."\";"); 
        }
        if (empty($adopted_foren)) {
            $adopted_foren = $lang->partnerboards_modcp_none_adopted;
        }

        eval("\$page = \"".$templates->get("partnerboards_modcp")."\";");
        output_page($page);
        die();
    }
}

// INDEX ANZEIGE - Klassik
function partnerboards_global() {

    global $db, $cache, $mybb, $templates, $lang, $partnerboards_index, $partnerboards_index_bit;
	
	// SPRACHDATEI
	$lang->load('partnerboards');

	// EINSTELLUNGEN
    $indexdisplay_setting = $mybb->settings['partnerboards_indexdisplay'];

    $partnerboards_index_bit = "";

    if ($indexdisplay_setting == 0) return;

    if ($indexdisplay_setting == 1) { // Sister
        $sisterarea = $mybb->settings['partnerboards_sisterarea'];

        $index_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (p.tid = t.tid) 
        WHERE t.fid = ".$sisterarea."
        ORDER BY t.subject ASC
        ");
    } else if($indexdisplay_setting == 2) { // alle
        $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];
        $relevant_forums_adoptedarea = partnerboards_get_relevant_forums($adoptedarea);

        $index_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (p.tid = t.tid) 
        WHERE fid IN (".implode(',', $relevant_forums_adoptedarea).")
        ORDER BY t.subject ASC
        ");
    } else { // individuell
        $index_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (p.tid = t.tid) 
        WHERE indexdisplay = 1
        ORDER BY t.subject ASC
        ");
    }

    $index_foren = "";        
    while($index = $db->fetch_array($index_query)) {

        // Leer laufen lassen
        $subject = "";
        $tid = "";
        $pid = "";
        $topiclink = "";

        // Mit Infos füllen
        $subject = $index['subject'];
        $tid = $index['tid'];
        $pid = $index['firstpost'];
        $topiclink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
        
        $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");
        
        $partnerboard = [];
        while ($field = $db->fetch_array($fields_query)) {

            // Leer laufen lassen
            $identification = "";
            $title = "";
            $value = "";
            $allow_html = "";
            $allow_mybb = "";
            $type = "";
        
            // Mit Infos füllen
            $identification = $field['identification'];
            $title = $field['title'];
            $allow_html = $field['allowhtml'];
            $allow_mybb = $field['allowmycode'];
            $type = $field['type'];

            if ($type == "upload") {
                if (!empty($index[$identification])) {
                    list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($field['upload_graphicdims']));
                    $value = "<img src=\"uploads/partnerboards/".$index[$identification]."\" width=\"".$maxwidth."\" height=\"".$maxheight."\">";
                } else {
                    $value = "";
                }
            } else {
                $value = partnerboards_parser_fields($index[$identification], $allow_html, $allow_mybb);
            }
    
            // Einzelne Variabeln
            $partnerboard[$identification] = $value;   
        }

        eval("\$index_foren .= \"".$templates->get("partnerboards_index_bit")."\";");
    }
    if(empty($index_foren)) {
        $index_foren = $lang->partnerboards_index_none;
    }

	eval("\$partnerboards_index = \"".$templates->get("partnerboards_index")."\";");
}

// INDEX ANZEIGE - Forumbit
function partnerboards_forumbit(&$forum) {

    global $db, $cache, $mybb, $templates, $lang, $partnerboards_index, $partnerboards_index_bit;
	
	// SPRACHDATEI
	$lang->load('partnerboards');

	// EINSTELLUNGEN
    $indexdisplay_setting = $mybb->settings['partnerboards_indexdisplay'];
    $indexforumbit_setting = $mybb->settings['partnerboards_indexforumbit'];

    $forum['partnerboards_index'] = "";

    if ($indexdisplay_setting == 0 OR $indexforumbit_setting == -1) return;

    if ($forum['fid'] != $indexforumbit_setting) {
        $forum['partnerboards_index'] = "";
        return;    
    }

    if ($indexdisplay_setting == 1) { // Sister
        $sisterarea = $mybb->settings['partnerboards_sisterarea'];

        $index_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (p.tid = t.tid) 
        WHERE t.fid = ".$sisterarea."
        ORDER BY t.subject ASC
        ");
    } else if($indexdisplay_setting == 2) { // alle
        $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];
        $relevant_forums_adoptedarea = partnerboards_get_relevant_forums($adoptedarea);

        $index_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (p.tid = t.tid) 
        WHERE fid IN (".implode(',', $relevant_forums_adoptedarea).")
        ORDER BY t.subject ASC
        ");
    } else { // individuell
        $index_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (p.tid = t.tid) 
        WHERE indexdisplay = 1
        ORDER BY t.subject ASC
        ");
    }

    $index_foren = "";        
    while($index = $db->fetch_array($index_query)) {

        // Leer laufen lassen
        $subject = "";
        $tid = "";
        $pid = "";
        $topiclink = "";

        // Mit Infos füllen
        $subject = $index['subject'];
        $tid = $index['tid'];
        $pid = $index['firstpost'];
        $topiclink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
        
        $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");
        
        $partnerboard = [];
        while ($field = $db->fetch_array($fields_query)) {

            // Leer laufen lassen
            $identification = "";
            $title = "";
            $value = "";
            $allow_html = "";
            $allow_mybb = "";
            $type = "";
        
            // Mit Infos füllen
            $identification = $field['identification'];
            $title = $field['title'];
            $allow_html = $field['allowhtml'];
            $allow_mybb = $field['allowmycode'];
            $type = $field['type'];

            if ($type == "upload") {
                if (!empty($index[$identification])) {
                    list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($field['upload_graphicdims']));
                    $value = "<img src=\"uploads/partnerboards/".$index[$identification]."\" width=\"".$maxwidth."\" height=\"".$maxheight."\">";
                } else {
                    $value = "";
                }
            } else {
                $value = partnerboards_parser_fields($index[$identification], $allow_html, $allow_mybb);
            }
    
            // Einzelne Variabeln
            $partnerboard[$identification] = $value;   
        }

        eval("\$index_foren .= \"".$templates->get("partnerboards_index_bit")."\";");
    }
    if(empty($index_foren)) {
        $index_foren = $lang->partnerboards_index_none;
    }

	eval("\$forum['partnerboards_index'] = \"".$templates->get("partnerboards_index")."\";");
}

// MISC ÜBERSICHT
function partnerboards_misc() {

    global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $page;

    // return if the action key isn't part of the input
    if ($mybb->get_input('action', MYBB::INPUT_STRING) !== 'partnerboards') {
        return;
    }

	// EINSTELLUNGEN ZIEHEN
    $adoptedarea = $mybb->settings['partnerboards_adoptedarea'];
    $overview_setting = $mybb->settings['partnerboards_overview'];
    $overview_permissions = $mybb->settings['partnerboards_overview_permissions'];

	// SPRACHDATEI
	$lang->load('partnerboards');

    if ($overview_setting == 0) {
       redirect('index.php', $lang->partnerboards_overview_redirect);
    }

    if(!is_member($overview_permissions)) {
        error_no_permission();
    }

    if($mybb->get_input('action') == 'partnerboards') {

        // Add a breadcrumb
        add_breadcrumb($lang->partnerboards_overview, "misc.php?action=partnerboards");

        $relevant_forums_adoptedarea = partnerboards_get_relevant_forums($adoptedarea);

        // angenommene Partnerforen
        $partner_foren = "";
        foreach ($relevant_forums_adoptedarea as $fid) {

            $count_threads = $db->num_rows($db->simple_select("threads", "tid", "fid= '".$fid."'"));

            if ($count_threads <= 0) {
                continue;
            }
            
            $forumname = $db->fetch_field($db->simple_select("forums", "name", "fid= '".$fid."'"), "name");

            $partnerboards_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards p
            LEFT JOIN ".TABLE_PREFIX."threads t 
            ON (p.tid = t.tid) 
            WHERE fid = ".$fid."
            ORDER BY t.subject ASC
            ");

            $partner_foren_bit = "";
            while($allpartner = $db->fetch_array($partnerboards_query)) {
    
                // Leer laufen lassen
                $subject = "";
                $tid = "";
                $pid = "";
                $topiclink = "";
                $postdate = "";
                $posteruid = "";
                $poster = "";
                $posterlink = "";
    
                // Mit Infos füllen
                $subject = $allpartner['subject'];
                $tid = $allpartner['tid'];
                $pid = $allpartner['firstpost'];
                $topiclink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
                $postdate = my_date('relative', $allpartner['dateline']);
                $posteruid = $allpartner['uid'];
                $poster = $allpartner['username'];
    
                if($posteruid == 0){
                    $posterlink = $poster;
                } else {
                    $posterlink = build_profile_link($poster, $posteruid);
                }
            
                $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields ORDER BY disporder ASC, title ASC");
            
                $partnerboard = [];
                $partnerboardsfields = "";
                while ($field = $db->fetch_array($fields_query)) {
    
                    // Leer laufen lassen
                    $identification = "";
                    $title = "";
                    $value = "";
                    $allow_html = "";
                    $allow_mybb = "";
                    $type = "";
            
                    // Mit Infos füllen
                    $identification = $field['identification'];
                    $title = $field['title'];
                    $allow_html = $field['allowhtml'];
                    $allow_mybb = $field['allowmycode'];
                    $type = $field['type'];

                    if ($type == "upload") {
                        if (!empty($allpartner[$identification])) {
                            list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($field['upload_graphicdims']));
                            $value = "<img src=\"uploads/partnerboards/".$allpartner[$identification]."\" width=\"".$maxwidth."\" height=\"".$maxheight."\">";
                        } else {
                            $value = "";
                        }
                    } else {
                        $value = partnerboards_parser_fields($allpartner[$identification], $allow_html, $allow_mybb);
                    }
            
                    // Einzelne Variabeln
                    $partnerboard[$identification] = $value;
    
                    if (!empty($value)) {
                        eval("\$partnerboardsfields .= \"" . $templates->get("partnerboards_overview_fields") . "\";");
                    }
                }
    
                eval("\$partner_foren_bit .= \"".$templates->get("partnerboards_overview_forenbit")."\";");
            }

            eval("\$partner_foren .= \"".$templates->get("partnerboards_overview_partnerareas")."\";"); 
        }
        if (empty($partner_foren)) {
            $partner_foren = $lang->partnerboards_overview_none;
        }

        eval("\$page = \"".$templates->get("partnerboards_overview")."\";");
        output_page($page);
        die();
    }
}

// PARTNERBEREICH FIDS
function partnerboards_get_relevant_forums($relevantforums) {

    global $db, $mybb;

    $relevantforums = trim($relevantforums, ',');
    
    $partnerarea = array_filter(explode(',', $relevantforums), function($fid) {
        return trim($fid) !== '-1'; 
    });

    if (empty($partnerarea)) {
        return [0];
    }

    $relevant_forums = [];
    foreach ($partnerarea as $fid) {

        $fid = trim($fid); 
        if (empty($fid) || $fid == '-1') {
            continue;
        }

        $query = $db->query("SELECT fid FROM ".TABLE_PREFIX."forums 
        WHERE (concat(',',parentlist,',') LIKE '%,".$fid.",%')
        ");
    
        while ($forum = $db->fetch_array($query)) {
            $relevant_forums[] = $forum['fid'];
        }
    }

    $relevant_forums = array_filter(array_unique($relevant_forums));

    if (empty($relevant_forums)) {
        return [0];
    }

    return $relevant_forums;
}

// PARTNERBEREICHFELDER AUSLESEN
function partnerboards_generate_fields($draft = null, $input_data = null, $tid = "") {

    global $db, $mybb, $templates;

    $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."partnerboards_fields 
    WHERE editableby != ''
    ORDER BY disporder ASC, title ASC
    ");

    $own_partnerboardsfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $description = "";
        $type = "";
        $options = "";
        $required = "";
        $editableby = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $description = $field['description'];
        $type = $field['type'];
        $options = $field['options'];
        $required = $field['required'];
        $editableby = $field['editableby'];

        if ($input_data) {
            if ($type == "multiselect" || $type == "checkbox") {
                $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
            } else if ($type == "upload" AND !empty($tid)) {
                $value = $db->fetch_field($db->simple_select('partnerboards', $identification, 'tid = '.$tid), $identification);
            } else {
                $value = $mybb->get_input($identification);
            }
        } elseif ($draft) {
            $value = $draft[$identification];
        } else {
            $value = ""; 
        }

        if ($required == 1) {
            $title = $field['title']."*";
        } else {
            $title = $field['title'];
        }

        // INPUTS generieren
        $code = partnerboards_generate_input_field($identification, $type, $value, $options);

        if ($editableby != -1) {
            $editableby_groups = explode(",", $editableby);
            $has_permission = false;
            foreach ($editableby_groups as $group) {
                if (($mybb->user['usergroup'] == $group) OR (in_array($group, explode(",", $mybb->user['additionalgroups'])))) {
                    $has_permission = true;
                    break;
                }
            }
        
            if ($has_permission) {
                eval("\$own_partnerboardsfields .= \"".$templates->get("partnerboards_newthread_fields")."\";");
            }
        } else {
            eval("\$own_partnerboardsfields .= \"".$templates->get("partnerboards_newthread_fields")."\";");
        }
        
    }

    return $own_partnerboardsfields;
}

// INPUT FELDER GENERIEN
function partnerboards_generate_input_field($identification, $type, $value = '', $options = '') {

    global $lang;

    $lang->load('partnerboards');

    $input = '';

    switch ($type) {
        case 'text':
            $input = '<input type="text" class="textbox" size="40" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;

        case 'textarea':
            $input = '<textarea name="'.htmlspecialchars($identification).'" rows="6" cols="42">' . htmlspecialchars($value) . '</textarea>';
            break;

        case 'date':
            $input = '<input type="date" class="textbox" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;

        case 'url':
            $input = '<input type="url" class="textbox" size="40" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;

        case 'upload':
            if (!empty($value)) {
                $fileParts = explode("?", $value);
                $edit_upload = "<div style=\"padding-bottom: 1em;\"><b>".$lang->partnerboards_edit_upload_active."</b> <a href=\"uploads/partnerboards/".$value."\" target=\"_blank\">".$fileParts[0]."</a><span style=\"margin-left: 0.5em;\"><input type=\"checkbox\" name=\"delete_".htmlspecialchars($identification)."\" id=\"delete_".htmlspecialchars($identification)."\" class=\"checkbox\" value=\"1\">".$lang->partnerboards_edit_upload_del."</span></div><b>".$lang->partnerboards_edit_upload_new."</b><br>";
            } else {
                $edit_upload = "";
            }
            $input = $edit_upload.'<input type="file" id="'.htmlspecialchars($identification).'" name="'.htmlspecialchars($identification).'">';
            break;

        case 'radio':
            $expoptions = explode("\n", $options);
            foreach ($expoptions as $option) {
                $checked = ($option == $value) ? ' checked' : '';
                $input .= '<input type="radio" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($option) . '"' . $checked . '>';
                $input .= '<span class="smalltext">' . htmlspecialchars($option) . '</span><br />';
            }
            break;

        case 'select':
            $expoptions = explode("\n", $options);
            $input = '<select name="'.htmlspecialchars($identification).'">';
            foreach ($expoptions as $option) {
                $selected = ($option == $value) ? ' selected' : '';
                $input .= '<option value="' . htmlspecialchars($option) . '"' . $selected . '>' . htmlspecialchars($option) . '</option>';
            }
            $input .= '</select>';
            break;

        case 'multiselect':
            $expoptions = explode("\n", $options);
            $value = is_array($value) ? $value : explode(',', $value);
            $input = '<select name="'.htmlspecialchars($identification).'[]" multiple>';
            foreach ($expoptions as $option) {
                $selected = in_array($option, $value) ? ' selected' : '';
                $input .= '<option value="' . htmlspecialchars($option) . '"' . $selected . '>' . htmlspecialchars($option) . '</option>';
            }
            $input .= '</select>';
            break;

        case 'checkbox':
            $expoptions = explode("\n", $options);
            $value = is_array($value) ? $value : explode(',', $value);
            foreach ($expoptions as $option) {
                $checked = in_array($option, $value) ? ' checked' : '';
                $input .= '<input type="checkbox" name="'.htmlspecialchars($identification).'[]" value="' . htmlspecialchars($option) . '"' . $checked . '>';
                $input .= '<span class="smalltext">' . htmlspecialchars($option) . '</span><br />';
            }
            break;

        default:
            $input = '<input type="text" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;
    }

    return $input;
}

// INDEXANZEIGE RADIOBUTTONS GENERIEN
function partnerboards_generate_radiobuttons_indexdisplay($selected_indexdisplay = '') {

    global $lang;

	$lang->load('partnerboards');

    // EINSTELLUNGEN
    $indexdisplay_options = array(
        1 => "Ja",
        0 => "Nein"
    );

    $radiobuttons = "";
    foreach ($indexdisplay_options as $key => $value) {
        $checked = ((int)$key === (int)$selected_indexdisplay) ? ' checked' : '';
        $radiobuttons .= '<label>';
        $radiobuttons .= '<input type="radio" name="indexdisplay" value="' . htmlspecialchars($key) . '"' . $checked . '>';
        $radiobuttons .= htmlspecialchars($value);
        $radiobuttons .= '</label><br>';
    }

    return $radiobuttons;
}

// PARSER OPTIONEN FELDR
function partnerboards_parser_fields($fieldvalue, $allow_html, $allow_mybb) {

    global $parser, $parser_array;
                
    require_once MYBB_ROOT."inc/class_parser.php";
        
    $parser = new postParser;
    $parser_array = array(
        "allow_html" => $allow_html,
        "allow_mycode" => $allow_mybb,
        "allow_smilies" => 0,
        "allow_imgcode" => 0,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );

    $value = $parser->parse_message($fieldvalue, $parser_array);

    return $value;
}

// DATENBANKTABELLEN
function partnerboards_database() {

    global $db;
    
    // DATENBANKEN ERSTELLEN
    // Boards
    if (!$db->table_exists("partnerboards")) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."partnerboards (
            `pbid` int(10) NOT NULL AUTO_INCREMENT, 
            `tid` int(11) unsigned,
            `indexdisplay` int(1) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY(`pbid`),
            KEY `pbid` (`pbid`)
            )
            ENGINE=InnoDB ".$db->build_create_table_collation().";
        ");
    }
    // Felder
    if (!$db->table_exists("partnerboards_fields")) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."partnerboards_fields (
            `pbfid` int(10) NOT NULL AUTO_INCREMENT, 
            `identification` VARCHAR(250) NOT NULL,
            `title` VARCHAR(250) NOT NULL,
            `description` VARCHAR(500),
            `type` VARCHAR(250) NOT NULL,
            `options` VARCHAR(500),
            `upload_extensions` VARCHAR(100),
            `upload_graphicdims` VARCHAR(100),
            `upload_bytesize` VARCHAR(100),
            `required` int(1) NOT NULL DEFAULT '0',
            `disporder` int(5) NOT NULL DEFAULT '0',
            `editableby` VARCHAR(500),
            `allowhtml` int(1) NOT NULL DEFAULT '0',
            `allowmycode` int(1) NOT NULL DEFAULT '0',
            PRIMARY KEY(`pbfid`),
            KEY `pbfid` (`pbfid`)
            )
            ENGINE=InnoDB ".$db->build_create_table_collation().";
        ");
    }
}

// VERZEICHNISSE
function partnerboards_directories() {
    if (!is_dir(MYBB_ROOT.'uploads/partnerboards')) {
        mkdir(MYBB_ROOT.'uploads/partnerboards', 0777, true);
    }
}

// EINSTELLUNGEN
function partnerboards_settings($type = 'install') {

    global $db; 

    $setting_array = array(
		'partnerboards_managementarea' => array(
			'title' => 'Bereich für die Verwaltung',
			'description' => 'Bei welchem Forum oder welchen Foren handelt es sich um den Bereich für Anfragen, Bestätigungen und weitere Verwaltungsbereiche, wo die Felder angezeigt werden sollen? Es reicht aus, die übergeordnete Kategorien oder Forum zu markieren.',
			'optionscode' => 'forumselect',
			'value' => 0, // Default
			'disporder' => 1
		),
		'partnerboards_adoptedarea' => array(
			'title' => 'Bereich für angenommene Partner',
			'description' => 'Bei welchem Forum oder welchen Foren handelt es sich um den Bereich für angenommene Partnerforen? Es reicht aus, die übergeordnete Kategorien oder Forum zu markieren.',
			'optionscode' => 'forumselect',
			'value' => 0, // Default
			'disporder' => 2
		),
		'partnerboards_rules' => array(
			'title' => 'Kriterien für Partnerschaftsanfrage',
			'description' => 'Formuliere hier die zusammengefassten Kriterien für Partnerschaftsanfrage. HTML und BBCode sind möglich.',
			'optionscode' => 'textarea',
			'value' => '', // Default
			'disporder' => 3
		),
		'partnerboards_indexdisplay' => array(
			'title' => 'Darstellung auf dem Index',
			'description' => 'Sollen bestimmte angenommene Partnerforen auf dem Index ausgelesen werden?',
			'optionscode' => 'select\n0=keine Anzeige\n1=nur Sisterforen\n2=alle Partnerforen\n3=individuell entscheiden',
			'value' => 0, // Default
			'disporder' => 4
		),
        'partnerboards_indexforumbit' => array(
			'title' => 'Ort der Anzeige auf dem Index',
			'description' => 'Soll neben der klassischen Anzeige auf dem Index noch eine Variable gebildet werden für die Darstellung zwischen den Foren? Keins = Nein<br>Beide Variabeln können parallel/pro Design genutzt werden.',
			'optionscode' => 'forumselectsingle',
			'value' => -1, // Default
			'disporder' => 5
		),
		'partnerboards_sisterarea' => array(
			'title' => 'Bereich für besondere Partner (Sister)',
			'description' => 'Bei welchem Forum handelt es sich um den Bereich für besondere Partnerforen (Sister)?',
			'optionscode' => 'forumselectsingle',
			'value' => 0, // Default
			'disporder' => 6
		),
		'partnerboards_overview' => array(
			'title' => 'Übersichtsseite',
			'description' => 'Soll es eine extra Übersichtsseite geben für alle angenommen Partner? Im ModCP gibt es eine eigene Übersicht.',
			'optionscode' => 'yesno',
			'value' => 0, // Default
			'disporder' => 7
		),
		'partnerboards_overview_permissions' => array(
			'title' => 'Übersichtsseite - Berechtigungen',
			'description' => 'Welche Gruppen dürfen die Übersichtsseite der Partnerforen sehen?',
			'optionscode' => 'groupselect',
			'value' => 0, // Default
			'disporder' => 8
		),
	);

    $gid = $db->fetch_field($db->write_query("SELECT gid FROM ".TABLE_PREFIX."settinggroups WHERE name = 'partnerboards' LIMIT 1;"), "gid");

    if ($type == 'install') {
        foreach ($setting_array as $name => $setting) {
          $setting['name'] = $name;
          $setting['gid'] = $gid;
          $db->insert_query('settings', $setting);
        }  
    }

    if ($type == 'update') {

        // Einzeln durchgehen 
        foreach ($setting_array as $name => $setting) {
            $setting['name'] = $name;
            $check = $db->write_query("SELECT name FROM ".TABLE_PREFIX."settings WHERE name = '".$name."'"); // Überprüfen, ob sie vorhanden ist
            $check = $db->num_rows($check);
            $setting['gid'] = $gid;
            if ($check == 0) { // nicht vorhanden, hinzufügen
              $db->insert_query('settings', $setting);
            } else { // vorhanden, auf Änderungen überprüfen
                
                $current_setting = $db->fetch_array($db->write_query("
                    SELECT title, description, optionscode, disporder 
                    FROM ".TABLE_PREFIX."settings 
                    WHERE name = '".$db->escape_string($name)."'
                "));
            
                $update_needed = false;
                $update_data = array();
            
                if ($current_setting['title'] != $setting['title']) {
                    $update_data['title'] = $setting['title'];
                    $update_needed = true;
                }
                if ($current_setting['description'] != $setting['description']) {
                    $update_data['description'] = $setting['description'];
                    $update_needed = true;
                }
                if ($current_setting['optionscode'] != $setting['optionscode']) {
                    $update_data['optionscode'] = $setting['optionscode'];
                    $update_needed = true;
                }
                if ($current_setting['disporder'] != $setting['disporder']) {
                    $update_data['disporder'] = $setting['disporder'];
                    $update_needed = true;
                }
            
                if ($update_needed) {
                    $db->update_query('settings', $update_data, "name = '".$db->escape_string($name)."'");
                }
            }  
        }  
    }

    rebuild_settings();
}

// TEMPLATES
function partnerboards_templates($mode = '') {

    global $db;

    $templates[] = array(
        'title'		=> 'partnerboards_forumdisplay',
        'template'	=> $db->escape_string('<div class="smalltext">{$partnerboardsfields}</div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_forumdisplay_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_index',
        'template'	=> $db->escape_string('<tr>
        <td class="tcat">
		<strong>{$lang->partnerboards_index}</strong>
        </td>
        </tr>
        <tr>
        <td>
		{$index_foren}
        </td>
        </tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_index_bit',
        'template'	=> $db->escape_string('<div><a href="{$topiclink}">{$subject}</a></div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_modcp',
        'template'	=> $db->escape_string('<html>
        <head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->partnerboards_modcp}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$modcp_nav}
				<td valign="top" class="partnerboards_modcp">
					<div class="tborder">
						<div class="thead">
							<strong>{$lang->partnerboards_modcp_management}</strong>
						</div>
						<div class="partnerboards_modcp_head tcat">
							<div>{$lang->partnerboards_modcp_thread}</div>
							<div>{$lang->partnerboards_modcp_facts}</div>
							<div>{$lang->partnerboards_modcp_post}</div>
						</div>
						<div class="partnerboards_modcp_foren trow1">
							{$management_foren}
						</div>
					</div>

					<div class="tborder">
						<div class="thead">
							<strong>{$lang->partnerboards_modcp_adopted}</strong>
						</div>
						<div class="partnerboards_modcp_foren trow1">
							{$adopted_foren}
						</div>
					</div>
				</td>
			</tr>
		</table>
		{$footer}
        </body>
        </html>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_modcp_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_modcp_forenbit',
        'template'	=> $db->escape_string('<div class="partnerboards_modcp_bit">
        <div class="smalltext"><a href="{$topiclink}" target="_blank">{$subject}</a></div>
        <div class="smalltext">{$partnerboardsfields}</div>
        <div class="smalltext">{$postdate}<br>{$posterlink}</div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_modcp_nav',
        'template'	=> $db->escape_string('<tr><td class="trow1 smalltext"><a href="modcp.php?action=partnerboards" class="modcp_nav_item modcp_nav_modqueue">{$lang->partnerboards_modcp_nav}</td></tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_modcp_partnerareas',
        'template'	=> $db->escape_string('<div class="tcat"><strong>{$forumname}</strong></div>
        <div class="partnerboards_modcp_head trow2">
        <div>{$lang->partnerboards_modcp_thread}</div>
        <div>{$lang->partnerboards_modcp_facts}</div>
        <div>{$lang->partnerboards_modcp_post}</div>
        </div>
        {$adopted_foren_bit}'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_newthread',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1" width="20%">
		<strong>{$lang->partnerboards_newthread_rules}</strong>
        </td>
        <td class="trow1">
		<span class="smalltext">
			{$partnerboards_rules}
		</span>
        </td>	
        </tr>
        {$own_partnerboardsfields}
        {$indexdisplay_setting}'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_newthread_fields',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1" width="20%"><strong>{$title}</strong>
        <div class="smalltext">{$description}</div>
        </td>
        <td class="trow1">
        <span class="smalltext">
			{$code}
		</span>		
        </td>	
        </tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_newthread_indexdisplay',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1" width="20%">
		<strong>{$lang->partnerboards_newthread_indexdisplay_title}</strong>
		<div class="smalltext">{$lang->partnerboards_newthread_indexdisplay_desc}</div>
        </td>
        <td class="trow1">
		<span class="smalltext">
			{$indexdisplay_radiobuttons}
		</span> 
        </td>
        </tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_overview',
        'template'	=> $db->escape_string('<html>
        <head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->partnerboards_overview}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		<div class="tborder">
			<div class="thead">{$lang->partnerboards_overview}</div>
			<div class="partnerboards_overview">
				{$partner_foren}
			</div>
		</div>
		{$footer}
        </body>
        </html>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_overview_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_overview_forenbit',
        'template'	=> $db->escape_string('<div class="partnerboards_overview_bit">
        <div class="smalltext"><a href="{$topiclink}">{$subject}</a></div>
        <div class="smalltext">{$partnerboardsfields}</div>
        <div class="smalltext">{$postdate}<br>{$posterlink}</div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_overview_partnerareas',
        'template'	=> $db->escape_string('<div class="tcat"><strong>{$forumname}</strong></div>
        <div class="partnerboards_overview_head trow2">
        <div>{$lang->partnerboards_overview_thread}</div>
        <div>{$lang->partnerboards_overview_facts}</div>
        <div>{$lang->partnerboards_overview_post}</div>
        </div>
        <div class="partnerboards_overview_foren trow1">
        {$partner_foren_bit}
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_showthread',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1">
		<div class="partnerboards_showthread">
			{$partnerboardsfields}
		</div>
        </td>
        </tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'partnerboards_showthread_fields',
        'template'	=> $db->escape_string('<div class="partnerboards_showthread-bit">
        <div class="partnerboards_showthread-label"><strong>{$title}</strong></div>
        <div class="partnerboards_showthread-value">{$value}</div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    if ($mode == "update") {

        foreach ($templates as $template) {
            $query = $db->simple_select("templates", "tid, template", "title = '".$template['title']."' AND sid = '-2'");
            $existing_template = $db->fetch_array($query);

            if($existing_template) {
                if ($existing_template['template'] !== $template['template']) {
                    $db->update_query("templates", array(
                        'template' => $template['template'],
                        'dateline' => TIME_NOW
                    ), "tid = '".$existing_template['tid']."'");
                }
            }
            
            else {
                $db->insert_query("templates", $template);
            }
        }

    } else {
        foreach ($templates as $template) {
            $check = $db->num_rows($db->simple_select("templates", "title", "title = '".$template['title']."'"));
            if ($check == 0) {
                $db->insert_query("templates", $template);
            }
        }
    }
}

// STYLESHEET MASTER
function partnerboards_stylesheet() {

    global $db;
    
    $css = array(
		'name' => 'partnerboards.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" =>	'.partnerboards_modcp {
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        justify-content: flex-start;
        gap: 10px 0;
        }

        .partnerboards_modcp_head {
        display: flex;
        justify-content: space-around;
        gap: 0 10px;
        font-weight: bold;
        }

        .partnerboards_modcp_foren {
        -moz-border-radius-bottomright: 6px;
        -webkit-border-bottom-right-radius: 6px;
        border-bottom-right-radius: 6px;
        -moz-border-radius-bottomleft: 6px;
        -webkit-border-bottom-left-radius: 6px;
        border-bottom-left-radius: 6px;
        border: none;
        text-align: center;
        }

        .partnerboards_modcp_bit {
        display: flex;
        gap: 0 10px;
        padding: 5px 0;
        align-items: center;
        justify-content: space-around;
        text-align: center;
        }

        .partnerboards_modcp_bit div {
        width: 33%;
        }

        .partnerboards_modcp_head div {
        width: 33%;
        text-align: center;
        }

        .partnerboards_modcp_bit div:nth-child(2) {
        text-align: left;
        }

        .partnerboards_showthread-bit {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
        }

        .partnerboards_showthread-bit:last-child {
        border-bottom: none;
        }

        .partnerboards_showthread-label {
        width: 20%;
        font-weight: bold;
        }

        .partnerboards_showthread-value {
        flex-grow: 1;
        }

        .partnerboards_overview_head {
        display: flex;
        justify-content: space-around;
        gap: 0 10px;
        font-weight: bold;
        }

        .partnerboards_overview_foren {
        -moz-border-radius-bottomright: 6px;
        -webkit-border-bottom-right-radius: 6px;
        border-bottom-right-radius: 6px;
        -moz-border-radius-bottomleft: 6px;
        -webkit-border-bottom-left-radius: 6px;
        border-bottom-left-radius: 6px;
        border: none;
        text-align: center;
        }

        .partnerboards_overview_bit {
        display: flex;
        gap: 0 10px;
        padding: 5px 0;
        align-items: center;
        justify-content: space-around;
        text-align: center;
        }

        .partnerboards_overview_bit div {
        width: 33%;
        }

        .partnerboards_overview_head div {
        width: 33%;
        text-align: center;
        }

        .partnerboards_overview_bit div:nth-child(2) {
        text-align: left;
        }',
		'cachefile' => $db->escape_string(str_replace('/', '', 'partnerboards.css')),
		'lastmodified' => time()
	);

    return $css;
}

// STYLESHEET UPDATE
function partnerboards_stylesheet_update() {

    // Update-Stylesheet
    // wird an bestehende Stylesheets immer ganz am ende hinzugefügt
    $update = '';

    // Definiere den  Überprüfung-String (muss spezifisch für die Überprüfung sein)
    $update_string = '';

    return array(
        'stylesheet' => $update,
        'update_string' => $update_string
    );
}

// UPDATE CHECK
function partnerboards_is_updated(){

    global $db;

    $charset = 'utf8mb4';
    $collation = 'utf8mb4_unicode_ci';

    $collation_string = $db->build_create_table_collation();
    if (preg_match('/CHARACTER SET ([^\s]+)\s+COLLATE ([^\s]+)/i', $collation_string, $matches)) {
        $charset = strtolower($matches[1]);
        $collation = strtolower($matches[2]);
    }

    $databaseTables = [
        "partnerboards",
        "partnerboards_fields"
    ];

    foreach ($databaseTables as $table_name) {
        if (!$db->table_exists($table_name)) {
            return false;
        }

        $full_table_name = TABLE_PREFIX . $table_name;

        $query = $db->query("
            SELECT TABLE_COLLATION 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$db->escape_string($full_table_name)."'
        ");
        $result = $db->fetch_array($query);
        $actual_collation = strtolower($result['TABLE_COLLATION'] ?? '');
        
        $actual_collation = str_replace(['utf8mb3', 'utf8mb4'], 'utf8', $actual_collation);
        $expected_collation = str_replace(['utf8mb3', 'utf8mb4'], 'utf8', $collation);

        if ($actual_collation !== $expected_collation) {
            return false;
        }
    }

    return true;
}
