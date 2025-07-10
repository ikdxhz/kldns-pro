-- 修复表前缀重复问题
-- 由 ⓘⓚⓓⓧⓗⓩ 开发维护 v3.1.3

-- 1. 检查并删除可能存在的重复前缀表
-- kldns_kldns_configs 表处理
DROP TABLE IF EXISTS `kldns_kldns_configs`;
DROP TABLE IF EXISTS `kldns_kldns_dns_configs`;
DROP TABLE IF EXISTS `kldns_kldns_domains`;
DROP TABLE IF EXISTS `kldns_kldns_domain_records`;
DROP TABLE IF EXISTS `kldns_kldns_users`;
DROP TABLE IF EXISTS `kldns_kldns_user_groups`;

-- 2. 确保configs表结构正确
CREATE TABLE IF NOT EXISTS `kldns_configs` (
  `k` varchar(150) NOT NULL,
  `v` text,
  PRIMARY KEY (`k`),
  UNIQUE KEY `k` (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 3. 确保dns_configs表结构正确
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

-- 4. 检查name字段是否存在，不存在则添加
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists FROM information_schema.columns 
WHERE table_name = 'kldns_dns_configs' AND column_name = 'name' AND table_schema = DATABASE();

SET @query = IF(@column_exists = 0, 
  'ALTER TABLE `kldns_dns_configs` ADD COLUMN `name` varchar(150) NOT NULL DEFAULT CONCAT(dns, "-默认配置") AFTER `id`', 
  'SELECT "Column name already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. 确保name值不为空
UPDATE `kldns_dns_configs` SET `name` = CONCAT(dns, '-默认配置') WHERE `name` = '' OR `name` IS NULL;

-- 6. 检查是否存在唯一键，不存在则添加
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics
WHERE table_name = 'kldns_dns_configs' AND index_name = 'name_dns' AND table_schema = DATABASE();

SET @query = IF(@index_exists = 0,
  'ALTER TABLE `kldns_dns_configs` ADD UNIQUE KEY `name_dns` (`name`, `dns`)',
  'SELECT "Index name_dns already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. 如果dns是主键，修改为id为主键
SET @has_primary_dns = 0;
SELECT COUNT(*) INTO @has_primary_dns FROM information_schema.key_column_usage
WHERE table_name = 'kldns_dns_configs' AND column_name = 'dns' AND constraint_name = 'PRIMARY' AND table_schema = DATABASE();

SET @query = IF(@has_primary_dns > 0,
  'ALTER TABLE `kldns_dns_configs` DROP PRIMARY KEY, ADD COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)',
  'SELECT "Primary key is not on dns column"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. 为domains表添加dns_config_id字段，如果不存在
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists FROM information_schema.columns 
WHERE table_name = 'kldns_domains' AND column_name = 'dns_config_id' AND table_schema = DATABASE();

SET @query = IF(@column_exists = 0, 
  'ALTER TABLE `kldns_domains` ADD COLUMN `dns_config_id` int(10) unsigned DEFAULT NULL AFTER `dns`', 
  'SELECT "Column dns_config_id already exists"');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 9. 更新域名表中的关联ID
UPDATE `kldns_domains` d
JOIN `kldns_dns_configs` c ON d.dns = c.dns
SET d.dns_config_id = c.id
WHERE d.dns_config_id IS NULL;

-- 10. 确保系统版本记录存在
INSERT INTO `kldns_configs` (`k`, `v`) VALUES ('system_version', 'v3.1.3')
ON DUPLICATE KEY UPDATE `v` = 'v3.1.3'; 