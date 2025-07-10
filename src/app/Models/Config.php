<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:33
 */

namespace App\Models;


class Config extends Model
{
    protected $primaryKey = 'k';
    public $incrementing = false;
    protected $guarded = [];
    const SYSTEM_VERSION = 'v3.1.3'; // 当前系统版本
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // 修复: 防止表前缀重复添加
        $prefix = config('database.connections.mysql.prefix', 'kldns_');
        $this->table = $prefix . 'configs';
        
        // 兼容处理: 检查并修复可能错误创建的表
        $this->fixTablePrefix();
    }
    
    /**
     * 检查并修复可能错误创建的表名前缀重复问题
     */
    protected function fixTablePrefix()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $wrongTable = $prefix . $prefix . 'configs';
            $correctTable = $prefix . 'configs';
            
            // 检查是否存在错误表名
            if (\Schema::hasTable($wrongTable) && !\Schema::hasTable($correctTable)) {
                // 重命名表
                \DB::statement("RENAME TABLE `{$wrongTable}` TO `{$correctTable}`");
                \Log::info("Fixed table name: renamed {$wrongTable} to {$correctTable}");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to fix table prefix: " . $e->getMessage());
        }
    }
    
    // 获取系统当前版本
    public static function getVersion()
    {
        $version = self::where('k', 'system_version')->first();
        return $version ? $version->v : 'v3.0.0'; // 默认为初始版本
    }
    
    // 检查并更新系统版本
    public static function checkAndUpdateVersion()
    {
        $currentVersion = self::getVersion();
        
        // 如果当前版本低于系统版本，执行更新
        if (version_compare($currentVersion, self::SYSTEM_VERSION, '<')) {
            $updates = self::getUpdateScripts($currentVersion, self::SYSTEM_VERSION);
            
            foreach ($updates as $version => $script) {
                if (self::executeUpdateScript($script)) {
                    // 更新系统版本记录
                    self::updateOrCreate(['k' => 'system_version'], ['v' => $version]);
                }
            }
            
            // 最后更新到当前版本
            self::updateOrCreate(['k' => 'system_version'], ['v' => self::SYSTEM_VERSION]);
            
            return true;
        }
        
        return false;
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
        
        return $updates;
    }
    
    // 执行更新脚本
    private static function executeUpdateScript($scriptFile)
    {
        try {
            $scriptPath = base_path('src/install/' . $scriptFile);
            
            if (file_exists($scriptPath)) {
                $sql = file_get_contents($scriptPath);
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        \DB::statement($statement);
                    }
                }
                
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Update script execution failed: ' . $e->getMessage());
        }
        
        return false;
    }
}