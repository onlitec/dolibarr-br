-- Upgrade script 1.0.0 for Serviceordergeo module
-- Adiciona campos de geolocalização e cálculo de custos

ALTER TABLE `llx_commande`
  ADD COLUMN `origin_address` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `destination_address` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `latitude` DOUBLE DEFAULT NULL,
  ADD COLUMN `longitude` DOUBLE DEFAULT NULL,
  ADD COLUMN `fuel_price` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `fuel_consumption` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `other_costs` DECIMAL(10,2) DEFAULT NULL;

ALTER TABLE `llx_facture`
  ADD COLUMN `distance_km` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN `travel_cost` DECIMAL(10,2) DEFAULT NULL;

-- Cria tabela llx_categorie_invoice se não existir, para suporte a categorias em faturas
CREATE TABLE IF NOT EXISTS `llx_categorie_invoice` (
  `fk_categorie` INT(11) NOT NULL,
  `fk_invoice`   INT(11) NOT NULL,
  `import_key`   VARCHAR(14) DEFAULT NULL,
  PRIMARY KEY (`fk_categorie`,`fk_invoice`),
  KEY `idx_categorie_invoice_fk_categorie` (`fk_categorie`),
  KEY `idx_categorie_invoice_fk_invoice`   (`fk_invoice`),
  CONSTRAINT `fk_categorie_invoice_categorie_rowid` FOREIGN KEY (`fk_categorie`) REFERENCES `llx_categorie` (`rowid`),
  CONSTRAINT `fk_categorie_invoice_fk_invoice_rowid` FOREIGN KEY (`fk_invoice`)   REFERENCES `llx_facture` (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 