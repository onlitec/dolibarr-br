<?php
/* Copyright (C) 2025 YourCompany
 * Licensed under the GNU GPL v3
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modServiceordergeo extends DolibarrModules
{
    public function __construct($db)
    {
        parent::__construct($db);
        // Inicializa conf->serviceordergeo se não existir, para evitar undefined
        global $conf;
        if (!isModEnabled('serviceordergeo')) {
            $conf->serviceordergeo = new stdClass();
            $conf->serviceordergeo->enabled = 0;
        }
        $this->numero = 104000;            // ID único do módulo (começando em 104000 para custom)
        $this->rights_class = 'serviceordergeo';
        $this->family = 'services';
        $this->name = 'Serviceordergeo';    // Nome interno (sem espaços)
        $this->description = 'Módulo de Ordem de Serviço com Geolocalização e Cálculo de Custo';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_SERVICEORDERGEO';
        $this->picto = 'serviceordergeo@serviceordergeo';

        // Dependências de outros módulos
        $this->module_parts = array(
            // Hooks para formulários de pedido, fatura, intervenção e proposta
            'hooks' => array('ordercard', 'invoicecard', 'interventioncard', 'propalcard'),
            'triggers' => array()
        );

        // Configurações do módulo
        $this->config_page_url = array(DOL_URL_ROOT.'/custom/serviceordergeo/htdocs/admin/admin_serviceordergeo.php');

        // Menus a adicionar
        $this->menu = array();
        $r = 0;
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=setup',
            'type' => 'left',
            'titre' => 'ServiceordergeoTitle',
            'mainmenu' => 'setup',
            'leftmenu' => 'serviceordergeo_setup',
            'url' => '/custom/serviceordergeo/htdocs/admin/admin_serviceordergeo.php',
            'langs' => 'serviceordergeo@serviceordergeo',
            'position' => 100,
            'enabled' => 'isModEnabled("serviceordergeo")',
            'perms' => '$user->admin',
            'target' => '',
            'user' => 2
        );

        // Permissões
        $this->rights = array(
            0 => array('id' => 104000, 'label' => 'Gerenciar configuração de Geolocalização', 'default' => 0),
        );

        // Linguagens
        $this->langfiles = array('serviceordergeo@serviceordergeo');
    }

    /**
     * Init module (called when enabling module)
     * @param string $options Options when enabling module
     * @return int
     */
    public function init($options = '')
    {
        // Carrega e executa SQLs de script/sql (erros de SQL ignorados para não bloquear ativação)
        $this->_load_tables('/custom/serviceordergeo/script/sql');
        // Executa instalação usando _init
        return $this->_init(array(), $options);
    }

    /**
     * Remove module (called when disabling module)
     * @param string $options options when disabling module
     * @return int
     */
    public function remove($options = '')
    {
        // Executa desinstalação usando _remove sem SQL adicional
        return $this->_remove(array(), $options);
    }
} 