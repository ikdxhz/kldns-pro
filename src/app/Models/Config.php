<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:33
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Config extends Model
{
    protected $primaryKey = 'k';
    public $incrementing = false;
    protected $guarded = [];
    const SYSTEM_VERSION = 'v3.1.3'; // 当前系统版本
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // 直接设置完整表名，防止前缀被重复添加
        $prefix = config('database.connections.mysql.prefix', 'kldns_');
        $this->setTable($prefix . 'configs');
        
        // 修复表名问题（只在实例化时运行一次）
        $this->fixTablePrefixIssue();
    }
    
    /**
     * 手动设置表名（覆盖默认行为）
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }
    
    /**
     * 获取表名（覆盖默认行为）
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * 检查并修复可能错误创建的表名前缀重复问题
     */
    protected function fixTablePrefixIssue()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $correctTable = $prefix . 'configs';
            $wrongTable = $prefix . $prefix . 'configs';
            
            // 检查错误表名是否存在
            $tableExists = false;
            try {
                $tableExists = DB::select("SHOW TABLES LIKE '{$wrongTable}'");
            } catch (\Exception $e) {
                Log::error("Failed to check if table exists: " . $e->getMessage());
                return;
            }
            
            if (!empty($tableExists)) {
                try {
                    // 检查正确表名是否存在
                    $correctTableExists = DB::select("SHOW TABLES LIKE '{$correctTable}'");
                    
                    if (empty($correctTableExists)) {
                        // 如果正确表名不存在，重命名错误表名
                        DB::statement("RENAME TABLE `{$wrongTable}` TO `{$correctTable}`");
                        Log::info("Fixed table name: renamed {$wrongTable} to {$correctTable}");
                    } else {
                        // 如果两个表都存在，合并数据后删除错误表
                        DB::statement("INSERT IGNORE INTO `{$correctTable}` SELECT * FROM `{$wrongTable}`");
                        DB::statement("DROP TABLE `{$wrongTable}`");
                        Log::info("Merged data from {$wrongTable} to {$correctTable} and dropped the duplicate table");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to fix table: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fix table prefix: " . $e->getMessage());
        }
    }
    
    /**
     * 获取系统当前版本（静态方法）
     */
    public static function getVersion()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $tableName = $prefix . 'configs';
            
            // 直接使用DB查询而不是模型，以避免前缀问题
            $version = DB::table($tableName)->where('k', 'system_version')->first();
            
            return $version ? $version->v : 'v3.0.0'; // 默认为初始版本
        } catch (\Exception $e) {
            Log::error("Failed to get version: " . $e->getMessage());
            return 'v3.0.0'; // 出错时返回默认版本
        }
    }
    
    /**
     * 检查并更新系统版本
     */
    public static function checkAndUpdateVersion()
    {
        try {
            $currentVersion = self::getVersion();
            
            // 如果当前版本低于系统版本，执行更新
            if (version_compare($currentVersion, self::SYSTEM_VERSION, '<')) {
                $updates = self::getUpdateScripts($currentVersion, self::SYSTEM_VERSION);
                $prefix = config('database.connections.mysql.prefix', 'kldns_');
                
                foreach ($updates as $version => $script) {
                    if (self::executeUpdateScript($script)) {
                        // 更新系统版本记录
                        DB::table($prefix . 'configs')->updateOrInsert(
                            ['k' => 'system_version'],
                            ['v' => $version]
                        );
                    }
                }
                
                // 最后更新到当前版本
                DB::table($prefix . 'configs')->updateOrInsert(
                    ['k' => 'system_version'],
                    ['v' => self::SYSTEM_VERSION]
                );
                
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to check/update version: " . $e->getMessage());
            return false;
        }
    }
    
    // 获取需要执行的更新脚本
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
    
    // 执行更新脚本
    private static function executeUpdateScript($scriptFile)
    {
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