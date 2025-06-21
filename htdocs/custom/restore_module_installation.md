# Módulo Smart Restore

Este documento descreve o procedimento de instalação do módulo de restauração inteligente do Dolibarr.

## Conteúdo do pacote

- **restore/**                Pasta com os scripts do módulo
- **restore_module.tar.gz**   Pacote compactado do módulo
- **restore_module_installation.md**  Este documento de instalação

## Pré-requisitos

- Dolibarr 22.0.0-beta ou superior
- PHP 8.3+ com extensão Zip habilitada
- Acesso administrativo ao Dolibarr e permissão de escrita em `custom/restore`
- Permissão de escrita na pasta de documentos (`DOL_DATA_ROOT`)

## Passo a passo de instalação

1. Faça upload de `restore_module.tar.gz` para a pasta `htdocs/custom/` do seu Dolibarr.
2. No servidor ou via FTP, navegue até `htdocs/custom` e execute:
   ```bash
   tar -xzf restore_module.tar.gz
   ```
3. Verifique que foi criada a pasta `htdocs/custom/restore` contendo:
   - `restore_smart.php`
   - `langs/pt_BR/restore.lang`
   - Demais recursos do módulo
4. Ajuste permissões:
   ```bash
   chmod -R 750 htdocs/custom/restore
   ```
5. No Dolibarr, acesse **Configuração → Parâmetros avançados → Servidor** (ou **Outros → Configuração global**) e ajuste:
   - **Diretório temporário de upload** (parâmetro `RESTORE_TEMP_DIR`) para um caminho válido e gravável
   - Verifique se `DOL_DATA_ROOT` aponta para a pasta de documentos correta
6. Para usar o módulo, abra no navegador:
   ```
   https://<seu_dominio>/custom/restore/restore_smart.php
   ```
7. Siga as instruções na tela para analisar e executar a restauração de banco de dados e arquivos.

## Observações

- Após restaurar o banco, a senha do usuário `alfreire` será resetada para `M3a74g20M`.
- Se a versão do backup não coincidir com a versão instalada, o módulo redirecionará automaticamente para a página de migração de banco do Dolibarr.

---
Salve este documento para referência futura.
