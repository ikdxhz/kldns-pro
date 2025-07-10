-- 修复dns_configs表结构，确保表存在并有name字段

-- 检查表是否存在，不存在则创建
CREATE TABLE IF NOT EXISTS `kldns_dns_configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `dns` varchar(150) NOT NULL,
  `config` varchar(1024) DEFAULT NULL,
  `created_at` int(10) unsigned DEFAULT NULL,
  `updated_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_dns` (`name`, `dns`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 检查name字段是否存在，不存在则添加
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists FROM information_schema.columns 
WHERE table_name = 'kldns_dns_configs' AND column_name = 'name' AND table_schema = DATABASE();

SET @query = IF(@column_exists = 0, 
  'ALTER TABLE `kldns_dns_configs` ADD COLUMN `name` varchar(150) NOT NULL DEFAULT CONCAT(dns, "-默认配置") AFTER `id`, ADD UNIQUE KEY `name_dns` (`name`, `dns`)', 
  'SELECT "Column name already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查id字段是否为主键，不是则修改
SET @has_id = 0;
SELECT COUNT(*) INTO @has_id FROM information_schema.columns 
WHERE table_name = 'kldns_dns_configs' AND column_name = 'id' AND table_schema = DATABASE();

SET @has_primary = 0;
SELECT COUNT(*) INTO @has_primary FROM information_schema.table_constraints
WHERE table_name = 'kldns_dns_configs' AND constraint_type = 'PRIMARY KEY' AND table_schema = DATABASE();

SET @query = IF(@has_id = 0 AND @has_primary = 0, 
  'ALTER TABLE `kldns_dns_configs` DROP PRIMARY KEY, 
   ADD COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT FIRST, 
   ADD PRIMARY KEY (`id`)', 
  'SELECT "Table structure is already correct"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 如果表中有数据但没有name值，则更新name字段
UPDATE `kldns_dns_configs` SET `name` = CONCAT(dns, "-默认配置") WHERE `name` = '' OR `name` IS NULL;

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