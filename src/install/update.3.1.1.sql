-- 检查表是否存在，不存在则创建
CREATE TABLE IF NOT EXISTS `kldns_dns_configs` (
  `dns` varchar(150) NOT NULL,
  `config` varchar(1024) DEFAULT NULL,
  `created_at` int(10) unsigned DEFAULT NULL,
  `updated_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`dns`)
);

-- 添加临时表来保存旧数据
CREATE TABLE IF NOT EXISTS `kldns_dns_configs_temp` (
  `dns` varchar(150) NOT NULL,
  `config` varchar(1024) DEFAULT NULL,
  `created_at` int(10) unsigned DEFAULT NULL,
  `updated_at` int(10) unsigned DEFAULT NULL
);

-- 复制现有数据到临时表
INSERT INTO `kldns_dns_configs_temp` SELECT * FROM `kldns_dns_configs`;

-- 删除原表并重建
DROP TABLE IF EXISTS `kldns_dns_configs`;

-- 创建新表结构
CREATE TABLE `kldns_dns_configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `dns` varchar(150) NOT NULL,
  `config` varchar(1024) DEFAULT NULL,
  `created_at` int(10) unsigned DEFAULT NULL,
  `updated_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_dns` (`name`, `dns`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 将数据恢复到新表，添加默认名称
INSERT INTO `kldns_dns_configs` (`name`, `dns`, `config`, `created_at`, `updated_at`)
SELECT CONCAT(dns, '-默认配置'), dns, config, created_at, updated_at FROM `kldns_dns_configs_temp`;

-- 删除临时表
DROP TABLE IF EXISTS `kldns_dns_configs_temp`;

-- 为domains表添加dns_config_id字段，如果不存在
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists FROM information_schema.columns 
WHERE table_name = 'kldns_domains' AND column_name = 'dns_config_id' AND table_schema = DATABASE();

SET @query = IF(@column_exists = 0, 
  'ALTER TABLE `kldns_domains` ADD COLUMN `dns_config_id` int(10) unsigned DEFAULT NULL AFTER `dns`', 
  'SELECT "Column dns_config_id already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 更新域名表中的关联ID
UPDATE `kldns_domains` d
JOIN `kldns_dns_configs` c ON d.dns = c.dns
SET d.dns_config_id = c.id
WHERE d.dns_config_id IS NULL; 