# Service Order Geolocation & Cost Calculation Module

Versão: 1.0.0

## Descrição
Este módulo para Dolibarr adiciona geolocalização e cálculo de custo por km em Ordens de Serviço e Faturas.

## Instalação

1. Copie a pasta `custom/serviceordergeo` para `htdocs/custom/` no Dolibarr.
2. No Dolibarr, vá em **Configurações > Módulos**, localize **Serviceordergeo** e clique em **Instalar**.
3. Confirme a execução do script SQL de upgrade.

## Configuração

- Acesse **Setup > Geolocation & Cost** para inserir configurações do módulo.
- Defina provedores de Geocoding (OpenStreetMap/Nominatim) e chaves se necessário.
- Ajuste valores padrão de combustível e parâmetros de custo.

## Uso

1. Crie ou edite uma Ordem de Serviço. Um novo campo **Endereço de Destino** aparecerá.
2. Ao sair do campo de endereço, a geolocalização (lat/lng) será obtida automaticamente.
3. Gere a fatura; distância e custo de deslocamento aparecerão no cartão e PDF.

## Requisitos

- Dolibarr 14.0+  
- PHP >= 7.4 com cURL e JSON  

## Traduções

- Inglês (en_GB) e Português do Brasil (pt_BR) inclusos.

## Licença
GPL v3 