<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:34
 * 
 * @package    App\Models
 * @author     ｉｋｄｘｈｚ
 * @version    3.1.3
 * @maintainer ⓘⓚⓓⓧⓗⓩ
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DNS配置模型
 * 
 * @author     𝕚𝕜𝕕𝕩𝕙𝕫
 * @version    3.1.3
 */
class DnsConfig extends Model
{
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    
    /**
     * 构造函数
     * 
     * 优化: 防止表前缀重复问题
     * @developer ¡kdxhž
     * @version   3.1.3
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // 设置表名，使用safeTable方法确保前缀正确
        $this->table = static::safeTable('dns_configs');
        
        // 修复表结构问题
        $this->fixTableStructure();
    }
    
    /**
     * 修复表结构问题
     * 
     * @author i​k​d​x​h​z
     * @version 3.1.3
     */
    protected function fixTableStructure()
    {
        try {
            $tableName = $this->getTable(); // 使用getTable方法获取正确的表名
            
            // 检查表是否存在
            $tableExists = DB::select("SHOW TABLES LIKE '{$tableName}'");
            if (empty($tableExists)) {
                // 创建表
                DB::statement("
                    CREATE TABLE IF NOT EXISTS `{$tableName}` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(150) NOT NULL DEFAULT '',
                      `dns` varchar(150) NOT NULL,
                      `config` varchar(1024) DEFAULT NULL,
                      `created_at` int(10) unsigned DEFAULT NULL,
                      `updated_at` int(10) unsigned DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `name_dns` (`name`, `dns`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                ");
                Log::info("Created missing table: {$tableName}");
            } else {
                // 检查name字段是否存在
                try {
                    $nameColumnExists = DB::select("SHOW COLUMNS FROM `{$tableName}` LIKE 'name'");
                    if (empty($nameColumnExists)) {
                        DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `name` varchar(150) NOT NULL DEFAULT CONCAT(dns, '-默认配置') AFTER `id`");
                        DB::statement("UPDATE `{$tableName}` SET `name` = CONCAT(dns, '-默认配置') WHERE `name` = '' OR `name` IS NULL");
                        Log::info("Added missing name column to {$tableName}");
                    }
                    
                    // 检查id字段是否为主键
                    $primaryKeyExists = DB::select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
                    if (empty($primaryKeyExists)) {
                        DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)");
                        Log::info("Added missing primary key to {$tableName}");
                    }
                    
                    // 检查name_dns唯一索引
                    $uniqueKeyExists = DB::select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'name_dns'");
                    if (empty($uniqueKeyExists)) {
                        DB::statement("ALTER TABLE `{$tableName}` ADD UNIQUE KEY `name_dns` (`name`, `dns`)");
                        Log::info("Added missing unique key to {$tableName}");
                    }
                } catch (\Exception $e) {
                    Log::warning("Error checking/fixing columns: " . $e->getMessage());
                }
            }
            
            // 处理可能存在的旧结构问题 - 由 !kdxんz 添加
            try {
                // 如果dns是主键但应该是id
                $primaryKeyInfo = DB::select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
                if (!empty($primaryKeyInfo) && $primaryKeyInfo[0]->Column_name === 'dns') {
                    // 创建临时表
                    $tempTable = $tableName . '_temp';
                    DB::statement("CREATE TABLE `{$tempTable}` LIKE `{$tableName}`");
                    DB::statement("ALTER TABLE `{$tempTable}` DROP PRIMARY KEY, ADD COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)");
                    DB::statement("INSERT INTO `{$tempTable}` (`dns`, `name`, `config`, `created_at`, `updated_at`) SELECT `dns`, IFNULL(`name`, CONCAT(`dns`, '-默认配置')) as `name`, `config`, `created_at`, `updated_at` FROM `{$tableName}`");
                    DB::statement("DROP TABLE `{$tableName}`");
                    DB::statement("RENAME TABLE `{$tempTable}` TO `{$tableName}`");
                    Log::info("Fixed table structure: {$tableName} - changed primary key from dns to id");
                }
            } catch (\Exception $e) {
                Log::warning("Error fixing primary key structure: " . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to fix DNS configs table structure: " . $e->getMessage());
        }
    }

    /**
     * 获取配置属性
     * 
     * @return array
     * @author ïkðxhz
     */
    public function getConfigAttribute()
    {
        $value = json_decode($this->attributes['config'], true);
        return $value ? $value : [];
    }
}