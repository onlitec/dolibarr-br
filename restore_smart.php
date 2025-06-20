<?php
// Load Dolibarr environment for authentication
require '../../main.inc.php';

// Now, ensure all necessary libraries are loaded, even if main.inc.php had issues.
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/restore/lib/restore.lib.php';

// Fallback info_box function if not defined (added by Smart Restore module)
if (!function_exists('info_box')) {
    /**
     * Fallback info_box function
     */
    function info_box($title = '', $content = '', $statut = '', $more = 0, $short = 0) {
        global $langs;
        $html = '<div class="info-box">';
        if (!empty($title)) {
            $html .= '<div class="info-box-title">'.dol_escape_htmltag($title).'</div>';
        }
        $html .= '<div class="info-box-content">'.dol_escape_htmltag($content).'</div>';
        $html .= '</div>';
        return $html;
    }
}

// Permissions and globals are loaded by main.inc.php
if (empty($user->admin)) {
	accessforbidden();
}
// global $conf, $db, $langs, $user are loaded by master.inc.php

// Translations
$langs->load("restore@restore");
$langs->load("admin");

// Page header
llxHeader("", $langs->trans("SmartRestore"), '', '', 0, 0, '', '', '', 'mod-restore page-restore_smart');
print load_fiche_titre($langs->trans("SmartRestore"), '', 'restore@restore');

// --- Action handlers ---
if (GETPOST('action', 'alpha') == 'analyze' && ! empty($_FILES['backupfile'])) {
    if (checkToken()) {
        $upload_dir = $conf->restore->dir_temp;
        $uploaded_file = $upload_dir . '/' . dol_sanitizeFileName($_FILES['backupfile']['name']);

        if (move_uploaded_file($_FILES['backupfile']['tmp_name'], $uploaded_file)) {
            // Analyze the file
            $analysis_result = analyze_sql_backup($uploaded_file);

            // Display results
            display_analysis_results($analysis_result);

            // Clean up
            unlink($uploaded_file);
        } else {
            setEventMessage($langs->trans("ErrorUploadCantWrite"), 'errors');
        }
    } else {
        setEventMessage($langs->trans("ErrorForbidden"), 'errors');
    }
} elseif (GETPOST('action', 'alpha') == 'execute_plan') {
    if (checkToken()) {
        $backup_file_name = GETPOST('backup_file_name', 'alpha');
        $backup_prefix = GETPOST('backup_prefix', 'alpha');
        $current_prefix = $conf->db->prefix;

        $filepath = $conf->restore->dir_temp . '/' . $backup_file_name;

        // Check if file still exists
        if (!file_exists($filepath)) {
            setEventMessage($langs->trans("ErrorFileNotFound", $backup_file_name), 'errors');
            Header('Location: '.$_SERVER["PHP_SELF"]);
            exit;
        }

        // --- 1. Restore Database ---
        $restore_message = dol_restore_db($filepath, 'mysql', $conf->db->name, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->port, 1);
        if (empty($restore_message)) {
        	setEventMessage($langs->trans("DatabaseRestoredSuccessfully"));

        	// --- 2. Fix conf.php if needed ---
	        if ($backup_prefix != $current_prefix) {
	            $conf_file = DOL_DOCUMENT_ROOT . '/conf/conf.php';
	            if (update_conf_file_prefix($conf_file, $backup_prefix)) {
	                setEventMessage($langs->trans("ConfFileUpdatedSuccessfully", $backup_prefix));
	            } else {
	                setEventMessage($langs->trans("ErrorUpdatingConfFile"), 'errors');
	            }
	        }

	        // --- 3. Final instructions ---
	        $link_to_home = '<a href="'.DOL_URL_ROOT.'/">'.$langs->trans("HomePage").'</a>';
	        $final_message = $langs->trans("SmartRestoreFinished", $link_to_home);
	        dol_htmloutput_mesg($langs->trans("ActionPlanExecuted"), $final_message, 'info');

        } else {
        	setEventMessage($langs->trans("ErrorRestoringDatabase"). ': ' . $restore_message, 'errors');
        }

        // Clean up
        unlink($filepath);

    } else {
        setEventMessage($langs->trans("ErrorForbidden"), 'errors');
    }
}


