<?php
// Carrega o ambiente do Dolibarr
require_once __DIR__ . '/../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Verifica se o usuário é administrador
if (!$user->admin) {
    accessforbidden();
}

// Carrega a classe principal do módulo
require_once __DIR__ . '/../class/actions_certificado.class.php';

// Inicializa variáveis
$action = GETPOST('action', 'alpha');
$error = '';
$success = '';

// Cria instância da classe principal
$certificado = new ActionsCertificado($db);

// Processa ações
if ($action === 'issue_certificate') {
    $domain = GETPOST('domain', 'alpha');
    $email = GETPOST('email', 'email');
    $test = GETPOST('test', 'int');
    
    if (empty($domain)) {
        $error = 'O domínio é obrigatório';
    } else {
        $output = [];
        $result = $certificado->issueCertificate($domain, $output, $test);
        
        if ($result) {
            $success = 'Certificado emitido com sucesso!';
            // Atualiza a lista de certificados
            $certificates = $certificado->listCertificates();
        } else {
            $error = 'Erro ao emitir certificado: ' . implode("\n", $output);
        }
    }
}

// Obtém a lista de certificados existentes
$certificates = method_exists($certificado, 'listCertificates')
    ? $certificado->listCertificates()
    : [];

// Inicia a saída HTML
llxHeader('', 'Emissor de Certificados ACME');

// CSS personalizado
?>
<style>
    /* Layout principal */
    .acme-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    /* Cabeçalho */
    .acme-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 15px 20px;
        margin: -20px -20px 20px -20px;
    }
    
    .acme-header h2 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }
    
    /* Cartões */
    .acme-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .acme-card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 10px 15px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .acme-card-body {
        padding: 20px;
    }
    
    /* Formulário */
    .acme-form-group {
        margin-bottom: 15px;
    }
    
    .acme-form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .acme-form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }
    
    /* Botões */
    .acme-btn {
        display: inline-block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        user-select: none;
        border: 1px solid transparent;
        padding: 8px 16px;
        font-size: 14px;
        line-height: 1.5;
        border-radius: 4px;
        transition: all 0.15s ease-in-out;
        cursor: pointer;
    }
    
    .acme-btn-primary {
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .acme-btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
    }
    
    .acme-btn-secondary {
        color: #fff;
        background-color: #6c757d;
        border-color: #6c757d;
    }
    
    .acme-btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }
    
    /* Mensagens */
    .acme-alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    
    .acme-alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    
    .acme-alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    
    /* Tabela de certificados */
    .acme-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .acme-table th,
    .acme-table td {
        padding: 12px 15px;
        border: 1px solid #dee2e6;
        text-align: left;
    }
    
    .acme-table thead th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    
    .acme-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    /* Badges */
    .acme-badge {
        display: inline-block;
        padding: 3px 7px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        border-radius: 10px;
    }
    
    .acme-badge-success {
        color: #fff;
        background-color: #28a745;
    }
    
    .acme-badge-warning {
        color: #212529;
        background-color: #ffc107;
    }
    
    .acme-badge-danger {
        color: #fff;
        background-color: #dc3545;
    }
</style>

<div class="acme-container">
    <!-- Cabeçalho -->
    <div class="acme-header">
        <h2><i class="fa fa-lock"></i> Emissor de Certificados ACME</h2>
    </div>
    
    <!-- Mensagens de sucesso/erro -->
    <?php if ($success): ?>
        <div class="acme-alert acme-alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="acme-alert acme-alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Formulário de emissão de certificado -->
    <div class="acme-card">
        <div class="acme-card-header">
            <span>Emitir Novo Certificado</span>
        </div>
        <div class="acme-card-body">
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="acme-form">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <input type="hidden" name="action" value="issue_certificate">
                
                <div class="acme-form-group">
                    <label for="domain">Domínio Principal *</label>
                    <input type="text" id="domain" name="domain" class="acme-form-control" 
                           placeholder="exemplo.com.br" required>
                </div>
                
                <div class="acme-form-group">
                    <label for="email">E-mail para notificações</label>
                    <input type="email" id="email" name="email" class="acme-form-control" 
                           placeholder="admin@exemplo.com.br" value="<?php echo $conf->global->MAIN_MAIL_EMAIL_FROM; ?>">
                </div>
                
                <div class="acme-form-group">
                    <label>
                        <input type="checkbox" name="test" value="1" checked> Usar servidor de teste (não gera certificados reais)
                    </label>
                </div>
                
                <div class="acme-form-group">
                    <button type="submit" class="acme-btn acme-btn-primary">
                        <i class="fa fa-certificate"></i> Emitir Certificado
                    </button>
                    <a href="setup_acme.php" class="acme-btn acme-btn-secondary">
                        <i class="fa fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de certificados existentes -->
    <div class="acme-card">
        <div class="acme-card-header">
            <span>Certificados Existentes</span>
        </div>
        <div class="acme-card-body">
            <?php if (empty($certificates)): ?>
                <p>Nenhum certificado encontrado.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="acme-table">
                        <thead>
                            <tr>
                                <th>Domínio</th>
                                <th>Válido até</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $cert): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['domain']); ?></strong><br>
                                        <small class="text-muted">Emitido em: <?php echo dol_print_date($cert['issue_date'], '%d/%m/%Y'); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $expiryDate = strtotime($cert['expiry_date']);
                                        $daysLeft = floor(($expiryDate - time()) / (60 * 60 * 24));
                                        
                                        if ($daysLeft > 30) {
                                            $badgeClass = 'acme-badge-success';
                                        } elseif ($daysLeft > 7) {
                                            $badgeClass = 'acme-badge-warning';
                                        } else {
                                            $badgeClass = 'acme-badge-danger';
                                        }
                                        ?>
                                        <span class="acme-badge <?php echo $badgeClass; ?>">
                                            <?php echo dol_print_date($expiryDate, '%d/%m/%Y'); ?>
                                            (<?php echo $daysLeft; ?> dias)
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cert['valid']): ?>
                                            <span class="acme-badge acme-badge-success">Válido</span>
                                        <?php else: ?>
                                            <span class="acme-badge acme-badge-danger">Inválido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="acme-btn acme-btn-secondary" style="padding: 3px 8px; font-size: 12px;">
                                            <i class="fa fa-download"></i> Baixar
                                        </button>
                                        <button class="acme-btn acme-btn-secondary" style="padding: 3px 8px; font-size: 12px;">
                                            <i class="fa fa-sync"></i> Renovar
                                        </button>
                                        <button class="acme-btn" style="padding: 3px 8px; font-size: 12px; background-color: #dc3545; color: white; border-color: #dc3545;">
                                            <i class="fa fa-trash"></i> Remover
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Informações sobre o ACME -->
    <div class="acme-card">
        <div class="acme-card-header">
            <span>Informações sobre o ACME</span>
        </div>
        <div class="acme-card-body">
            <p>O protocolo ACME (Automatic Certificate Management Environment) é usado para automatizar a emissão e renovação de certificados SSL/TLS.</p>
            <p>Ao emitir um certificado, você concorda com os <a href="https://letsencrypt.org/repository/" target="_blank">Termos de Serviço do Let's Encrypt</a>.</p>
            <p><strong>Limitações:</strong></p>
            <ul>
                <li>Limite de 50 certificados por domínio por semana</li>
                <li>Limite de 5 certificados idênticos por semana</li>
                <li>Certificados válidos por 90 dias</li>
            </ul>
        </div>
    </div>
</div>

<?php
// Finaliza a saída HTML
llxFooter();
