<?php
/*
 * 
 * Module descriptor for Certificado
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modCertificado extends DolibarrModules
{
    public function __construct($db)
    {
        parent::__construct($db);
        $this->numero = 104000;
        $this->rights_class = 'certificado';
        $this->family = 'administration';
        $this->name = preg_replace('/^mod/', '', get_class($this));
        $this->description = 'Módulo para alterar URL base e gerenciar certificados SSL';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_CERTIFICADO';
        $this->config_page_url = array(DOL_URL_ROOT . '/custom/certificado/admin/setup_acme.php?leftmenu=admintools');
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array('certificado@certificado');

        // Menu para Certificado SSL em Admin Tools
        $this->menu = array();
        $r = 0;
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=home,fk_leftmenu=admintools',
            'type' => 'left',
            'titre' => 'Certificado SSL',
            'url' => '/custom/certificado/admin/setup_acme.php',
            'langs' => 'certificado@certificado',
            'position' => 100,
            'enabled' => '$conf->certificado->enabled && $user->admin',
            'perms' => '$user->admin',
            'target' => '',
            'user' => 2
        );
        $this->module_parts = array(
            'hooks' => array('certificado'),
        );
        $this->const = array(
            0 => array('CERTIFICADO_BASE_URL', '', 'URL base do Dolibarr', 0, '', 0),
            1 => array('CERTIFICADO_FORCE_HTTPS', '1', 'Forçar HTTPS', 0, '', 0),
            2 => array('CERTIFICADO_CERT_TYPE', 'letsencrypt', 'Tipo de certificado (letsencrypt|custom)', 0, '', 0),
            3 => array('CERTIFICADO_LE_EMAIL', '', 'E-mail para Let\'s Encrypt', 0, '', 0),
            4 => array('CERTIFICADO_CUSTOM_CRT', '', 'Caminho para certificado .crt', 0, '', 0),
            5 => array('CERTIFICADO_CUSTOM_KEY', '', 'Caminho para chave privada .key', 0, '', 0),
        );
        $this->tabs = array();
        $this->picto = 'lock';
    }

    public function init($options = '')
    {
        $sql = array();
        // Chama o init padrão para criar constantes e tabelas
        $result = $this->_init($sql, $options);
        // Se habilitado com sucesso, instala o cliente ACME automaticamente
        if ($result) {
            // Instala o ACME.sh
            require_once DOL_DOCUMENT_ROOT . '/custom/certificado/class/actions_certificado.class.php';
            $act = new ActionsCertificado($this->db);
            $out = array();
            if ($act->installAcmeSh($out)) {
                dol_syslog('ACME.sh instalado automaticamente com sucesso pelo módulo Certificado', LOG_INFO);
            } else {
                dol_syslog('Falha ao instalar ACME.sh automaticamente: ' . implode("\n", $out), LOG_ERR);
            }
        }
        return $result;
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
