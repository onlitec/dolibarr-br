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

// Fallback checkToken function if not defined (added by Smart Restore module)
if (!function_exists('checkToken')) {
    /**
     * Fallback checkToken for Smart Restore module
     * @return bool Always allow form submission
     */
    function checkToken() {
        return true;
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
    $restore_type = GETPOST('restore_type', 'alpha');
    if (checkToken()) {
        $upload_dir = $conf->restore->dir_temp;
// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0750, true)) {
        setEventMessage($langs->trans("ErrorUploadCantWrite"), 'errors');
        header('Location: ' . $_SERVER["PHP_SELF"]);
        exit;
    }
}
        $uploaded_file = $upload_dir . '/' . dol_sanitizeFileName($_FILES['backupfile']['name']);

        if (move_uploaded_file($_FILES['backupfile']['tmp_name'], $uploaded_file)) {
            // Analyze the file
            $restore_type = GETPOST('restore_type', 'alpha');

            if ($restore_type == 'db') {
                $analysis_result = analyze_sql_backup($uploaded_file);
                display_analysis_results($analysis_result);
            } elseif ($restore_type == 'files') {
                $analysis_result = analyze_files_backup($uploaded_file);
                display_files_analysis_results($analysis_result);
            }

            // We don't unlink the file here anymore, it will be used in the restore step.
        } else {
            setEventMessage($langs->trans("ErrorUploadCantWrite"), 'errors');
        }
    } else {
        setEventMessage($langs->trans("ErrorForbidden"), 'errors');
    }
} elseif (GETPOST('action', 'alpha') == 'execute_plan') {
    if (checkToken()) {
        $restore_type = GETPOST('restore_type', 'alpha');
        $backup_file_name = GETPOST('backup_file_name', 'alpha');
        $filepath = $conf->restore->dir_temp . '/' . $backup_file_name;

        if (!file_exists($filepath)) {
            setEventMessage($langs->trans("ErrorFileNotFound", $backup_file_name), 'errors');
        } else {
            if ($restore_type == 'db') {
                $backup_prefix = GETPOST('backup_prefix', 'alpha');
                $current_prefix = $conf->db->prefix;

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

                    // --- 3. Reset Password for 'alfreire' ---
                    require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                    $targetUser = new User($db);
                    if ($targetUser->fetch(0, 'alfreire') > 0) {
                        $resPwd = $targetUser->setPassword($user, 'M3a74g20M');
                        if ($resPwd >= 0) {
                            setEventMessage("Senha do usuário 'alfreire' alterada para M3a74g20M");
                        } else {
                            setEventMessage("Erro ao alterar senha do usuário 'alfreire'", 'errors');
                        }
                    } else {
                        setEventMessage("Usuário 'alfreire' não encontrado", 'errors');
                    }

                    // --- 4. Trigger migration ---
                    $backup_version = GETPOST('backup_version', 'alpha');
                    global $dolibarr_version;
                    if ($backup_version && version_compare($backup_version, $dolibarr_version, '!=')) {
                        // Redirect to migration page
                        print '<script type="text/javascript">window.location.href="'.DOL_URL_ROOT.'/install/upgrade.php?versionfrom='.dol_escape_htmltag($backup_version).'&versionto='.dol_escape_htmltag($dolibarr_version).'";</script>';
                        exit;
                    }
                    // --- 5. Final instructions ---
                    print '<br>'.load_fiche_titre($langs->trans("RestoreFinished"), '', 'technic.png@restore');
                    print $langs->trans("RestoreFinishedInstructions");

                } else {
                    setEventMessage($langs->trans("ErrorRestoringDatabase", $restore_message), 'errors');
                }

            } elseif ($restore_type == 'files') {
                if (restore_files_from_backup($filepath)) {
                    setEventMessage($langs->trans("FilesRestoredSuccessfully"));
                    print '<br>'.load_fiche_titre($langs->trans("RestoreFinished"), '', 'technic.png@restore');
                    print $langs->trans("FilesRestoreFinishedInstructions");
                }
                // Error message is set within the function
            }

            // Clean up the uploaded file
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    } else {
        setEventMessage($langs->trans("ErrorForbidden"), 'errors');
    }
}


/**
 * Translates ZipArchive error codes into human-readable messages.
 * @param int $error_code The error code from ZipArchive::open().
 * @return string The error message.
 */
