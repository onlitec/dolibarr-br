<?php
/* Hooks do módulo Serviceordergeo */
require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
class ActionsServiceordergeo
{
    public $results;
    public $resprints;
    public $errors;
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
        $this->results = array();
        $this->errors = array();
    }
    /**
     * Hook acionado ao exibir formulário de Pedido (commande)
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        if (in_array($object->table_element, array('commande', 'fichinter'))) {
            // Exibir Endereço de Destino apenas para pedidos (commande)
            if ($object->table_element === 'commande') {
                // Destino e geolocalização
                print '<div class="form-group">';
                print '<label>' . $langs->trans('FieldDestinationAddress') . '</label>';
                print '<input type="text" name="destination_address" id="destination_address" value="'.htmlspecialchars($object->destination_address).'" class="form-control">';
                print '<input type="hidden" name="latitude" id="latitude" value="'.htmlspecialchars($object->latitude).'">';
                print '<input type="hidden" name="longitude" id="longitude" value="'.htmlspecialchars($object->longitude).'">';
                print '</div>';
            }
            // Tipo de serviço
            print '<div class="form-group">';
            print '<label>' . $langs->trans('FieldServiceType') . '</label>';
            print '<select name="service_type" id="service_type" class="form-control">'
                 . '<option value="internal"'.($object->service_type=='internal'?' selected':''). '>'.$langs->trans('ServiceInternal').'</option>'
                 . '<option value="remote"'.($object->service_type=='remote'?' selected':''). '>'.$langs->trans('ServiceRemote').'</option>'
                 . '<option value="external"'.($object->service_type=='external'?' selected':''). '>'.$langs->trans('ServiceExternal').'</option>'
             . '</select>';
            print '</div>';
            // Endereço de serviço
            print '<div class="form-group" id="service_address_group"'.($object->service_type!='external'?' style="display:none;"':'').'>';
            print '<label>' . $langs->trans('FieldServiceAddress') . '</label>';
            print '<input type="text" name="service_address" id="service_address" value="'.htmlspecialchars($object->service_address).'" class="form-control">';
            print '</div>';
            print '<script>
document.getElementById("service_type").addEventListener("change", function() {
    var grp = document.getElementById("service_address_group");
    grp.style.display = this.value === "external" ? "block" : "none";
});
</script>';
            // Origem (se vazio, usa endereço da empresa)
            print '<div class="form-group">';
            print '<label>' . $langs->trans('FieldOriginAddress') . '</label>';
            print '<input type="text" name="origin_address" id="origin_address" value="'.htmlspecialchars($object->origin_address).'" class="form-control" placeholder="'.dol_escape_htmltag($langs->trans('HelpOriginAddressUseCompany')).'">';
            print '</div>';
            // Parâmetros de custo
            print '<div class="form-row">';
            print '<div class="form-group col-md-4">';
            print '<label>' . $langs->trans('FieldFuelPrice') . '</label>';
            print '<input type="text" name="fuel_price" id="fuel_price" value="'.htmlspecialchars($object->fuel_price).'" class="form-control">';
            print '</div>';
            print '<div class="form-group col-md-4">';
            print '<label>' . $langs->trans('FieldFuelConsumption') . '</label>';
            print '<input type="text" name="fuel_consumption" id="fuel_consumption" value="'.htmlspecialchars($object->fuel_consumption).'" class="form-control">';
            print '</div>';
            print '<div class="form-group col-md-4">';
            print '<label>' . $langs->trans('FieldOtherCosts') . '</label>';
            print '<input type="text" name="other_costs" id="other_costs" value="'.htmlspecialchars($object->other_costs).'" class="form-control">';
            print '</div>';
            print '</div>';
            // Script de geocoding
            print '<script src="'.dol_buildpath('/custom/serviceordergeo/js/serviceordergeo.js',1).'" defer></script>';
        }
    }
    /**
     * Hook para exibir custo de deslocamento no cartão de fatura
     */
    public function printFieldList($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        if ($object->element === 'facture') {
            if (isset($object->array_options['options_distance_km'])) {
                print '<tr><td>'.$langs->trans('FieldDistance').'</td><td>'.$object->array_options['options_distance_km'].' km</td></tr>';
            }
            if (isset($object->array_options['options_travel_cost'])) {
                print '<tr><td>'.$langs->trans('FieldTravelCost').'</td><td>'.price($object->array_options['options_travel_cost']).'</td></tr>';
            }
        }
    }
    /**
     * Hook para inserir campos no formulário de proposta (propal)
     */
    public function tabContentCreateProposal($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        if ($object->element === 'propal') {
            // Tipo de serviço
            print '<tr><td>'.$langs->trans('FieldServiceType').'</td><td>';
            print '<select name="service_type">'
                . '<option value="internal">'.$langs->trans('ServiceInternal').'</option>'
                . '<option value="remote">'.$langs->trans('ServiceRemote').'</option>'
                . '<option value="external">'.$langs->trans('ServiceExternal').'</option>'
            . '</select>';
            print '</td></tr>';
            // Endereço de serviço
            print '<tr id="service_address_row" style="display:none;"><td>'.$langs->trans('FieldServiceAddress').'</td><td>';
            print '<input type="text" name="service_address" value="">';
            print '</td></tr>';
            // JS para mostrar campo de endereço
            print '<script>
(function(){
    var sel = document.getElementsByName("service_type")[0];
    sel.addEventListener("change", function() {
        var row = document.getElementById("service_address_row");
        row.style.display = this.value === "external" ? "table-row" : "none";
    });
})();
</script>';
        }
        return 0;
    }
    /**
     * Hook para salvar campos custom ao gravar pedido
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $db;
        $currentcontext = !empty($parameters['currentcontext']) ? $parameters['currentcontext'] : '';
        // Contexto de pedido
        if ($currentcontext === 'commandecard' && in_array($action, array('create','update'))) {
            $rowid = $object->id;
            if ($rowid) {
                $dest = GETPOST('destination_address','alpha');
                $orig = GETPOST('origin_address','alpha');
                $lat = GETPOST('latitude','alpha');
                $lon = GETPOST('longitude','alpha');
                $fuel_price = GETPOST('fuel_price','alpha');
                $fuel_consumption = GETPOST('fuel_consumption','alpha');
                $other_costs = GETPOST('other_costs','alpha');
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande'
                     . ' SET destination_address=' . $db->quote($dest)
                     . ', origin_address=' . $db->quote($orig)
                     . ', latitude=' . $db->quote($lat)
                     . ', longitude=' . $db->quote($lon)
                     . ', fuel_price=' . $db->quote($fuel_price)
                     . ', fuel_consumption=' . $db->quote($fuel_consumption)
                     . ', other_costs=' . $db->quote($other_costs)
                     . ' WHERE rowid=' . $rowid;
                $db->query($sql);
            }
        }
        // Contexto de intervenção
        if ($currentcontext === 'interventioncard' && in_array($action, array('create','update'))) {
            $rowid = $object->id;
            if ($rowid) {
                $svc = GETPOST('service_type', 'alpha');
                $addr = GETPOST('service_address', 'alpha');
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinter'
                     . ' SET service_type='.$db->quote($svc)
                     . ', service_address='.$db->quote($addr)
                     . ' WHERE rowid='.((int)$rowid);
                $db->query($sql);
            }
        }
        // Contexto de proposta
        if ($currentcontext === 'propalcard' && in_array($action, array('add','update'))) {
            $rowid = $object->id;
            if ($rowid) {
                $svc = GETPOST('service_type', 'alpha');
                $addr = GETPOST('service_address', 'alpha');
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'propal'
                     . ' SET service_type='.$db->quote($svc)
                     . ', service_address='.$db->quote($addr)
                     . ' WHERE rowid='.((int)$rowid);
                $db->query($sql);
            }
        }
        // Contexto de fatura: cálculo de distância e custo na validação
        if ($currentcontext === 'invoicecard' && $action === 'confirm_valid' && GETPOST('confirm','alpha') === 'yes') {
            $facid = $object->id;
            // Busca dados do pedido original
            $cmdId = $object->origin_id;
            if ($cmdId > 0) {
                $sql = 'SELECT destination_address, origin_address, latitude, longitude, fuel_price, fuel_consumption, other_costs'
                     . ' FROM ' . MAIN_DB_PREFIX . 'commande'
                     . ' WHERE rowid=' . (int)$cmdId;
                $resql = $db->query($sql);
                if ($resql && $db->num_rows($resql)) {
                    $objC = $db->fetch_object($resql);
                    // Geocode origem caso necessário
                    $origLat = 0; $origLon = 0;
                    if (!empty($objC->origin_address)) {
                        $url = DOL_URL_ROOT . '/custom/serviceordergeo/htdocs/api/serviceordergeo/geocode.php?address=' . urlencode($objC->origin_address);
                        $resp = dol_httpclientGET($url);
                        $data = json_decode($resp, true);
                        if (!empty($data['latitude']) && !empty($data['longitude'])) {
                            $origLat = (float)$data['latitude'];
                            $origLon = (float)$data['longitude'];
                        }
                    }
                    // Coords de destino
                    $destLat = (float)$objC->latitude;
                    $destLon = (float)$objC->longitude;
                    // Cálculo de distância (Haversine)
                    $distance_km = 0;
                    if ($origLat && $origLon && $destLat && $destLon) {
                        $deg2rad = function($deg) { return $deg * pi() / 180; };
                        $dLat = $deg2rad($destLat - $origLat);
                        $dLon = $deg2rad($destLon - $origLon);
                        $a = sin($dLat/2) * sin($dLat/2)
                           + cos($deg2rad($origLat)) * cos($deg2rad($destLat))
                           * sin($dLon/2) * sin($dLon/2);
                        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                        $R = 6371; // raio da Terra em km
                        $distance_km = round($R * $c, 2);
                    }
                    // Cálculo de custo
                    $cost_per_km = 0;
                    if ((float)$objC->fuel_consumption && (float)$objC->fuel_price) {
                        $cost_per_km = ($objC->fuel_price / $objC->fuel_consumption) + (float)$objC->other_costs;
                    }
                    $travel_cost = round($distance_km * $cost_per_km, 2);
                    // Atualiza fatura
                    $sql2 = 'UPDATE ' . MAIN_DB_PREFIX . 'facture'
                          . ' SET distance_km=' . $distance_km
                          . ', travel_cost=' . $travel_cost
                          . ' WHERE rowid=' . (int)$facid;
                    $db->query($sql2);
                    // Adiciona linha de custo de deslocamento na fatura
                    // Carrega tradução e insere linha com valor total de deslocamento
                    global $langs;
                    $langs->load('serviceordergeo@serviceordergeo');
                    if ($travel_cost > 0) {
                        $object->addline(
                            $langs->trans('FieldTravelCost'),
                            $travel_cost,
                            1,
                            0
                        );
                    }
                }
            }
        }
        return 0;
    }
} 