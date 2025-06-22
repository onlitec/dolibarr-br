<?php
/*
 * Actions class for Certificado module
 */

class ActionsCertificado
{
    /** @var DoliDB Database handler */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ... outros métodos ...

    /**
     * Instala o cliente ACME.sh via script oficial
     * @param array<string> &$output saída de comandos
     * @return bool
     */
    public function installAcmeSh(array &$output): bool
    {
        dol_syslog("Iniciando instalação do ACME.sh", LOG_DEBUG);
        // Instalar em diretório de dados do Dolibarr (gravável)
        $installDir = DOL_DATA_ROOT . '/acme.sh';
        // Garante que o diretório exista e seja gravável
        if (!is_dir($installDir)) {
            if (!@mkdir($installDir, 0755, true)) {
                $output[] = "Erro: não foi possível criar diretório $installDir";
                return false;
            }
        }
        if (!is_writable($installDir)) {
            $output[] = "Erro: diretório sem permissão de escrita: $installDir";
            return false;
        }
        $scriptUrl = 'https://get.acme.sh';
        // Define HOME e INSTALL_HOME para que o acme.sh instale no diretório correto
        $cmd = sprintf(
            'export HOME=%s INSTALL_HOME=%s && curl -sS %s | sh',
            escapeshellarg($installDir),
            escapeshellarg($installDir),
            escapeshellarg($scriptUrl)
        );
        dol_syslog("Executando comando: $cmd", LOG_DEBUG);
        exec($cmd . ' 2>&1', $output, $ret);
        dol_syslog("Resultado do comando (código: $ret): " . print_r($output, true), LOG_DEBUG);
        // Verifica se o script acme.sh foi instalado no subdiretório .acme.sh
        $installedScript = $installDir . '/.acme.sh/acme.sh';
        if (!file_exists($installedScript)) {
            $output[] = "Erro: script acme.sh não encontrado em $installedScript";
            return false;
        }
        return $ret === 0;
    }

    /**
     * Emite um certificado ACME para o domínio especificado
     */
    public function issueCertificate(string $domain, array &$output = [], bool $testMode = true): bool
    {
        global $conf, $user;
        
        // Validação do domínio
        if (empty($domain)) {
            $output[] = "Erro: Domínio não especificado";
            return false;
        }
        
        // Verifica se o acme.sh foi instalado no diretório de dados
        $installDir = DOL_DATA_ROOT . '/acme.sh';
        $acmePath = $installDir . '/.acme.sh/acme.sh';
        if (!file_exists($acmePath)) {
            $output[] = "Erro: ACME.sh não encontrado em $acmePath";
            return false;
        }
        
        // Prepara o comando
        $email = !empty($conf->global->CERTIFICADO_LE_EMAIL) 
            ? $conf->global->CERTIFICADO_LE_EMAIL 
            : $conf->global->MAIN_MAIL_EMAIL_FROM;
        
        $webrootPath = DOL_DOCUMENT_ROOT . '/..';
        $certDir = DOL_DATA_ROOT . '/certificates/' . $domain;
        $server = $testMode ? 'letsencrypt_test' : 'letsencrypt';
        
        // Cria o diretório de certificados se não existir
        if (!file_exists($certDir)) {
            if (!dol_mkdir($certDir)) {
                $output[] = "Erro ao criar diretório de certificados: $certDir";
                return false;
            }
        }
        
        // Comando para emitir o certificado
        $cmd = sprintf(
            'export HOME=%s && %s --issue -d %s -w %s --keylength ec-256 --server %s --force',
            escapeshellarg($installDir),
            escapeshellarg($acmePath),
            escapeshellarg($domain),
            escapeshellarg($webrootPath),
            $server
        );
        
        if (!empty($email)) {
            $cmd .= ' --email ' . escapeshellarg($email);
        }
        
        // Executa o comando
        exec($cmd . ' 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0) {
            $output[] = "Erro ao emitir certificado (código: $returnVar)";
            return false;
        }
        
        // Instala o certificado
        $installCmd = sprintf(
            'export HOME=%s && %s --install-cert -d %s --cert-file %s --key-file %s --fullchain-file %s --reloadcmd "service apache2 reload"',
            escapeshellarg($installDir),
            escapeshellarg($acmePath),
            escapeshellarg($domain),
            escapeshellarg("$certDir/cert.pem"),
            escapeshellarg("$certDir/key.pem"),
            escapeshellarg("$certDir/fullchain.pem")
        );
        
        exec($installCmd . ' 2>&1', $installOutput, $installReturnVar);
        $output = array_merge($output, $installOutput);
        
        if ($installReturnVar !== 0) {
            $output[] = "Erro ao instalar certificado (código: $installReturnVar)";
            return false;
        }
        
        // Atualiza o banco de dados
        return $this->saveCertificateInfo($domain, $certDir);
    }

