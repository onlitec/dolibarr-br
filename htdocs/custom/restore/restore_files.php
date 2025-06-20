<?php
// Add CSRF protection
if (! defined('CSRFCHECK_WITH_TOKEN')) {
    define('CSRFCHECK_WITH_TOKEN', 1);
}
// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';

// Permissão de administrador
if (empty($user->admin)) accessforbidden();

// Traduções
$langs->load("restore@restore");

// Handle upload action
if (isset($_FILES['backupfile']['name']) && $_FILES['backupfile']['name']) {
    // CSRF check is handled automatically by main.inc.php when CSRFCHECK_WITH_TOKEN is defined
    $upload_dir = $conf->admin->dir_output . '/documents/';

    // Check for PHP upload errors
    if ($_FILES['backupfile']['error'] !== UPLOAD_ERR_OK) {
        $php_upload_errors = array(
            UPLOAD_ERR_INI_SIZE   => $langs->trans("ErrorUploadIniSize"),
            UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
            UPLOAD_ERR_PARTIAL    => $langs->trans("ErrorUploadPartial"),
            UPLOAD_ERR_NO_FILE    => $langs->trans("ErrorUploadNoFile"),
            UPLOAD_ERR_NO_TMP_DIR => $langs->trans("ErrorUploadNoTmpDir"),
            UPLOAD_ERR_CANT_WRITE => $langs->trans("ErrorUploadCantWrite"),
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
        );
        $error_message = $php_upload_errors[$_FILES['backupfile']['error']] ?? "Unknown upload error.";
        setEventMessages($langs->trans("FileUploadFailed"). ': ' . $error_message, null, 'errors');
    } else {
        // Create directory if it does not exist
        dol_mkdir($upload_dir);

        if (!is_writable($upload_dir)) {
            setEventMessages($langs->trans("ErrorDirNotWritable", $upload_dir), null, 'errors');
        } else {
            $new_file_name = dol_sanitizeFileName($_FILES['backupfile']['name']);
            $new_file_path = $upload_dir . $new_file_name;
            if (move_uploaded_file($_FILES['backupfile']['tmp_name'], $new_file_path)) {
                setEventMessages($langs->trans("FileUploadSuccess", $new_file_name), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("FileUploadFailed"), null, 'errors');
            }
        }
    }
}

// Handle restore files action
$action = GETPOST('action', 'alpha');
if ($action === 'do_restore_files') {
    $fileSel = GETPOST('file', 'alpha');
    $filepath = $conf->admin->dir_output.'/documents/'.dol_sanitizeFileName($fileSel);
    if (!is_file($filepath)) {
        setEventMessages($langs->trans('ErrorFileNotFound', $fileSel), null, 'errors');
    } else {
        // Determine extraction command
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        switch (strtolower($ext)) {
            case 'zip':
                // For zip, we extract into a temp dir and move contents up, as --strip-components is not standard
                $tmpdir = $conf->admin->dir_temp.'/restore_'.md5(uniqid(rand(), true));
                dol_mkdir($tmpdir);
                $cmd = 'unzip -o '.escapeshellarg($filepath).' -d '.escapeshellarg($tmpdir).'; ';
                // Move contents from the (likely) single subdirectory up to the real documents root
                $cmd .= 'rsync -a "'.rtrim($tmpdir, '/').'/"*/* "'.rtrim(DOL_DATA_ROOT, '/').'/" && rm -rf '.escapeshellarg($tmpdir);
                break;
            default:
                // tar, tgz, gz, bz2, zst - use --strip-components=1 to remove the top-level directory
                $cmd = 'tar --strip-components=1 -xf '.escapeshellarg($filepath).' -C '.escapeshellarg(DOL_DATA_ROOT);
        }
        // Execute
        $utils = new Utils($db);
        $outputfile = $conf->admin->dir_temp.'/restore_files_'.getmypid().'.log';
        $res = $utils->executeCLI($cmd, $outputfile, 0, null, 1);
        if (empty($res['error'])) {
            setEventMessages($langs->trans('RestoreFilesSucceeded'), null, 'mesgs');
        } else {
            setEventMessages($langs->trans('RestoreFilesFailed'), $outputfile, 'errors');
        }
    }
}

// Page header
llxHeader("", $langs->trans("RestoreFiles"), '', '', 0, 0, '', '', '', 'mod-restore page-restore_files');
print load_fiche_titre($langs->trans("RestoreFiles"), '', 'restore@restore');

// Upload form
print '<br>';
print '<fieldset>';
print '<legend>'.$langs->trans("UploadBackupFile").'</legend>';
print '<form method="post" enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="noborder">';
print '<tr><td class="label">'.$langs->trans('SelectFileToUpload').'</td><td><input type="file" name="backupfile"></td></tr>';
print '<tr><td colspan="2" class="opacitymedium">'.$langs->trans("MaxUploadSize", ini_get('upload_max_filesize'), ini_get('post_max_size')).'</td></tr>';
print '</table>';
print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Upload').'" /></div>';
print '</form>';
print '</fieldset>';

print '<br>';
print '<fieldset>';
print '<legend>'.$langs->trans("RestoreFromBackup").'</legend>';

// List backup files
$fileArray = dol_dir_list($conf->admin->dir_output.'/documents', 'files', 0, '\\.(zip|tar|tgz|gz|bz2|zst)$', '', 'date', SORT_DESC, 0, '\n', 0, '', '', 0, 0, 1);

// Form
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="do_restore_files">';
print '<table class="noborder">';
print '<tr><td class="label">'.$langs->trans('SelectBackupFile').'</td><td><select name="file">';
foreach ($fileArray as $f) {
    print '<option value="'.dol_escape_htmltag($f['name']).'">'.dol_escape_htmltag($f['name']).' ('.dol_print_size($f['size']).')</option>';
}
print '</select></td></tr>';
print '</table>';
print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Restore').'" /></div>';
print '</form>';
print '</fieldset>';

// Rodapé da página
llxFooter();
$db->close(); 