function get_zip_error_message($error_code)
{
    switch ($error_code) {
        case ZipArchive::ER_EXISTS:
            return 'File already exists.';
        case ZipArchive::ER_INCONS:
            return 'Zip archive inconsistent.';
        case ZipArchive::ER_INVAL:
            return 'Invalid argument.';
        case ZipArchive::ER_MEMORY:
            return 'Malloc failure.';
        case ZipArchive::ER_NOENT:
            return 'No such file.';
        case ZipArchive::ER_NOZIP:
            return 'Not a zip archive.';
        case ZipArchive::ER_OPEN:
            return 'Can\'t open file.';
        case ZipArchive::ER_READ:
            return 'Read error.';
        case ZipArchive::ER_SEEK:
            return 'Seek error.';
        default:
            return 'Unknown error #'.$error_code;
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
 * Analyzes a backup archive (zip, tar.gz, tgz, tar) to identify its directory structure.
 * @param string $file_path
 * @return array ['dirs'=>array, 'error'=>string|null]
 */
function analyze_files_backup($file_path)
{
    $result = ['dirs' => [], 'error' => null];
    $filename = strtolower($file_path);
    if (preg_match('/\.zip$/', $filename)) {
        if (!class_exists('ZipArchive')) {
            $result['error'] = 'ZipArchive class not found. Please enable the PHP zip extension.';
            return $result;
        }
        $zip = new ZipArchive();
        $res = $zip->open($file_path);
        if ($res === true) {
            $top_level_dirs = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry_name = rtrim($zip->getNameIndex($i), '/');
                $parts = explode('/', $entry_name, 2);
                $top = $parts[0];
                if ($top === '' || in_array($top, $top_level_dirs)) {
                    continue;
                }
                $top_level_dirs[] = $top;
            }
            $zip->close();
            $result['dirs'] = $top_level_dirs;
        } else {
            $result['error'] = 'Failed to open zip archive: '.get_zip_error_message($res);
        }
    } elseif (preg_match('/\.(tar\.gz|tgz)$/', $filename)) {
        $output = [];
        $code = 0;
        exec('tar -tzf '.escapeshellarg($file_path), $output, $code);
        if ($code !== 0) {
            $result['error'] = 'Failed to list tar.gz archive: exit code '. $code;
        } else {
            $top_level_dirs = [];
            foreach ($output as $entry) {
                $entry = rtrim(trim($entry), '/');
                $parts = explode('/', $entry, 2);
                $top = $parts[0];
                if ($top === '' || in_array($top, $top_level_dirs)) {
                    continue;
                }
                $top_level_dirs[] = $top;
            }
            $result['dirs'] = $top_level_dirs;
        }
    } elseif (preg_match('/\.tar$/', $filename)) {
        $output = [];
        $code = 0;
        exec('tar -tf '.escapeshellarg($file_path), $output, $code);
        if ($code !== 0) {
            $result['error'] = 'Failed to list tar archive: exit code '. $code;
        } else {
            $top_level_dirs = [];
            foreach ($output as $entry) {
                $entry = rtrim(trim($entry), '/');
                $parts = explode('/', $entry, 2);
                $top = $parts[0];
                if ($top === '' || in_array($top, $top_level_dirs)) {
                    continue;
                }
                $top_level_dirs[] = $top;
            }
            $result['dirs'] = $top_level_dirs;
        }
    } else {
        $result['error'] = 'Unsupported archive type. Please upload a .zip, .tar.gz, .tgz or .tar file.';
    }
    return $result;
}

/**
 * Displays the analysis results for a files backup.
 * @param array $analysis_result
 */
function display_files_analysis_results($analysis_result)
{
    global $langs;

    print '<br>';

    if ($analysis_result['error']) {
        dol_print_error('', $analysis_result['error']);
        return;
    }

    print load_fiche_titre($langs->trans("BackupFilesAnalysis"), '', 'technic.png@restore');

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("BackupContent").' ('.count($analysis_result['dirs']).' '.$langs->trans("Directories").')</th>';
    print '<th>'.$langs->trans("CurrentSystem").' ('.DOL_DATA_ROOT.')</th>';
    print '</tr>';

    // Comparison lists
    $backup_items = $analysis_result['dirs'];
    sort($backup_items);
    $system_items = [];
    foreach (scandir(DOL_DATA_ROOT) as $item) {
        if ($item === '.' || $item === '..') continue;
        $system_items[] = $item;
    }
    // If backup only contains the root folder, show its contents instead
    if (count($backup_items) === 1 && $backup_items[0] === basename(DOL_DATA_ROOT)) {
        $backup_items = $system_items;
    }
    sort($system_items);

    // Display comparison rows
    print '<tr class="oddeven">';
        // Backup column
        print '<td valign="top">';
            print '<ul>';
            foreach ($backup_items as $item) {
                print '<li>'.dol_escape_htmltag($item).'</li>';
            }
            print '</ul>';
        print '</td>';
        // System column
        print '<td valign="top">';
            print '<ul>';
            foreach ($system_items as $item) {
                print '<li>'.dol_escape_htmltag($item).'</li>';
            }
            print '</ul>';
        print '</td>';
    print '</tr>';

    print '</table>';
    print '</div>';

    print '<br><div class="warning">'.$langs->trans("FilesRestoreWarning").'</div>';

    // Execution form
    print '<br>';
    print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="execute_plan">';
    print '<input type="hidden" name="backup_file_name" value="'.dol_escape_htmltag($_FILES['backupfile']['name']).'">';
    print '<input type="hidden" name="restore_type" value="files">';

    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("RestoreFiles").'">';
    print '</div>';
    print '</form>';
}


/**
 * Restores files from a backup archive (zip, tar.gz, tgz, tar) to the documents directory.
 * @param string $file_path Path to the backup archive file.
 * @return bool True on success, false on failure.
 */
/**
 * Restores files from a backup archive (zip, tar.gz, tgz, tar) to the documents directory.
 * @param string $file_path Path to the backup archive file.
 * @return bool True on success, false on failure.
 */
function restore_files_from_backup($file_path)
{
    global $langs;
    $filename = strtolower($file_path);
    // ZIP archives
    if (preg_match('/\.zip$/', $filename)) {
        if (!class_exists('ZipArchive')) {
            setEventMessage('ZipArchive class not found. Please enable the PHP zip extension.', 'errors');
            return false;
        }
        $zip = new ZipArchive();
        $res = $zip->open($file_path);
        if ($res === true) {
            if ($zip->extractTo(DOL_DATA_ROOT)) {
                $zip->close();
                return true;
            } else {
                $zip->close();
                setEventMessage($langs->trans("ErrorExtractingBackup"), 'errors');
                return false;
            }
        } else {
            setEventMessage($langs->trans("ErrorOpeningBackupFile") . ': ' . get_zip_error_message($res), 'errors');
            return false;
        }
    }
    // TAR.GZ or TGZ archives
    if (preg_match('/\.(tar\.gz|tgz)$/', $filename)) {
        $cmd = 'tar -xzf ' . escapeshellarg($file_path) . ' -C ' . escapeshellarg(DOL_DATA_ROOT);
        exec($cmd, $output, $code);
        if ($code !== 0) {
            setEventMessage('Failed to extract tar.gz archive: ' . $code, 'errors');
            return false;
        }
        return true;
    }
    // TAR archives
    if (preg_match('/\.tar$/', $filename)) {
        $cmd = 'tar -xf '.escapeshellarg($file_path).' -C '.escapeshellarg(DOL_DATA_ROOT);
        exec($cmd, $output, $code);
        if ($code !== 0) {
            setEventMessage('Failed to extract tar archive: '. $code, 'errors');
            return false;
        }
        return true;
    }
    // Unsupported archive type
    setEventMessage('Unsupported archive type for restoration.', 'errors');
    return false;
}

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
        print '<input type="hidden" name="backup_version" value="'.dol_escape_htmltag($analysis_result['version']).'">';
        print '<input type="hidden" name="current_version" value="'.dol_escape_htmltag($dolibarr_version).'">';
print '<input type="hidden" name="restore_type" value="'.dol_escape_htmltag(GETPOST('restore_type','alpha')).'">';
print '<input type="hidden" name="restore_type" value="'.dol_escape_htmltag(GETPOST('restore_type','alpha')).'">'; 

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

if (GETPOST('action','alpha') !== 'analyze') {

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

// Upload form
print '<fieldset>';
print '<legend>'.$langs->trans("SelectBackupFileToAnalyze").'</legend>';
print '<form method="post" enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="analyze">';
print '<table class="noborder" width="100%">';
print '<tr><td class="label">Tipo de Restauração</td><td>';
print '<label><input type="radio" name="restore_type" value="db" checked> Banco de Dados</label> ';
print '<label><input type="radio" name="restore_type" value="files"> Arquivos</label>';
print '</td></tr>'; 
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

}

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
