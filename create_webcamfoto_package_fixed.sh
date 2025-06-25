#!/bin/bash

# Diretório base
dolibarr_dir="/var/www/html/dolibarr-develop"
output_dir="$HOME/dolibarr_packages"
module_name="webcamfoto"

# Cria o diretório de saída se não existir
mkdir -p "$output_dir"

# Caminho para o diretório do módulo
module_dir="$dolibarr_dir/htdocs/custom/$module_name"

# Verifica se o diretório do módulo existe
if [ ! -d "$module_dir" ]; then
    echo "Erro: Diretório do módulo $module_name não encontrado em $module_dir"
    exit 1
fi

# Obtém a versão do módulo
version=$(grep -oP "this->version\s*=\s*'[0-9.]+'" "$module_dir/core/modules/mod${module_name^}.class.php" | grep -oP "[0-9.]+\.[0-9]+" | head -1)
if [ -z "$version" ]; then
    version="1.0.0"
    echo "Aviso: Versão não encontrada, usando versão padrão: $version"
fi

# Cria diretório temporário
temp_dir="/tmp/${module_name}_package"
rm -rf "$temp_dir"
mkdir -p "$temp_dir"

# Cria a estrutura de diretórios necessária
mkdir -p "$temp_dir/$module_name"

# Copia os arquivos para a estrutura correta
cp -r "$module_dir/"* "$temp_dir/$module_name/" 2>/dev/null

# Cria o arquivo .norescan
touch "$temp_dir/$module_name/.norescan"

# Cria o arquivo .newmodule
touch "$temp_dir/$module_name/.newmodule"

# Cria o pacote ZIP
cd "$temp_dir"
zip -r "$output_dir/${module_name}-${version}.zip" "$module_name"

# Volta para o diretório original
cd - > /dev/null

# Remove o diretório temporário
rm -rf "$temp_dir"

echo "\nPacote criado com sucesso em: $output_dir/${module_name}-${version}.zip"
ls -lh "$output_dir/${module_name}-${version}.zip"

echo "\nPara instalar o módulo no Dolibarr:"
echo "1. Acesse o Dolibarr como administrador"
echo "2. Vá em Configuração > Módulos/APIs"
echo "3. Role até a seção 'Outros módulos'"
echo "4. Clique em 'Carregar módulo/ferramenta externa'"
echo "5. Selecione o arquivo: $output_dir/${module_name}-${version}.zip"
echo "6. Siga as instruções na tela para concluir a instalação"