/**
 * Analyze SQL Backup file to find prefix and version
 * @param  string $filepath Path to the .sql.gz or .sql file
 * @return array            ['prefix' => found_prefix, 'version' => found_version]
 */
function analyze_sql_backup($filepath)
{
	global $langs;
    $result = ['prefix' => 'Não encontrado', 'version' => 'Não encontrada', 'error' => null];
    $file_content = '';

    try {
        // Handle both .gz and plain .sql files
        $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
        if ($file_extension == 'gz') {
            $zd = gzopen($filepath, 'r');
            if (!$zd) {
                throw new Exception("Falha ao abrir o arquivo GZ.");
            }
            while (!gzeof($zd)) {
                $file_content .= gzread($zd, 1024*512); // Read in chunks
                 if (strlen($file_content) > 5 * 1024 * 1024) break; // Safety break for large files
            }
            gzclose($zd);
        } else {
            $file_content = file_get_contents($filepath);
        }

        if (empty($file_content)) {
        	throw new Exception("O arquivo de backup está vazio ou não pôde ser lido.");
        }

        // 1. Find table prefix
        if (preg_match('/CREATE TABLE `([^`]+)_/', $file_content, $matches)) {
            $result['prefix'] = $matches[1] . '_';
        }

        // 2. Find Dolibarr version
        if (preg_match("/INSERT INTO `(.*)const` VALUES \('MAIN_VERSION_LAST_INSTALL','([^']+)'.*\)/", $file_content, $matches)) {
            $result['version'] = $matches[2];
        }

    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }


    return $result;
}

/**
 * Display analysis results in a comparative table.
 * @param array $analysis_result
 */
function display_analysis_results($analysis_result)
{
    global $conf, $langs, $dolibarr_version;
    print '<br>';

    if ($analysis_result['error']) {
    	dol_print_error('', $analysis_result['error']);
    	return;
    }

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Parameter").'</th>';
    print '<th>'.$langs->trans("ValueInBackup").'</th>';
    print '<th>'.$langs->trans("CurrentValue").'</th>';
    print '<th>'.$langs->trans("Status").'</th>';
    print '</tr>';

    // Prefix row
    $is_prefix_ok = ($analysis_result['prefix'] == $conf->db->prefix);
    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("TablePrefix").'</td>';
    print '<td>'.dol_escape_htmltag($analysis_result['prefix']).'</td>';
    print '<td>'.$conf->db->prefix.'</td>';
    print '<td>'.($is_prefix_ok ? dol_htmloutput_mesg('', '', 'ok', 0, 1) : dol_htmloutput_mesg('', '', 'error', 0, 1)).'</td>';
    print '</tr>';

    // Version row
    $is_version_ok = version_compare($analysis_result['version'], $dolibarr_version, '==');
    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("Version").'</td>';
    print '<td>'.dol_escape_htmltag($analysis_result['version']).'</td>';
    print '<td>'.$dolibarr_version.'</td>';
    print '<td>'.($is_version_ok ? dol_htmloutput_mesg('', '', 'ok', 0, 1) : dol_htmloutput_mesg('', '', 'warning', 0, 1)).'</td>';
    print '</tr>';

    print '</table>';
    print '</div>';

    // --- Action Plan ---
    $actions_to_take = [];
    if (!$is_prefix_ok) {
        $actions_to_take['fix_prefix'] = $langs->trans("ActionFixPrefix", $analysis_result['prefix'], $conf->db->prefix);
    }
    if (!$is_version_ok) {
        $actions_to_take['run_migration'] = $langs->trans("ActionRunMigration", $analysis_result['version'], $dolibarr_version);
    }


    if (!empty($actions_to_take)) {
        print '<br>'.load_fiche_titre($langs->trans("RecommendedActionPlan"), '', 'technic.png@restore');

        print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><th>'.$langs->trans("Action").'</th></tr>';

		$i = 0;
		foreach ($actions_to_take as $action_key => $action_desc) {
			print '<tr class="oddeven"><td>'.($i+1).'. '.$action_desc.'</td></tr>';
			$i++;
		}
		print '</table>';
		print '</div>';

		// Execution form
		print '<br>';
		print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="execute_plan">';
        print '<input type="hidden" name="backup_file_name" value="'.dol_escape_htmltag($_FILES['backupfile']['name']).'">';
        print '<input type="hidden" name="backup_prefix" value="'.dol_escape_htmltag($analysis_result['prefix']).'">';

        print '<div class="center">';
        print '<input type="submit" class="button" value="'.$langs->trans("ExecuteActionPlan").'">';
        print '</div>';
        print '</form>';


    } else {
    	print '<br>';
    	dol_htmloutput_mesg($langs->trans("BackupLooksGood"), '', 'ok', 0, 0, 'center');
    }
}