    /**
     * Lista todos os certificados emitidos
     */
    public function listCertificates(): array
    {
        global $conf;
        
        $certificates = [];
        $certDir = DOL_DATA_ROOT . '/certificates';
        
        if (!is_dir($certDir)) {
            return [];
        }
        
        // Lista os diretórios de certificados
        $domains = array_filter(glob($certDir . '/*'), 'is_dir');
        
        foreach ($domains as $domainDir) {
            $domain = basename($domainDir);
            $certFile = "$domainDir/cert.pem";
            
            if (file_exists($certFile)) {
                $certData = openssl_x509_parse(file_get_contents($certFile));
                $validTo = $certData['validTo_time_t'] ?? 0;
                
                $certificates[] = [
                    'domain' => $domain,
                    'path' => $domainDir,
                    'issue_date' => $certData['validFrom_time_t'] ?? time(),
                    'expiry_date' => date('Y-m-d H:i:s', $validTo),
                    'valid' => $validTo > time(),
                    'issuer' => $certData['issuer']['O'] ?? 'Unknown',
                    'serial' => $certData['serialNumberHex'] ?? '',
                    'files' => [
                        'cert' => "$domainDir/cert.pem",
                        'key' => "$domainDir/key.pem",
                        'fullchain' => "$domainDir/fullchain.pem"
                    ]
                ];
            }
        }
        
        // Ordena por data de expiração (mais recente primeiro)
        usort($certificates, function($a, $b) {
            return $b['issue_date'] - $a['issue_date'];
        });
        
        return $certificates;
    }

    /**
     * Salva informações do certificado no banco de dados
     */
    private function saveCertificateInfo(string $domain, string $certDir): bool
    {
        global $conf, $user;
        
        $now = dol_now();
        $certFile = "$certDir/cert.pem";
        
        if (!file_exists($certFile)) {
            return false;
        }
        
        $certData = openssl_x509_parse(file_get_contents($certFile));
        $validFrom = $certData['validFrom_time_t'] ?? time();
        $validTo = $certData['validTo_time_t'] ?? strtotime('+90 days');
        
        // Cria a tabela se não existir
        $this->createDatabaseTables();
        
        // Verifica se já existe registro
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "certificado_ssl 
                WHERE domain = '" . $this->db->escape($domain) . "'";
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            
            if ($obj) {
                // Atualiza registro existente
                $sql = "UPDATE " . MAIN_DB_PREFIX . "certificado_ssl SET
                        issue_date = '" . $this->db->idate($validFrom) . "',
                        expiry_date = '" . $this->db->idate($validTo) . "',
                        cert_path = '" . $this->db->escape($certDir) . "',
                        date_modification = '" . $this->db->idate($now) . "',
                        fk_user_modif = " . $user->id . "
                        WHERE domain = '" . $this->db->escape($domain) . "'";
            } else {
                // Insere novo registro
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "certificado_ssl
                        (domain, issue_date, expiry_date, cert_path, date_creation, fk_user_creat)
                        VALUES ('" . $this->db->escape($domain) . "', '" . 
                        $this->db->idate($validFrom) . "', '" . $this->db->idate($validTo) . "', '" . 
                        $this->db->escape($certDir) . "', '" . $this->db->idate($now) . "', " . $user->id . ")";
            }
            
            $resql = $this->db->query($sql);
            return $resql !== false;
        }
        
        return false;
    }

    /**
     * Cria a tabela no banco de dados se não existir
     */
    private function createDatabaseTables(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "certificado_ssl (
            rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
            domain VARCHAR(255) NOT NULL,
            issue_date DATETIME NOT NULL,
            expiry_date DATETIME NOT NULL,
            cert_path VARCHAR(255) NOT NULL,
            date_creation DATETIME NOT NULL,
            date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fk_user_creat INTEGER NOT NULL,
            fk_user_modif INTEGER,
            UNIQUE KEY idx_certificado_ssl_domain (domain)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $this->db->query($sql) !== false;
    }

