#!/bin/bash

# Diretório base
dolibarr_dir="/var/www/html/dolibarr-develop"
output_dir="/tmp/dolibarr_packages"

# Função para obter a versão do módulo
get_module_version() {
    local module_dir="$1"
    local module_name="$2"
    
    # Verifica se existe um arquivo de classe do módulo
    local module_file=$(find "$module_dir" -name "mod${module_name}.class.php" | head -1)
    
    if [ -f "$module_file" ]; then
        # Extrai a versão do arquivo da classe do módulo
        local version=$(grep -oP "this\->version\s*=\s*'[0-9.]+'" "$module_file" | grep -oP "[0-9.]+\.[0-9]+" | head -1)
        if [ -n "$version" ]; then
            echo "$version"
            return 0
        fi
    fi
    
    # Se não encontrou, retorna uma versão padrão
    echo "1.0.0"
}

# Função para criar o pacote de um módulo
create_module_package() {
    local module_name="$1"
    local module_dir="$dolibarr_dir/htdocs/custom/$module_name"
    
    if [ ! -d "$module_dir" ]; then
        echo "Diretório do módulo $module_name não encontrado em $module_dir"
        return 1
    fi
    
    # Obtém a versão do módulo
    local version=$(get_module_version "$module_dir" "${module_name^}")
    
    # Cria o diretório temporário para o pacote
    local temp_dir="/tmp/${module_name}_package"
    rm -rf "$temp_dir"
    
    # Cria a estrutura de diretórios necessária
    mkdir -p "$temp_dir/$module_name"
    
    # Copia os arquivos do módulo para o diretório temporário
    cp -r "$module_dir"/* "$temp_dir/$module_name/"
    
    # Cria o arquivo .norescan se não existir
    touch "$temp_dir/$module_name/.norescan"
    
    # Para o módulo webcamfoto, garante que a estrutura esteja correta
    if [ "$module_name" = "webcamfoto" ]; then
        # Cria a estrutura htdocs se não existir
        mkdir -p "$temp_dir/htdocs/$module_name"
        
        # Move os arquivos públicos para a pasta htdocs
        # Mantém uma cópia na raiz para compatibilidade
        cp -r "$temp_dir/$module_name/" "$temp_dir/htdocs/"
    fi
    
    # Cria o diretório de saída se não existir
    mkdir -p "$output_dir"
    
    # Cria o pacote ZIP
    cd "$temp_dir"
    
    # Adiciona todos os diretórios necessários ao ZIP
    if [ -d "htdocs" ]; then
        zip -r "$output_dir/${module_name}-${version}.zip" "htdocs"
    fi
    
    # Adiciona a pasta do módulo na raiz
    zip -r "$output_dir/${module_name}-${version}.zip" "$module_name"
    
    # Volta para o diretório original
    cd - > /dev/null
    
    echo "Pacote criado: $output_dir/${module_name}-${version}.zip"
    
    # Remove o diretório temporário
    rm -rf "$temp_dir"
}

# Cria os pacotes para cada módulo
create_module_package "certificado"
create_module_package "restore"
create_module_package "serviceordergeo"
create_module_package "webcamfoto"

echo "\nPacotes criados em: $output_dir"
ls -lh "$output_dir/"
