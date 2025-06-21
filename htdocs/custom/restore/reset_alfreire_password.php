<?php
/**
 * reset_alfreire_password.php
 *
 * Script para redefinir a senha do usuário 'alfreire' para 'M3a74g20M'.
 * Execute via web ou CLI (php reset_alfreire_password.php).
 */
// Carrega ambiente Dolibarr
require_once __DIR__ . '/../../main.inc.php';
// Verifica permissão de admin
if (empty($user->admin)) {
    echo "Acesso negado: é necessário privilégio de administrador.\n";
    exit(1);
}
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$login = 'alfreire';
$newPwd = 'M3a74g20M';

$u = new User($db);
if ($u->fetch(0, $login) <= 0) {
    echo "Usuário '{$login}' não encontrado.\n";
    exit(1);
}

$res = $u->setPassword($user, $newPwd);
if (is_int($res) && $res >= 0) {
    echo "Senha do usuário '{$login}' redefinida para '{$newPwd}'.\n";
    exit(0);
} else {
    $err = $u->error ?: 'erro desconhecido';
    echo "Falha ao redefinir senha de '{$login}': {$err}\n";
    exit(1);
}
