<?php
/* Página de configuração avançada do módulo Serviceordergeo */
require __DIR__ . '/../../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
// Carrega traduções do módulo
$langs->load('serviceordergeo@serviceordergeo');

// Permissão de administrador
if (!$user->admin) {
    accessforbidden();
}

// Ação de salvar configurações
$action = GETPOST('action', 'alpha');
if ($action === 'save') {
    // Chaves de API e valores padrão
    dolibarr_set_const($db, 'SERVICEORDERGEO_GEOCODE_API_KEY', GETPOST('geocode_api_key', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SERVICEORDERGEO_DISTANCE_API_KEY', GETPOST('distance_api_key', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SERVICEORDERGEO_DEFAULT_FUEL_PRICE', GETPOST('default_fuel_price', 'alpha'), 'float', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SERVICEORDERGEO_DEFAULT_FUEL_CONSUMPTION', GETPOST('default_fuel_consumption', 'alpha'), 'float', 0, '', $conf->entity);
    dolibarr_set_const($db, 'SERVICEORDERGEO_DEFAULT_OTHER_COSTS', GETPOST('default_other_costs', 'alpha'), 'float', 0, '', $conf->entity);
    setEventMessage($langs->trans('ConfirmUpdateConfig'));
    // Redireciona incluindo token para manter sessão válida
    header('Location: ' . $_SERVER['PHP_SELF'] . '?token=' . newToken());
    exit;
}

// Página e título
llxHeader('', $langs->trans('ConfigTitle'), '');
print load_fiche_titre($langs->trans('ConfigTitle'));

// Formulário
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="action" value="save">';
// Token de segurança para evitar CSRF
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="noborder" width="100%">';

// Função auxiliar para linha
function showRowConfig($labelKey, $helpKey, $name, $value) {
    global $langs;
    print '<tr class="oddeven"><td>'.$langs->trans($labelKey).'</td>';
    print '<td><input type="text" size="50" name="'.$name.'" value="'.htmlspecialchars($value).'" class="flat"></td>';
    print '<td>'.$langs->trans($helpKey).'</td></tr>';
}

// Linhas de configuração
showRowConfig('FieldGeocodeAPIKey','HelpGeocodeAPIKey','geocode_api_key', $conf->global->SERVICEORDERGEO_GEOCODE_API_KEY);
showRowConfig('FieldDistanceAPIKey','HelpDistanceAPIKey','distance_api_key', $conf->global->SERVICEORDERGEO_DISTANCE_API_KEY);
showRowConfig('FieldDefaultFuelPrice','HelpDefaultFuelPrice','default_fuel_price', $conf->global->SERVICEORDERGEO_DEFAULT_FUEL_PRICE);
showRowConfig('FieldDefaultFuelConsumption','HelpDefaultFuelConsumption','default_fuel_consumption', $conf->global->SERVICEORDERGEO_DEFAULT_FUEL_CONSUMPTION);
showRowConfig('FieldDefaultOtherCosts','HelpDefaultOtherCosts','default_other_costs', $conf->global->SERVICEORDERGEO_DEFAULT_OTHER_COSTS);

print '</table>';
print '<div class="tabsAction"><input type="submit" class="button" value="'.$langs->trans('Save').'" /></div>';
print '</form>';

// Footer
llxFooter();
$db->close();
?> 