    /**
     * Interpreta a saída do acme.sh para fornecer feedback ao usuário.
     * @param array $output O array de saída do comando exec.
     * @param bool $success O status de sucesso do comando.
     * @return array Um array com 'message' (string) e 'type' ('errors' ou 'mesgs').
     */
    public function getFormattedAcmeOutput(array $output, bool $success): array
    {
        $outputText = implode("\n", $output);

        if ($success) {
            return [
                'type' => 'mesgs',
                'message' => 'Operação com o certificado concluída com sucesso.'
            ];
        }

        // Erro de verificação HTTP-01 (404 Not Found)
        if (strpos($outputText, 'Invalid response from') !== false && strpos($outputText, ': 404') !== false) {
            preg_match('/http:\/\/(.*?)\/\.well-known/', $outputText, $matches);
            $domain = $matches[1] ?? 'seu domínio';
            $webroot = DOL_DOCUMENT_ROOT . '/..';

            $message = "<b>Erro de Verificação de Domínio (HTTP-01)</b><br>" .
                       "O servidor da Let's Encrypt não conseguiu validar o seu domínio porque não encontrou o arquivo de teste. Ele tentou acessar uma URL em `http://{$domain}/.well-known/acme-challenge/...` mas recebeu um erro '404 - Not Found'.<br><br>" .
                       "<b>Possíveis Causas e Soluções:</b>" .
                       "<ul>" .
                       "<li><b>Apontamento de DNS:</b> Verifique se o domínio <code>{$domain}</code> está apontando corretamente para o IP deste servidor.</li>" .
                       "<li><b>Caminho do Webroot:</b> O caminho raiz (webroot) usado foi <code>{$webroot}</code>. Confirme se este é o diretório público do seu site. Se o Dolibarr estiver em um subdiretório, este caminho pode estar incorreto.</li>" .
                       "<li><b>Configuração do Servidor Web:</b> Certifique-se que seu Apache ou Nginx está configurado para permitir o acesso a diretórios ocultos como <code>.well-known</code>.</li>" .
                       "<li><b>Firewall:</b> Garanta que a porta 80 está aberta para tráfego externo.</li>" .
                       "</ul>" .
                       "<b>Log Técnico:</b><br><pre style='white-space: pre-wrap; word-wrap: break-word; background-color: #f5f5f5; border: 1px solid #ccc; padding: 10px; border-radius: 4px;'>" . htmlspecialchars($outputText, ENT_QUOTES) . "</pre>";
            
            return ['type' => 'errors', 'message' => $message];
        }

        // Erro de Permissão
        if (strpos($outputText, 'Permission denied') !== false) {
             $message = "<b>Erro de Permissão</b><br>" .
                        "O script `acme.sh` não teve permissão para criar ou escrever em um arquivo ou diretório. Isso geralmente acontece no diretório de dados.<br><br>" .
                        "<b>Solução:</b><br>" .
                        "Verifique se o usuário do servidor web (ex: `www-data` ou `apache`) tem permissões de escrita no diretório: <code>" . DOL_DATA_ROOT . "/acme.sh/</code> e em todos os seus subdiretórios.<br><br>" .
                        "<b>Log Técnico:</b><br><pre style='white-space: pre-wrap; word-wrap: break-word; background-color: #f5f5f5; border: 1px solid #ccc; padding: 10px; border-radius: 4px;'>" . htmlspecialchars($outputText, ENT_QUOTES) . "</pre>";
            return ['type' => 'errors', 'message' => $message];
        }

        // Erro genérico
        $message = "<b>Ocorreu um erro desconhecido durante a operação.</b><br>" .
                   "Analise o log técnico abaixo para mais detalhes.<br><br>" .
                   "<b>Log Técnico:</b><br><pre style='white-space: pre-wrap; word-wrap: break-word; background-color: #f5f5f5; border: 1px solid #ccc; padding: 10px; border-radius: 4px;'>" . htmlspecialchars($outputText, ENT_QUOTES) . "</pre>";

        return ['type' => 'errors', 'message' => $message];
    }
}