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
        global $langs, $conf;
        if (! in_array($object->table_element, array('commande', 'fichinter', 'propal'))) {
            return 0;
        }
        // Hide module fields on proposal and service order creation
        if (in_array($object->table_element, array('propal', 'fichinter')) && empty($object->id)) {
            return 0;
        }
        // Início do card de configuração de pedido/intervenção dentro da tabela
            print '<tr><td colspan="2">';
            print '<div class="card mb-4">';
            print '<div class="card-body">';
            print '<div class="form-row">';
            // Exibir Endereço de Destino apenas para pedidos (commande)
            if ($object->table_element === 'commande') {
                // Destino e geolocalização
                print '<div class="form-group col-md-6 mb-3">';
                print '<label>' . $langs->trans('FieldDestinationAddress') . '</label>';
                print '<input type="text" name="destination_address" id="destination_address" value="'.htmlspecialchars($object->destination_address).'" class="form-control">';
                print '<input type="hidden" name="latitude" id="latitude" value="'.htmlspecialchars($object->latitude).'">';
                print '<input type="hidden" name="longitude" id="longitude" value="'.htmlspecialchars($object->longitude).'">';
                print '</div>';
            }
            // Adiciona campos hidden de latitude e longitude para fichinter
            if (in_array($object->table_element, array('fichinter', 'propal'))) {
                // Não ocupa coluna visível
                print '<input type="hidden" name="latitude" id="latitude" value="'.htmlspecialchars($object->latitude).'">';
                print '<input type="hidden" name="longitude" id="longitude" value="'.htmlspecialchars($object->longitude).'">';
            }
            // Tipo de serviço
            print '<div class="form-group col-md-6 mb-3">';
            print '<label>' . $langs->trans('FieldServiceType') . '</label>';
            print '<select name="service_type" id="service_type" class="form-control">'
                 . '<option value="internal"'.($object->service_type=='internal'?' selected':''). '>'.$langs->trans('ServiceInternal').'</option>'
                 . '<option value="remote"'.($object->service_type=='remote'?' selected':''). '>'.$langs->trans('ServiceRemote').'</option>'
                 . '<option value="external"'.($object->service_type=='external'?' selected':''). '>'.$langs->trans('ServiceExternal').'</option>'
             . '</select>';
            print '</div>';
            // Endereço de serviço
            print '<div class="form-group col-md-6 mb-3" id="service_address_group"'.($object->service_type!='external'?' style="display:none;"':'').'>';
            print '<label>' . $langs->trans('FieldServiceAddress') . '</label>';
            print '<input type="text" name="service_address" id="service_address" value="'.htmlspecialchars($object->service_address).'" class="form-control mb-2 pac-target-input" placeholder="'.dol_escape_htmltag($langs->trans('HelpMapSearch')).'" autocomplete="off">';
            print '</div>';
            // Fecha primeira linha de fields
            print '</div>';
            // Segunda linha: checkbox e origem
            print '<div class="form-row">';
            // Valores padrão para origem e custos
            $defaultOrigin = getDolGlobalString('MAIN_INFO_SOCIETE_ADDRESS') . ', ' . getDolGlobalString('MAIN_INFO_SOCIETE_ZIP') . ' ' . getDolGlobalString('MAIN_INFO_SOCIETE_TOWN');
            $useCompany = empty($object->origin_address) || $object->origin_address === $defaultOrigin;
            $valueOrigin = $useCompany ? $defaultOrigin : $object->origin_address;
            $valueFuelPrice = isset($object->fuel_price) && $object->fuel_price !== '' ? $object->fuel_price : (isset($conf->global->SERVICEORDERGEO_DEFAULT_FUEL_PRICE) ? $conf->global->SERVICEORDERGEO_DEFAULT_FUEL_PRICE : 0);
            $valueFuelConsumption = isset($object->fuel_consumption) && $object->fuel_consumption !== '' ? $object->fuel_consumption : (isset($conf->global->SERVICEORDERGEO_DEFAULT_FUEL_CONSUMPTION) ? $conf->global->SERVICEORDERGEO_DEFAULT_FUEL_CONSUMPTION : 0);
            $valueOtherCosts = isset($object->other_costs) && $object->other_costs !== '' ? $object->other_costs : (isset($conf->global->SERVICEORDERGEO_DEFAULT_OTHER_COSTS) ? $conf->global->SERVICEORDERGEO_DEFAULT_OTHER_COSTS : 0);
            // Checkbox para usar endereço da empresa
            print '<div class="form-group col-md-6 mb-3"><div class="form-check">';
            print '<input type="checkbox" name="use_company_address" id="use_company_address" class="form-check-input"'.($useCompany?' checked':'').'>';
            print '<label class="form-check-label" for="use_company_address">'.$langs->trans('UseCompanyAddress').'</label>';
            print '</div></div>';
            // Origem e parâmetros de custo (apenas para serviço externo)
            print '<div class="form-group col-md-6 mb-3" id="origin_group"'.($object->service_type!=='external'?' style="display:none;"':'').'>';
            print '<label>' . $langs->trans('FieldOriginAddress') . '</label>';
            print '<input type="text" name="origin_address" id="origin_address" value="'.htmlspecialchars($valueOrigin).'" class="form-control pac-target-input" placeholder="'.dol_escape_htmltag($langs->trans('HelpOriginAddressUseCompany')).'" autocomplete="off">';
            print '</div>';
            // Fecha segunda linha
            print '</div>';
            print '<div class="form-row" id="cost_group"'.($object->service_type!=='external'?' style="display:none;"':'').'>';
            print '<div class="form-group col-md-4">';
            print '<label>' . $langs->trans('FieldFuelPrice') . '</label>';
            print '<input type="text" name="fuel_price" id="fuel_price" value="'.htmlspecialchars($valueFuelPrice).'" class="form-control">';
            print '</div>';
            print '<div class="form-group col-md-4">';
            print '<label>' . $langs->trans('FieldFuelConsumption') . '</label>';
            print '<input type="text" name="fuel_consumption" id="fuel_consumption" value="'.htmlspecialchars($valueFuelConsumption).'" class="form-control">';
            print '</div>';
            print '<div class="form-group col-md-4">';
            print '<label>' . $langs->trans('FieldOtherCosts') . '</label>';
            print '<input type="text" name="other_costs" id="other_costs" value="'.htmlspecialchars($valueOtherCosts).'" class="form-control">';
            print '</div>';
            print '</div>';
            // Campos de distância e custo
            print '<div class="form-row" id="travel_group"'.($object->service_type!=='external'?' style="display:none;"':'').'>';
            print '<div class="form-group col-md-6">';
            print '<label>'.$langs->trans('FieldDistance').'</label>';
            print '<input type="text" readonly class="form-control" name="distance_km" value="'.htmlspecialchars($object->distance_km).'">';
            print '</div>';
            print '<div class="form-group col-md-6">';
            print '<label>'.$langs->trans('FieldTravelCost').'</label>';
            print '<input type="text" readonly class="form-control" name="travel_cost" value="'.price($object->travel_cost).'">';
            print '</div>';
            print '</div>';
            // Toggle unificado para fields no fichinter
            print '<script>