/**
 * Update the table prefix in conf.php file.
 *
 * @param string $conf_file_path Path to conf.php
 * @param string $new_prefix     The new prefix to set
 * @return bool                  True on success, false on failure
 */
function update_conf_file_prefix($conf_file_path, $new_prefix)
{
    if (!is_writable($conf_file_path)) {
        return false;
    }

    $conf_content = file_get_contents($conf_file_path);
    if ($conf_content === false) {
        return false;
    }

    // Use regex to replace the prefix value, keeping the rest of the line intact.
    $new_conf_content = preg_replace(
        "/\\\$dolibarr_main_db_prefix\s*=\s*'.*';/",
        "\\\$dolibarr_main_db_prefix = '".$new_prefix."';",
        $conf_content
    );

    if ($new_conf_content === null || $new_conf_content === $conf_content) {
        return false; // Error or no change
    }

    if (file_put_contents($conf_file_path, $new_conf_content)) {
        // Try to invalidate opcache to make sure the change is loaded
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($conf_file_path, true);
        }
        return true;
    }

    return false;
}

// --- Start of page content ---

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

// Upload form
print '<fieldset>';
print '<legend>'.$langs->trans("SelectBackupFileToAnalyze").'</legend>';
print '<form method="post" enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="analyze">';
print '<table class="noborder" width="100%">';
print '<tr><td class="label">'.$langs->trans('SelectFileToUpload').'</td><td><input type="file" name="backupfile" class="flat"></td></tr>';
print '<tr><td colspan="2" class="opacitymedium">'.$langs->trans("MaxUploadSize", ini_get('upload_max_filesize'), ini_get('post_max_size')).'</td></tr>';
print '</table>';
print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Analyze').'" /></div>';
print '</form>';
print '</fieldset>';


print '</div>';
print '<div class="fichehalfright">';

// Explanation box
// Strip HTML tags from info box content
$info_content = strip_tags($langs->trans("SmartRestore_InfoBoxContent"));
print info_box($langs->trans("SmartRestore_InfoBoxTitle"), $info_content);

print '</div>';
print '</div>';

// --- End of page content ---

llxFooter();
$db->close(); 
// Fallback info_box function if not defined
if (!function_exists('info_box')) {
    /**
     * Fallback info_box function for Smart Restore module
     *
     * @param string $title
     * @param string $content
     * @param string $statut
     * @param int $more
     * @param int $short
     * @return string HTML info box
     */
    function info_box($title = '', $content = '', $statut = '', $more = 0, $short = 0) {
        global $langs;
        $html = '<div class="info-box">';
        if (!empty($title)) {
            $html .= '<div class="info-box-title">'.dol_escape_htmltag($title).'</div>';
        }
        $html .= '<div class="info-box-content">'.dol_escape_htmltag($content).'</div>';
        $html .= '</div>';
        return $html;
    }
}
