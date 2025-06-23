-- Upgrade script 1.1.0 for Serviceordergeo module
-- Adiciona colunas de geolocalização e cálculo de custos em fichinter e propal

ALTER TABLE `llx_fichinter`
  ADD COLUMN `origin_address` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `latitude` DOUBLE DEFAULT NULL,
  ADD COLUMN `longitude` DOUBLE DEFAULT NULL,
  ADD COLUMN `fuel_price` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `fuel_consumption` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `other_costs` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `distance_km` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `travel_cost` DECIMAL(10,2) DEFAULT NULL;

ALTER TABLE `llx_propal`
  ADD COLUMN `origin_address` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `latitude` DOUBLE DEFAULT NULL,
  ADD COLUMN `longitude` DOUBLE DEFAULT NULL,
  ADD COLUMN `fuel_price` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `fuel_consumption` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `other_costs` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `distance_km` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `travel_cost` DECIMAL(10,2) DEFAULT NULL;
