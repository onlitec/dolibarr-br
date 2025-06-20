<?php
/**
 * \file    custom/restore/core/modules/modRestore.class.php
 * \ingroup restore
 * \brief   Descriptor do módulo Restore para restauração de banco de dados e arquivos
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Descrição e ativação do módulo Restore
 */
class modRestore extends DolibarrModules
{
    /**
     * Construtor. Define nomes, constantes, diretórios e menus.
     *
     * @param DoliDB $db Manipulador de banco
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        // ID único do módulo (verificar número livre)
        $this->numero = 520000;

        // Classe de permissões
        $this->rights_class = 'restore';

        // Família do módulo
        $this->family = 'other';
        $this->module_position = 500;

        // Nome do módulo
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Descrição curta
        $this->description = "Módulo de restauração de banco de dados e arquivos";

        // Versão do módulo
        $this->version = '0.1';

        // Constante para ativar/desativar
        $this->const_name = 'MAIN_MODULE_RESTORE';

        // Ícone do módulo
        $this->picto = '/custom/restore/img/restore.png';

        // Partes do módulo (triggers, menus, etc.)
        $this->module_parts = array(
            'triggers' => 0,
            'login' => 0,
            'substitutions' => 0,
            'menus' => 0,
            'tpl' => 0,
            'barcode' => 0,
            'models' => 0,
            'printing' => 0,
            'theme' => 0,
            'css' => array(),
            'js' => array(),
            'hooks' => array(),
            'moduleforexternal' => 0
        );

        // Diretórios a criar
        $this->dirs = array();

        // Páginas de configuração
        $this->config_page_url = array();

        // Dependências e conflitos
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array("restore@restore");
        $this->phpmin = array(7, 0);
        $this->need_dolibarr_version = array(13, 0);

        // Constantes específicas do módulo
        $this->const = array();

        // Permissões fornecidas pelo módulo
        $this->rights = array();
        $r = 0;

        // Menus do módulo (removidos para integração direta na página dolibarr_import.php)
        $this->menu = array();
    }

    /**
     * Executa na ativação do módulo
     */
    public function init($options = '')
    {
        // Use _active() and insert_const (no custom SQL)
        return $this->_init(array(), $options);
    }

    /**
     * Executa na desativação do módulo
     */
    public function remove($options = '')
    {
        // Use _unactive() and delete_const (no custom SQL)
        return $this->_remove(array(), $options);
    }
} 