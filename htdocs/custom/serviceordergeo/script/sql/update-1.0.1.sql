-- Upgrade script 1.0.1 for Serviceordergeo module
-- Adiciona campos de tipo de serviço e endereço de serviço para intervenções e propostas

ALTER TABLE `llx_fichinter`
  ADD COLUMN `service_type` VARCHAR(20) NOT NULL DEFAULT 'internal',
  ADD COLUMN `service_address` VARCHAR(255) DEFAULT NULL;

ALTER TABLE `llx_propal`
  ADD COLUMN `service_type` VARCHAR(20) NOT NULL DEFAULT 'internal',
  ADD COLUMN `service_address` VARCHAR(255) DEFAULT NULL; 