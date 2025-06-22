<?php

// Setup page for Certificado module
require __DIR__ . '/../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once __DIR__ . '/../class/actions_certificado.class.php';
$langs->load('certificado@certificado');
if (!$user->admin) accessforbidden();

// Tratar ações ACME
$action = GETPOST('action', 'alpha');

// Instalar cliente ACME.sh
if ($action === 'install_acme') {
    $act = new ActionsCertificado($db);
    $out = [];
    if ($act->installAcmeSh($out)) {
        setEventMessage('Cliente ACME instalado com sucesso');
    } else {
        setEventMessage('Erro na instalação do Acme.sh: '.implode("\n", $out), 'errors');
    }
    header('Location: '.$_SERVER['PHP_SELF']); 
    exit;
}

// Emitir certificado
if ($action === 'issue_cert') {
    $act = new ActionsCertificado($db);
    $out = [];
    $domain = preg_replace('#^https?://#', '', rtrim($conf->global->CERTIFICADO_BASE_URL, '/'));
    if ($act->issueCertificate($domain, $out)) {
        setEventMessage('Certificado emitido com sucesso');
    } else {
        setEventMessage('Erro na emissão do certificado: '.implode("\n", $out), 'errors');
    }
    header('Location: '.$_SERVER['PHP_SELF']); 
    exit;
}

// Opções para o seletor de tipo de certificado
$toselect = array(
    'selfsigned' => 'Auto-assinado',
    'letsencrypt' => 'Let\'s Encrypt',
    'custom' => 'Personalizado'
);
$form = new Form($db);
if ($_POST) {
    dolibarr_set_const($db, 'CERTIFICADO_BASE_URL', GETPOST('base_url', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CERTIFICADO_FORCE_HTTPS', GETPOST('force_https', 'int'), 'yesno', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CERTIFICADO_CERT_TYPE', GETPOST('cert_type', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CERTIFICADO_LE_EMAIL', GETPOST('le_email', 'email'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CERTIFICADO_CUSTOM_CRT', GETPOST('custom_crt', 'chaine'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CERTIFICADO_CUSTOM_KEY', GETPOST('custom_key', 'chaine'), 'chaine', 0, '', $conf->entity);
    setEventMessage('Configurações salvas');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


llxHeader('', 'Certificado SSL');
print load_fiche_titre('Configuração de Certificado SSL');

print '<form method="post">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>Parâmetro</td><td>Valor</td></tr>';
print '<tr><td>URL base</td><td><input name="base_url" value="' . $conf->global->CERTIFICADO_BASE_URL . '" size="60"></td></tr>';
print '<tr><td>Forçar HTTPS</td><td>' . $form->selectyesno('force_https', $conf->global->CERTIFICADO_FORCE_HTTPS) . '</td></tr>';
print '<tr><td>Tipo de certificado</td><td>' . $form->selectarray('cert_type', $toselect, $conf->global->CERTIFICADO_CERT_TYPE) . '</td></tr>';
print '<tr><td>E-mail Let\'s Encrypt</td><td><input name="le_email" value="' . $conf->global->CERTIFICADO_LE_EMAIL . '" size="40"></td></tr>';
print '<tr><td>Certificado .crt</td><td><input name="custom_crt" value="' . $conf->global->CERTIFICADO_CUSTOM_CRT . '" size="60"></td></tr>';
print '<tr><td>Chave .key</td><td><input name="custom_key" value="' . $conf->global->CERTIFICADO_CUSTOM_KEY . '" size="60"></td></tr>';
print '</table>';
print '<div class="tabsAction"><input type="submit" class="button" value="Salvar"></div>';
if ($conf->global->CERTIFICADO_CERT_TYPE === 'letsencrypt') {
    print '<div class="tabsAction"><input type="button" class="button" value="Instalar Cliente ACME" onclick="window.location.href=\'?action=install_acme&token='.newToken().'\'"></div>';
    print '<div class="tabsAction"><input type="button" class="button" value="Emitir Certificado" onclick="window.location.href=\'?action=issue_cert&token='.newToken().'\'"></div>';
}
print '</form>';


llxFooter();
