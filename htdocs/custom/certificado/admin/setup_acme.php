<?php
// Página de configuração do módulo Certificado SSL (ACME)
require __DIR__ . '/../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once __DIR__ . '/../class/actions_certificado.class.php';
$langs->load('certificado@certificado');
if (!$user->admin) accessforbidden();

session_start();

// Tratar ações ACME
$action = GETPOST('action', 'alpha');
if ($action === 'install_acme') {
    $act = new ActionsCertificado($db);
    $out = array();
    $success = $act->installAcmeSh($out);
    $_SESSION['acme_feedback'] = $act->getFormattedAcmeOutput($out, $success);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?token=' . newToken());
    exit;
}
if ($action === 'issue_cert') {
    $act = new ActionsCertificado($db);
    $out = array();
    $domain = preg_replace('#^https?://#', '', rtrim((!empty($conf->global->CERTIFICADO_BASE_URL) ? $conf->global->CERTIFICADO_BASE_URL : ''), '/'));
    if (empty($domain)) {
        $_SESSION['acme_feedback'] = ['type' => 'errors', 'message' => 'A URL base do Dolibarr precisa ser configurada e salva antes de emitir um certificado.'];
        header('Location: ' . $_SERVER['PHP_SELF'] . '?token=' . newToken());
        exit;
    }
    $success = $act->issueCertificate($domain, $out);
    $_SESSION['acme_feedback'] = $act->getFormattedAcmeOutput($out, $success);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?token=' . newToken());
    exit;
}

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_REQUEST['action'])) {
    // Diretório para certificados customizados
    $certDir = DOL_DATA_ROOT . '/certificado';
    if (!is_dir($certDir)) {
        dol_mkdir($certDir);
    }

    // Tratar upload do arquivo .crt
    if (!empty($_FILES['custom_crt_file']['name'])) {
        $crt_target_file = $certDir . '/' . basename($_FILES['custom_crt_file']['name']);
        if (dol_move_uploaded_file($_FILES['custom_crt_file']['tmp_name'], $crt_target_file, 1)) {
            dolibarr_set_const($db, 'CERTIFICADO_CUSTOM_CRT', $crt_target_file, 'chaine', 0, '', $conf->entity);
        } else {
            setEventMessage('Falha ao fazer upload do arquivo .crt.', 'errors');
        }
    }

    // Tratar upload do arquivo .key
    if (!empty($_FILES['custom_key_file']['name'])) {
        $key_target_file = $certDir . '/' . basename($_FILES['custom_key_file']['name']);
        if (dol_move_uploaded_file($_FILES['custom_key_file']['tmp_name'], $key_target_file, 1)) {
            dolibarr_set_const($db, 'CERTIFICADO_CUSTOM_KEY', $key_target_file, 'chaine', 0, '', $conf->entity);
        } else {
            setEventMessage('Falha ao fazer upload do arquivo .key.', 'errors');
        }
    }

    // Base URL
    dolibarr_set_const($db, 'CERTIFICADO_BASE_URL', GETPOST('base_url', 'alpha'), 'chaine', 0, '', $conf->entity);
    // Forçar HTTPS
    dolibarr_set_const($db, 'CERTIFICADO_FORCE_HTTPS', GETPOST('force_https', 'int'), 'yesno', 0, '', $conf->entity);
    // Tipo de certificado
    dolibarr_set_const($db, 'CERTIFICADO_CERT_TYPE', GETPOST('cert_type', 'alpha'), 'chaine', 0, '', $conf->entity);
    // E-mail Let's Encrypt
    dolibarr_set_const($db, 'CERTIFICADO_LE_EMAIL', GETPOST('le_email', 'email'), 'chaine', 0, '', $conf->entity);
    
    setEventMessage('Configurações salvas com sucesso');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?token=' . newToken());
    exit;
}

// Exibir interface de configuração
llxHeader('', 'Certificado SSL');
print load_fiche_titre('Configuração de Certificado SSL');

// Debug: Verificar valor de CERTIFICADO_CERT_TYPE
dol_syslog("Valor de CERTIFICADO_CERT_TYPE: " . print_r($conf->global->CERTIFICADO_CERT_TYPE, true), LOG_DEBUG);

$form = new Form($db);
$toselect = array(
    'letsencrypt' => "Let's Encrypt",
    'custom'      => 'Próprio'
);

print '<form method="post" enctype="multipart/form-data">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>Parâmetro</td><td>Valor</td></tr>';
print '<tr><td>URL base</td><td><input name="base_url" value="' . (!empty($conf->global->CERTIFICADO_BASE_URL) ? $conf->global->CERTIFICADO_BASE_URL : '') . '" size="60"></td></tr>';
print '<tr><td>Forçar HTTPS</td><td>' . $form->selectyesno('force_https', isset($conf->global->CERTIFICADO_FORCE_HTTPS) ? $conf->global->CERTIFICADO_FORCE_HTTPS : 1) . '</td></tr>';
print '<tr><td>Tipo de certificado</td><td>' . $form->selectarray('cert_type', $toselect, (!empty($conf->global->CERTIFICADO_CERT_TYPE) ? $conf->global->CERTIFICADO_CERT_TYPE : 'letsencrypt')) . '</td></tr>';

print '<tr class="liste_titre"><td colspan="2">Configurações para Let\'s Encrypt</td></tr>';
print '<tr><td>E-mail Let\'s Encrypt</td><td><input name="le_email" value="' . (!empty($conf->global->CERTIFICADO_LE_EMAIL) ? $conf->global->CERTIFICADO_LE_EMAIL : '') . '" size="40"></td></tr>';

print '<tr class="liste_titre"><td colspan="2">Configurações para Certificado Próprio</td></tr>';
print '<tr><td>Fazer upload de certificado .crt</td><td><input type="file" name="custom_crt_file" class="flat"></td></tr>';
if (!empty($conf->global->CERTIFICADO_CUSTOM_CRT)) {
    print '<tr><td>Caminho atual do .crt</td><td>' . htmlspecialchars($conf->global->CERTIFICADO_CUSTOM_CRT, ENT_QUOTES, 'UTF-8') . '</td></tr>';
}
print '<tr><td>Fazer upload de chave .key</td><td><input type="file" name="custom_key_file" class="flat"></td></tr>';
if (!empty($conf->global->CERTIFICADO_CUSTOM_KEY)) {
    print '<tr><td>Caminho atual da .key</td><td>' . htmlspecialchars($conf->global->CERTIFICADO_CUSTOM_KEY, ENT_QUOTES, 'UTF-8') . '</td></tr>';
}

print '</table>';
print '<div class="tabsAction"><input type="submit" class="button" value="Salvar"></div>';

// Botões de ação somente para ACME
print '<div class="tabsAction"><input type="button" class="button" value="Instalar Cliente ACME" onclick="window.location.href=\'?action=install_acme&token=' . newToken() . '\'" ></div>';
print '<div class="tabsAction"><input type="button" class="button" value="Emitir Certificado" onclick="window.location.href=\'?action=issue_cert&token=' . newToken() . '\'" ></div>';

print '</form>';

// Exibir feedback da última ação
if (!empty($_SESSION['acme_feedback'])) {
    $feedback = $_SESSION['acme_feedback'];
    $box_class = ($feedback['type'] === 'errors') ? 'error' : 'ok';
    print '<div class="box ' . $box_class . '">';
    print $feedback['message'];
    print '</div>';
    unset($_SESSION['acme_feedback']);
}

llxFooter();
