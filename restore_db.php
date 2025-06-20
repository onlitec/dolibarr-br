<?php
// Add CSRF protection
if (! defined('CSRFCHECK_WITH_TOKEN')) {
    define('CSRFCHECK_WITH_TOKEN', 1);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/restore/lib/restore.lib.php'; // Include the new library

// Permissions and globals
if (empty($user->admin)) accessforbidden();
global $conf, $db, $langs, $user;
global $dolibarr_main_db_name, $dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_port;

// Translations
$langs->load("restore@restore");
$langs->load("admin");

// Action handler
if ($_POST['action'] == 'restore') {
    if (checkToken()) {
        $upload_dir = $conf->restore->dir_temp;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0750, true);
        }

        $uploaded_file = $upload_dir . '/' . dol_sanitizeFileName($_FILES['backupfile']['name']);

        if (move_uploaded_file($_FILES['backupfile']['tmp_name'], $uploaded_file)) {
            $clean_first = GETPOST('clean_first', 'int');
            
            // Call the library function
            $error_msg = dol_restore_db(
                $uploaded_file,
                'mysql',
                $conf->db->name,
                $conf->db->host,
                $conf->db->user,
                $conf->db->pass,
                $conf->db->port,
                $clean_first
            );

            if (empty($error_msg)) {
                setEventMessage($langs->trans("DatabaseRestoredSuccessfully"));
            } else {
                setEventMessage($langs->trans("ErrorRestoringDatabase") . ': ' . dol_escape_htmltag($error_msg), 'errors');
            }
            unlink($uploaded_file); // Clean up
        } else {
            setEventMessage($langs->trans("ErrorUploadCantWrite"), 'errors');
        }
    } else {
        setEventMessage($langs->trans("ErrorForbidden"), 'errors');
    }
}

// Page header
llxHeader("", $langs->trans("RestoreDB"), '', '', 0, 0, '', '', '', 'mod-restore page-restore_db');
print load_fiche_titre($langs->trans("RestoreDB"), '', 'restore@restore');

// --- Start of page content ---

print '<div class="fichecenter"><div class="fichehalfleft">';

// Form
print '<fieldset>';
print '<legend>'.$langs->trans("SelectBackupFile").'</legend>';
print '<form method="post" enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="restore">';
print '<table class="noborder" width="100%">';
print '<tr><td class="label">'.$langs->trans('SelectFileToUpload').'</td><td><input type="file" name="backupfile" class="flat"></td></tr>';
print '<tr><td colspan="2" class="opacitymedium">'.$langs->trans("MaxUploadSize", ini_get('upload_max_filesize'), ini_get('post_max_size')).'</td></tr>';
print '<tr><td class="label">'.$langs->trans("CleanDatabaseFirst").'</td><td><input type="checkbox" name="clean_first" value="1"></td></tr>';
print '<tr><td colspan="2" class="opacitymedium">'.$langs->trans("CleanDatabaseFirstDesc").'</td></tr>';
print '</table>';
print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('ExecuteRestore').'" /></div>';
print '</form>';
print '</fieldset>';


print '</div></div>';

// --- End of page content ---

llxFooter();
$db->close(); 