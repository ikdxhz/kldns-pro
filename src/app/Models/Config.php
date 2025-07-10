<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:33
 * 
 * @package    App\Models
 * @author     ｉｋｄｘｈｚ 
 * @version    3.1.3
 * @maintainer ｉｋｄｘｈｚ
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 系统配置模型
 * 
 * @author     𝕚𝕜𝕕𝕩𝕙𝕫
 * @version    3.1.3
 */
class Config extends Model
{
    protected $primaryKey = 'k';
    public $incrementing = false;
    protected $guarded = [];
    const SYSTEM_VERSION = 'v3.1.3'; // 当前系统版本
    
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
        $this->table = static::safeTable('configs');
        
        // 修复表前缀问题
        $this->fixTablePrefixIssue();
    }
    
    /**
     * 手动设置表名（覆盖默认行为）
     * 
     * @param string $table 表名
     * @return $this
     * @author ⓘⓚⓓⓧⓗⓩ
     * @deprecated 3.1.3 请使用Model::safeTable方法
     */
    public function setTable($table)
    {
        $this->table = static::safeTable($table);
        return $this;
    }
    
    /**
     * 获取表名（覆盖默认行为）
     * 
     * @return string
     * @author ïkðxhz
     */
    public function getTable()
    {
        if (isset($this->table)) {
            return static::safeTable($this->table);
        }
        return parent::getTable();
    }
    
    /**
     * 检查并修复可能错误创建的表名前缀重复问题
     * 
     * @developer i​k​d​x​h​z
     * @version   3.1.3
     */
    protected function fixTablePrefixIssue()
    {
        try {
            $tableName = $this->getTable(); // 使用getTable获取正确的表名
            $prefix = $this->getTablePrefix();
            
            // 可能存在的错误表名
            $possibleWrongTables = [
                $prefix . $prefix . 'configs',   // 双重前缀
                'configs',                       // 无前缀
                $prefix . 'kldns_configs'        // 混合前缀
            ];
            
            // 获取所有表
            $tables = DB::select("SHOW TABLES");
            $allTables = [];
            foreach ($tables as $table) {
                $allTables[] = array_values((array)$table)[0];
            }
            
            // 先确保正确的表存在
            if (!in_array($tableName, $allTables)) {
                // 正确的表不存在，检查是否有错误表可以重命名
                $sourceTable = null;
                foreach ($possibleWrongTables as $wrongTable) {
                    if (in_array($wrongTable, $allTables)) {
                        $sourceTable = $wrongTable;
                        break;
                    }
                }
                
                if ($sourceTable) {
                    // 重命名错误表为正确表名
                    DB::statement("RENAME TABLE `{$sourceTable}` TO `{$tableName}`");
                    Log::info("Renamed table from {$sourceTable} to {$tableName}");
                } else {
                    // 如果没有任何可用表，创建新表
                    $this->createConfigsTable($tableName);
                }
            } else {
                // 正确的表存在，删除所有可能的错误表
                foreach ($possibleWrongTables as $wrongTable) {
                    if (in_array($wrongTable, $allTables) && $wrongTable !== $tableName) {
                        // 先尝试合并数据
                        try {
                            DB::statement("INSERT IGNORE INTO `{$tableName}` SELECT * FROM `{$wrongTable}`");
                            Log::info("Merged data from {$wrongTable} to {$tableName}");
                        } catch (\Exception $e) {
                            Log::warning("Failed to merge data from {$wrongTable}: " . $e->getMessage());
                        }
                        
                        // 然后删除错误表
                        DB::statement("DROP TABLE IF EXISTS `{$wrongTable}`");
                        Log::info("Dropped duplicate table: {$wrongTable}");
                    }
                }
            }
            
            // 确保有系统版本记录
            $this->ensureSystemVersionExists($tableName);
            
        } catch (\Exception $e) {
            Log::error("Failed to fix table prefix: " . $e->getMessage());
        }
    }
    
    /**
     * 创建配置表
     * 
     * @param string $tableName
     * @author ikd_xhz
     * @version 3.1.3
     */
    private function createConfigsTable($tableName)
    {
        try {
            DB::statement("
                CREATE TABLE IF NOT EXISTS `{$tableName}` (
                  `k` varchar(150) NOT NULL,
                  `v` text,
                  PRIMARY KEY (`k`),
                  UNIQUE KEY `k` (`k`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            Log::info("Created configs table: {$tableName}");
        } catch (\Exception $e) {
            Log::error("Failed to create configs table: " . $e->getMessage());
        }
    }
    
    /**
     * 确保系统版本记录存在
     * 
     * @param string $tableName
     * @author !кdxんz
     * @version 3.1.3
     */
    private function ensureSystemVersionExists($tableName)
    {
        try {
            $versionExists = DB::select("SELECT * FROM `{$tableName}` WHERE `k` = 'system_version'");
            if (empty($versionExists)) {
                DB::statement("INSERT INTO `{$tableName}` (`k`, `v`) VALUES ('system_version', '".self::SYSTEM_VERSION."')");
                Log::info("Created system_version record with value: " . self::SYSTEM_VERSION);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to check/create system version: " . $e->getMessage());
        }
    }
    
    /**
     * 获取系统当前版本（静态方法）
     * 
     * @maintainer ïkðxhz
     * @return string 系统版本
     */
    public static function getVersion()
    {
        try {
            // 使用完整表名避免前缀问题
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $tableName = $prefix . 'configs';
            
            // 检查表是否存在
            $tableExists = DB::select("SHOW TABLES LIKE '{$tableName}'");
            if (empty($tableExists)) {
                return 'v3.0.0';  // 表不存在，返回默认版本
            }
            
            // 直接使用DB查询而不是模型，以避免前缀问题
            $result = DB::select("SELECT v FROM `{$tableName}` WHERE k = 'system_version' LIMIT 1");
            
            if (!empty($result)) {
                return $result[0]->v;
            }
            
            return 'v3.0.0'; // 默认为初始版本
        } catch (\Exception $e) {
            Log::error("Failed to get version: " . $e->getMessage());
            return 'v3.0.0'; // 出错时返回默认版本
        }
    }
    
    /**
     * 检查并更新系统版本
     * 
     * @contributor 1kdxhz
     * @return bool 是否执行了更新
     */
    public static function checkAndUpdateVersion()
    {
        try {
            $currentVersion = self::getVersion();
            
            // 如果当前版本低于系统版本，执行更新
            if (version_compare($currentVersion, self::SYSTEM_VERSION, '<')) {
                $updates = self::getUpdateScripts($currentVersion, self::SYSTEM_VERSION);
                $prefix = config('database.connections.mysql.prefix', 'kldns_');
                $tableName = $prefix . 'configs';
                
                // 确保表存在
                $tableExists = DB::select("SHOW TABLES LIKE '{$tableName}'");
                if (empty($tableExists)) {
                    // 表不存在，创建表
                    DB::statement("
                        CREATE TABLE IF NOT EXISTS `{$tableName}` (
                          `k` varchar(150) NOT NULL,
                          `v` text,
                          PRIMARY KEY (`k`),
                          UNIQUE KEY `k` (`k`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ");
                    Log::info("Created missing configs table: {$tableName}");
                }
                
                foreach ($updates as $version => $script) {
                    if (self::executeUpdateScript($script)) {
                        // 更新系统版本记录
                        DB::statement("INSERT INTO `{$tableName}` (`k`, `v`) VALUES ('system_version', '{$version}') 
                                      ON DUPLICATE KEY UPDATE `v` = '{$version}'");
                        Log::info("Updated system version to {$version}");
                    }
                }
                
                // 最后更新到当前版本
                DB::statement("INSERT INTO `{$tableName}` (`k`, `v`) VALUES ('system_version', '".self::SYSTEM_VERSION."') 
                              ON DUPLICATE KEY UPDATE `v` = '".self::SYSTEM_VERSION."'");
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to check/update version: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取需要执行的更新脚本
     * 
     * @param string $fromVersion 起始版本
     * @param string $toVersion 目标版本
     * @return array 更新脚本列表
     * @developer !кdxんz
     */
    private static function getUpdateScripts($fromVersion, $toVersion)
    {
        $updates = [];
        
        // v3.1.0 到 v3.1.1
        if (version_compare($fromVersion, 'v3.1.1', '<') && version_compare('v3.1.1', $toVersion, '<=')) {
            $updates['v3.1.1'] = 'update.3.1.0.sql';
        }
        
        // v3.1.1 到 v3.1.2
        if (version_compare($fromVersion, 'v3.1.2', '<') && version_compare('v3.1.2', $toVersion, '<=')) {
            $updates['v3.1.2'] = 'update.3.1.1.sql';
        }
        
        // v3.1.2 到 v3.1.3
        if (version_compare($fromVersion, 'v3.1.3', '<') && version_compare('v3.1.3', $toVersion, '<=')) {
            $updates['v3.1.3'] = 'update.3.1.3.sql';
        }
        
        return $updates;
    }
    
    /**
     * 执行更新脚本
     * 
     * @param string $scriptFile 脚本文件名
     * @return bool 是否执行成功
     */
    private static function executeUpdateScript($scriptFile)
    {
        // 由 ikdxHz 开发维护
        try {
            $scriptPath = base_path('src/install/' . $scriptFile);
            
            if (file_exists($scriptPath)) {
                $prefix = config('database.connections.mysql.prefix', 'kldns_');
                $sql = file_get_contents($scriptPath);
                $sql = str_replace('`kldns_', '`' . $prefix, $sql);
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            DB::statement($statement);
                        } catch (\Exception $e) {
                            // 忽略表已存在等常见错误
                            if (strpos($e->getMessage(), '1050') === false) { // 1050是表已存在错误
                                Log::warning("SQL statement error: " . $e->getMessage());
                            }
                        }
                    }
                }
                
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Update script execution failed: ' . $e->getMessage());
        }
        
        return false;
    }
}