(function(){
    var sel = document.getElementById("service_type");
    var sa = document.getElementById("service_address_group");
    var og = document.getElementById("origin_group");
    var cg = document.getElementById("cost_group");
    function toggleFields(){
        var show = sel.value === "external";
        sa.style.display = show ? "block" : "none";
        og.style.display = show ? "block" : "none";
        cg.style.display = show ? "block" : "none";
        var mg = document.getElementById("map_group");
        if(mg) mg.style.display = show ? "block" : "none";
    }
    sel.addEventListener("change", toggleFields);
    toggleFields();
})();
</script>';
            // Script de geocoding
            print '<script src="'.dol_buildpath('/custom/serviceordergeo/js/serviceordergeo.js',1).'" defer></script>';
            // Script para alternar uso do endereço da empresa
            print '<script>';
            print '(function(){';
            print 'var useChk = document.getElementById("use_company_address");';
            print 'var orig = document.getElementById("origin_address");';
            print 'var defaultAddr = '.json_encode($defaultOrigin).';';
            print 'function toggleUse(){ if(useChk.checked){ orig.value = defaultAddr; orig.readOnly = true; } else { orig.readOnly = false; } }';
            print 'useChk.addEventListener("change", toggleUse);';
            print 'toggleUse();';
            print '})();';
            print '</script>';
            // Integração com Google Maps Places Autocomplete
            print '<div id="map_group"'.($object->service_type!=='external'?' style="display:none;"':'').'>';
            if (!empty($conf->global->SERVICEORDERGEO_GEOCODE_API_KEY)) {
    print '<script src="https://maps.googleapis.com/maps/api/js?key='.htmlspecialchars($conf->global->SERVICEORDERGEO_GEOCODE_API_KEY).'&libraries=places"></script>';
} else {
    print '<!-- SERVICEORDERGEO_GEOCODE_API_KEY not configured -->';
}
            print '<button type="button" class="btn btn-sm btn-secondary mb-2" id="btnOpenMap">'.$langs->trans('ButtonOpenMap').'</button>';
            print '<div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">';
            print '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">';
            print '<div class="modal-header"><h5 class="modal-title" id="mapModalLabel">'.$langs->trans('MapSearchTitle').'</h5>';
            print '<button type="button" class="close" data-dismiss="modal" aria-label="'.$langs->trans('Close').'"><span aria-hidden="true">&times;</span></button></div>';
            print '<div class="modal-body">';
            print '<input type="text" id="map_search_input" class="form-control mb-2" placeholder="'.dol_escape_htmltag($langs->trans('HelpMapSearch')).'">';
            print '<div id="mapid" style="height:400px;"></div>';
            print '</div>';
            print '<div class="modal-footer">';
            print '<button type="button" class="btn btn-primary" id="btnSelectLocation">'.$langs->trans('SelectLocation').'</button>';
            print '<button type="button" class="btn btn-secondary" data-dismiss="modal">'.$langs->trans('Close').'</button>';
            print '</div></div></div></div>';
            // Carrega Bootstrap Bundle para modal
            
            
            print '<script>';
            print 'jQuery(document).ready(function(){';
            print '    var modal = jQuery("#mapModal");';
            print ' var map = new google.maps.Map(document.getElementById("mapid"), {center:{lat:0,lng:0},zoom:2});';
            print ' var marker = new google.maps.Marker({ map: map });';
            print ' var input = document.getElementById("map_search_input");';
            print ' var autocomplete = new google.maps.places.Autocomplete(input);';
            
            print ' autocomplete.addListener("place_changed", function(){ var place = autocomplete.getPlace(); if(!place.geometry) return; map.setCenter(place.geometry.location); map.setZoom(15); marker.setPosition(place.geometry.location); });';
            print ' jQuery("#btnOpenMap").on("click", function(){';
            print '     var addr = jQuery("#service_address").val();';
            print '     jQuery("#mapModal").addClass("show").css("display","block");';
            print '     var backdrop = document.createElement("div");';
            print '     backdrop.className = "modal-backdrop fade show";';
            print '     document.body.appendChild(backdrop);';
            print '     google.maps.event.trigger(map, "resize");';
            print '     if(addr){';
            print '         var geocoder = new google.maps.Geocoder();';
            print '         geocoder.geocode({ address: addr }, function(results, status){';
            print '             if(status === google.maps.GeocoderStatus.OK && results[0]){';
            print '                 var loc = results[0].geometry.location;';
            print '                 map.setCenter(loc);';
            print '                 map.setZoom(15);';
            print '                 marker.setPosition(loc);';
            print '                 document.getElementById("map_search_input").value = results[0].formatted_address;';
            print '             } else { alert("Endereço não encontrado"); }';
            print '         });';
            print '     } else {';
            print '         map.setCenter(marker.getPosition()||{lat:0,lng:0});';
            print '     }';
            print ' });';
            print ' jQuery("#btnSelectLocation").on("click", function(){ var pos = marker.getPosition(); if(pos){ var geocoder = new google.maps.Geocoder(); geocoder.geocode({location: pos}, function(results, status){ if(status === "OK" && results[0]){ jQuery("#service_address").val(results[0].formatted_address); jQuery("#latitude").val(pos.lat()); jQuery("#longitude").val(pos.lng()); jQuery("#mapModal").removeClass("show").css("display","none"); jQuery(".modal-backdrop").remove(); } }); } });';
            print '});';
            print '</script>';
            // Inicializa Autocomplete do Google Places no campo service_address
            print '<script>';
            print 'google.maps.event.addDomListener(window, "load", function(){';
            print '    var sa = document.getElementById("service_address");';
            print '    if(sa){ new google.maps.places.Autocomplete(sa); }';
            
            print '});';
            print '</script>';
            print '</div>';
            print '</div>';// Fecha map_group
            // Botão para adicionar custo de deslocamento
            print '<div class="form-row"><div class="form-group col-md-12">';
            print '<button type="submit" name="addtravel" value="1" class="btn btn-secondary mt-2">Adicionar custo deslocamento</button>'; print '<script>jQuery(document).ready(function(){jQuery("button[name=addtravel]").on("click",function(){var form=jQuery(this).closest("form");if(!form.find("input[name=tab]").length){jQuery("<input>").attr({type:"hidden",name:"tab",value:2}).appendTo(form);}else{form.find("input[name=tab]").val(2);}return true;});});</script>';
            print '</div></div>';


            print '</div></div>';// Fecha card-body e card
            // Fecha row da tabela
            print '</td></tr>';
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
            
            $this->formObjectOptions($parameters, $object, $action, $hookmanager);

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
                // Geocode origem
                $origLat = 0; $origLon = 0;
                if (!empty($orig)) {
                    $url = DOL_URL_ROOT . '/custom/serviceordergeo/htdocs/api/serviceordergeo/geocode.php?address=' . urlencode($orig);
                    $resp = dol_httpclientGET($url);
                    $data = json_decode($resp, true);
                    if (!empty($data['latitude']) && !empty($data['longitude'])) {
                        $origLat = (float)$data['latitude'];
                        $origLon = (float)$data['longitude'];
                    }
                }
                // Distância e custo
                $destLat = (float)$lat; $destLon = (float)$lon;
                $distance_oneway = 0;
                if ($origLat && $origLon && $destLat && $destLon) {
                    $deg2rad = function($deg) { return $deg * M_PI / 180; };
                    $dLat = $deg2rad($destLat - $origLat);
                    $dLon = $deg2rad($destLon - $origLon);
                    $a = sin($dLat/2) * sin($dLat/2)
                       + cos($deg2rad($origLat)) * cos($deg2rad($destLat))
                       * sin($dLon/2) * sin($dLon/2);
                    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                    $R = 6371;
                    $distance_oneway = round($R * $c, 2);
                }
                $total_distance = 2 * $distance_oneway;
                $cost_per_km = $fuel_consumption > 0 ? ($fuel_price / $fuel_consumption) : 0;
                $cost_per_km += $other_costs;
                $total_cost = round($total_distance * $cost_per_km, 2);
                // Atualiza pedido com distância e custo
                $db->query('UPDATE '.MAIN_DB_PREFIX.'commande'
                     . ' SET distance_km=' . $total_distance . ', travel_cost=' . $total_cost . ' WHERE rowid=' . (int)$rowid);
                // Persiste em extrafields
                if (!empty($object->array_options)) {
                    $object->array_options['options_distance_km'] = $total_distance;
                    $object->array_options['options_travel_cost'] = $total_cost;
                    $object->insertExtraFields();
                }
                // Se solicitado, adiciona linha de custo de deslocamento
                if (GETPOST('addtravel','int') == '1') {
                    global $langs;
                    $langs->load('serviceordergeo@serviceordergeo');
                    $object->addline($langs->trans('FieldTravelCost'), $total_cost, 1, 0);
                }
            }
        }
        // Contexto de intervenção
        if ($currentcontext === 'interventioncard' && in_array($action, array('create','update'))) {
            $rowid = $object->id;
            if ($rowid) {
                $svc = GETPOST('service_type', 'alpha');
                $addr = GETPOST('service_address', 'alpha');
                $orig = GETPOST('origin_address', 'alpha');
                $lat = GETPOST('latitude', 'alpha');
                $lon = GETPOST('longitude', 'alpha');
                $fuel_price = (float)GETPOST('fuel_price', 'alpha');
                $fuel_consumption = (float)GETPOST('fuel_consumption', 'alpha');
                $other_costs = (float)GETPOST('other_costs', 'alpha');

                // Geocode origem
                $origLat = 0; $origLon = 0;
                if (!empty($orig)) {
                    $url = DOL_URL_ROOT . '/custom/serviceordergeo/htdocs/api/serviceordergeo/geocode.php?address=' . urlencode($orig);
                    $resp = dol_httpclientGET($url);
                    $data = json_decode($resp, true);
                    if (!empty($data['latitude']) && !empty($data['longitude'])) {
                        $origLat = (float)$data['latitude'];
                        $origLon = (float)$data['longitude'];
                    }
                }

                // Distância e custo
                $destLat = (float)$lat; $destLon = (float)$lon;
                $distance_oneway = 0;
                if ($origLat && $origLon && $destLat && $destLon) {
                    $deg2rad = function($deg) { return $deg * M_PI / 180; };
                    $dLat = $deg2rad($destLat - $origLat);
                    $dLon = $deg2rad($destLon - $origLon);
                    $a = sin($dLat/2) * sin($dLat/2)
                       + cos($deg2rad($origLat)) * cos($deg2rad($destLat))
                       * sin($dLon/2) * sin($dLon/2);
                    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                    $R = 6371;
                    $distance_oneway = round($R * $c, 2);
                }
                $total_distance = 2 * $distance_oneway;
                $cost_per_km = $fuel_consumption > 0 ? ($fuel_price / $fuel_consumption) : 0;
                $cost_per_km += $other_costs;
                $total_cost = round($total_distance * $cost_per_km, 2);

                // Atualiza ficha de intervenção
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinter'
                     . ' SET service_type='.$db->quote($svc)
                     . ', service_address='.$db->quote($addr)
                     . ', origin_address='.$db->quote($orig)
                     . ', latitude='.$db->quote($lat)
                     . ', longitude='.$db->quote($lon)
                     . ', fuel_price='.$db->quote($fuel_price)
                     . ', fuel_consumption='.$db->quote($fuel_consumption)
                     . ', other_costs='.$db->quote($other_costs)
                     . ', distance_km='.$total_distance
                     . ', travel_cost='.$total_cost
                     . ' WHERE rowid=' . ((int)$rowid);
                $db->query($sql);
                // Se solicitado, adiciona linha de custo de deslocamento
                // Persiste em extrafields
                if (!empty($object->array_options)) {
                    $object->array_options['options_distance_km'] = $total_distance;
                    $object->array_options['options_travel_cost'] = $total_cost;
                    $object->insertExtraFields();
                }
                if (GETPOST('addtravel','int') == '1') {
                    global $langs;
                    $langs->load('serviceordergeo@serviceordergeo');
                    $object->addline($langs->trans('FieldTravelCost'), $total_cost, 1, 0);
                }
            }
        }
        // Contexto de proposta
        if ($currentcontext === 'propalcard' && in_array($action, array('add','create','update'))) {
            $rowid = $object->id;
            if ($rowid) {
                $svc = GETPOST('service_type', 'alpha');
                $addr = GETPOST('service_address', 'alpha');
                $orig = GETPOST('origin_address', 'alpha');
                $lat = GETPOST('latitude', 'alpha');
                $lon = GETPOST('longitude', 'alpha');
                $fuel_price = (float)GETPOST('fuel_price', 'alpha');
                $fuel_consumption = (float)GETPOST('fuel_consumption', 'alpha');
                $other_costs = (float)GETPOST('other_costs', 'alpha');

                // Geocode origem
                $origLat = 0; $origLon = 0;
                if (!empty($orig)) {
                    $url = DOL_URL_ROOT . '/custom/serviceordergeo/htdocs/api/serviceordergeo/geocode.php?address=' . urlencode($orig);
                    $resp = dol_httpclientGET($url);
                    $data = json_decode($resp, true);
                    if (!empty($data['latitude']) && !empty($data['longitude'])) {
                        $origLat = (float)$data['latitude'];
                        $origLon = (float)$data['longitude'];
                    }
                }

                // Calcular distância e custo
                $destLat = (float)$lat; $destLon = (float)$lon;
                $distance_oneway = 0;
                if ($origLat && $origLon && $destLat && $destLon) {
                    $deg2rad = function($deg) { return $deg * M_PI / 180; };
                    $dLat = $deg2rad($destLat - $origLat);
                    $dLon = $deg2rad($destLon - $origLon);
                    $a = sin($dLat/2) * sin($dLat/2)
                       + cos($deg2rad($origLat)) * cos($deg2rad($destLat))
                       * sin($dLon/2) * sin($dLon/2);
                    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                    $R = 6371;
                    $distance_oneway = round($R * $c, 2);
                }
                $total_distance = 2 * $distance_oneway;
                $cost_per_km = $fuel_consumption > 0 ? ($fuel_price / $fuel_consumption) : 0;
                $cost_per_km += $other_costs;
                $total_cost = round($total_distance * $cost_per_km, 2);

                // Atualiza proposta
                $sql = 'UPDATE '.MAIN_DB_PREFIX.'propal'
                     . ' SET service_type='.$db->quote($svc)
                     . ', service_address='.$db->quote($addr)
                     . ', origin_address='.$db->quote($orig)
                     . ', latitude='.$db->quote($lat)
                     . ', longitude='.$db->quote($lon)
                     . ', fuel_price='.$db->quote($fuel_price)
                     . ', fuel_consumption='.$db->quote($fuel_consumption)
                     . ', other_costs='.$db->quote($other_costs)
                     . ', distance_km='.$total_distance
                     . ', travel_cost='.$total_cost
                     . ' WHERE rowid='.((int)$rowid);
                $db->query($sql);
                // Se solicitado, adiciona linha de custo de deslocamento
                // Persiste em extrafields
                if (!empty($object->array_options)) {
                    $object->array_options['options_distance_km'] = $total_distance;
                    $object->array_options['options_travel_cost'] = $total_cost;
                    $object->insertExtraFields();
                }
                if (GETPOST('addtravel','int') == '1') {
                    global $langs;
                    $langs->load('serviceordergeo@serviceordergeo');
                    $object->addline($langs->trans('FieldTravelCost'), $total_cost, 1, 0);
                }
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
                                    // Atualiza extrafields de fatura
                $db->query('UPDATE '.MAIN_DB_PREFIX.'facture_extrafields SET distance_km='.$distance_km.', travel_cost='.$travel_cost.' WHERE fk_object='.(int)$facid);
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