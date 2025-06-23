<?php
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 * Restores a database from a dump file.
 *
 * @param string $filepath       Path to the SQL dump file (.sql or .sql.gz)
 * @param string $db_type        Database type (e.g., 'mysql')
 * @param string $db_name        Database name
 * @param string $db_host        Database host
 * @param string $db_user        Database user
 * @param string $db_pass        Database password
 * @param string $db_port        Database port
 * @param int    $clean_first    1 to drop all tables before restoring, 0 otherwise
 * @return string                Empty string on success, error message on failure
 */
function dol_restore_db($filepath, $db_type, $db_name, $db_host, $db_user, $db_pass, $db_port, $clean_first = 0)
{
    global $conf, $langs, $db;

    // --- Clean database if requested ---
    if ($clean_first) {
        $tables_to_drop = array();
        $result = $db->query('SHOW TABLES');
        if ($result) {
            while ($row = $db->fetch_row($result)) {
                $tables_to_drop[] = $row[0];
            }
        } else {
            return $langs->trans("ErrorFailedToListTables");
        }

        if (!empty($tables_to_drop)) {
            $db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables_to_drop as $table) {
                if ($db->query('DROP TABLE `'.$db->escape($table).'`') === false) {
                     return $langs->trans("ErrorFailedToDropTable", $table);
                }
            }
            $db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    // --- Restore database ---
    $restore_cmd = get_mysql_restore_command($filepath, $db_name, $db_host, $db_user, $db_pass, $db_port);

    if (empty($restore_cmd)) {
        return "Failed to generate restore command.";
    }

    $output = array();
    $return_var = 0;
    exec($restore_cmd, $output, $return_var);

    if ($return_var === 0) {
        return ''; // Success
    } else {
        return "Return code: ".$return_var."\nOutput: ".implode("\n", $output);
    }
}

/**
 * Generates the appropriate mysql/mariadb command for restoring a database.
 *
 * @param string $filepath   Path to the SQL dump file
 * @param string $db_name    Database name
 * @param string $db_host    Database host
 * @param string $db_user    Database user
 * @param string $db_pass    Database password
 * @param string $db_port    Database port
 * @return string            The command to execute
 */
function get_mysql_restore_command($filepath, $db_name, $db_host, $db_user, $db_pass, $db_port)
{
    $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
    $cat_cmd = ($file_extension == 'gz') ? 'gzip -d -c' : 'cat';

    // Build the command
    $cmd = $cat_cmd . ' ' . escapeshellarg($filepath);
    $cmd .= ' | mysql';
    $cmd .= ' --host=' . escapeshellarg($db_host);
    $cmd .= ' --user=' . escapeshellarg($db_user);
    if ($db_pass) {
        $cmd .= ' --password=' . escapeshellarg($db_pass);
    }
    $cmd .= ' --port=' . escapeshellarg($db_port);
    $cmd .= ' ' . escapeshellarg($db_name);

    return $cmd;
} 