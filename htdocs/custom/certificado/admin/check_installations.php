<?php
// Carrega o ambiente do Dolibarr
require_once __DIR__ . '/../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Verifica se o usuário é administrador
if (!$user->admin) {
    accessforbidden();
}

// Carrega as funções de verificação
require_once __DIR__ . '/../class/actions_certificado.class.php';

// Cria uma instância da classe para acessar os métodos
$certificado = new ActionsCertificado($db);

// Verifica as instalações
$status = [];

// Verifica ACME.sh
$status['acme_sh'] = method_exists($certificado, 'checkAcmeShInstallation')
    ? $certificado->checkAcmeShInstallation()
    : ['installed' => false, 'details' => 'Método de verificação não disponível'];

// Verifica Composer
$status['composer'] = method_exists($certificado, 'isComposerInstalled')
    ? $certificado->isComposerInstalled()
    : ['installed' => false, 'details' => 'Método de verificação não disponível'];

// Informações adicionais
$status['php_version'] = phpversion();
$status['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido';
$status['dolibarr_version'] = DOL_VERSION;

// Exibe o resultado em formato HTML
llxHeader('', 'Verificação de Instalações');
print load_fiche_titre('Status das Instalações', '', 'title_setup');

// Estilos CSS
?>
<style>
    .status-container {
        max-width: 800px;
        margin: 20px auto;
    }
    .status-box {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
    }
    .status-header {
        font-size: 18px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #333;
    }
    .status-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        padding: 10px;
        background-color: #fff;
        border-radius: 4px;
        border-left: 4px solid #ddd;
    }
    .status-item.installed {
        border-left-color: #28a745;
    }
    .status-item.not-installed {
        border-left-color: #dc3545;
    }
    .status-icon {
        margin-right: 15px;
        font-size: 20px;
    }
    .status-details {
        flex-grow: 1;
    }
    .status-title {
        font-weight: bold;
        margin-bottom: 3px;
    }
    .status-description {
        color: #666;
        font-size: 13px;
    }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #17a2b8; }
</style>

<div class="status-container">
    <div class="status-box">
        <div class="status-header">
            <i class="fa fa-check-circle"></i> Status das Ferramentas
        </div>
        
        <!-- ACME.sh -->
        <div class="status-item <?php echo $status['acme_sh']['installed'] ? 'installed' : 'not-installed'; ?>">
            <div class="status-icon">
                <i class="fa fa-<?php echo $status['acme_sh']['installed'] ? 'check-circle success' : 'times-circle error'; ?>"></i>
            </div>
            <div class="status-details">
                <div class="status-title">ACME.sh</div>
                <div class="status-description">
                    <?php 
                    echo $status['acme_sh']['installed'] 
                        ? 'Instalado' . (isset($status['acme_sh']['version']) ? ' (Versão ' . $status['acme_sh']['version'] . ')' : '')
                        : 'Não instalado';
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Composer -->
        <div class="status-item <?php echo $status['composer']['installed'] ? 'installed' : 'not-installed'; ?>">
            <div class="status-icon">
                <i class="fa fa-<?php echo $status['composer']['installed'] ? 'check-circle success' : 'times-circle error'; ?>"></i>
            </div>
            <div class="status-details">
                <div class="status-title">Composer</div>
                <div class="status-description">
                    <?php 
                    echo $status['composer']['installed'] 
                        ? 'Instalado' . (isset($status['composer']['version']) ? ' (Versão ' . $status['composer']['version'] . ')' : '')
                        : 'Não instalado';
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="status-box">
        <div class="status-header">
            <i class="fa fa-info-circle"></i> Informações do Ambiente
        </div>
        
        <div class="status-item">
            <div class="status-icon">
                <i class="fa fa-code info"></i>
            </div>
            <div class="status-details">
                <div class="status-title">PHP</div>
                <div class="status-description">
                    Versão <?php echo $status['php_version']; ?>
                </div>
            </div>
        </div>
        
        <div class="status-item">
            <div class="status-icon">
                <i class="fa fa-server info"></i>
            </div>
            <div class="status-details">
                <div class="status-title">Servidor Web</div>
                <div class="status-description">
                    <?php echo $status['server_software']; ?>
                </div>
            </div>
        </div>
        
        <div class="status-item">
            <div class="status-icon">
                <i class="fa fa-cube info"></i>
            </div>
            <div class="status-details">
                <div class="status-title">Dolibarr</div>
                <div class="status-description">
                    Versão <?php echo $status['dolibarr_version']; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tabsAction" style="text-align: center; margin-top: 20px;">
        <a href="setup_acme.php" class="button">
            <i class="fa fa-arrow-left"></i> Voltar para Configurações
        </a>
    </div>
</div>

<?php
llxFooter();
?>
