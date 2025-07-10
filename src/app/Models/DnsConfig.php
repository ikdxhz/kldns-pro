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
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DNS配置模型
 * 
 * @author     𝕚𝕜𝕕𝕩𝕙𝕫
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
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // 直接设置完整表名，防止前缀被重复添加
        $prefix = config('database.connections.mysql.prefix', 'kldns_');
        $this->setTable($prefix . 'dns_configs');
        
        // 修复表结构问题
        $this->fixTableStructure();
    }
    
    /**
     * 修复表结构问题
     * 
     * @author i​k​d​x​h​z
     */
    protected function fixTableStructure()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $tableName = $prefix . 'dns_configs';
            
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
                $nameColumnExists = DB::select("SHOW COLUMNS FROM `{$tableName}` LIKE 'name'");
                if (empty($nameColumnExists)) {
                    DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `name` varchar(150) NOT NULL DEFAULT CONCAT(dns, '-默认配置') AFTER `id`");
                    DB::statement("UPDATE `{$tableName}` SET `name` = CONCAT(dns, '-默认配置') WHERE `name` = '' OR `name` IS NULL");
                    Log::info("Added missing name column to {$tableName}");
                }
            }
            
            // 检查并删除可能存在的重复前缀表
            $wrongTable = $prefix . $prefix . 'dns_configs';
            $wrongTableExists = DB::select("SHOW TABLES LIKE '{$wrongTable}'");
            if (!empty($wrongTableExists)) {
                DB::statement("DROP TABLE IF EXISTS `{$wrongTable}`");
                Log::info("Dropped duplicate table: {$wrongTable}");
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