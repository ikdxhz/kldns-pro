<?php
/**
 * 系统版本检查和自动升级中间件
 * 
 * 自动检查并维护系统版本，确保数据库结构正确
 *
 * @package     App\Http\Middleware
 * @author      ｉｋｄｘｈｚ
 * @maintainer  ⓘⓚⓓⓧⓗⓩ
 * @version     3.1.3
 */

namespace App\Http\Middleware;

use App\Models\Config;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

/**
 * 系统版本检查中间件
 * 
 * @author ⓘⓚⓓⓧⓗⓩ 
 * @version 3.1.3
 * @developer 𝕚𝕜𝕕𝕩𝕙𝕫
 */
class CheckSystemVersion
{
    /**
     * 处理传入的请求，检查系统版本并在需要时执行更新
     * 
     * 开发维护: 𝓲𝓴𝓭𝔁𝓱𝔃
     * @version 3.1.3
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 首页和静态资源请求不做安装检查，防止循环重定向
        $uri = $request->path();
        $skipPaths = ['/', 'js', 'css', 'images', 'fonts'];
        $isStaticRequest = false;
        
        // 判断是否为静态资源请求
        foreach ($skipPaths as $path) {
            if ($uri === $path || strpos($uri, $path.'/') === 0) {
                $isStaticRequest = true;
                break;
            }
        }
        
        // 如果正在访问安装页面，直接通过
        if ($uri == 'install') {
            // 如果系统已安装并且配置有效，显示已安装提示
            if ($this->isSystemInstalled()) {
                return response('对不起，你已完成安装！如需重新安装，请删除 根目录/src/config/mysql.php 文件', 200);
            }
            return $next($request);
        }

        // 对于静态资源请求，直接通过
        if ($isStaticRequest) {
            return $next($request);
        }
        
        // 检查系统是否已安装，未安装则重定向到安装页面
        // 由 !kdxんz 添加: 防止循环重定向
        $installCheckCookie = $request->cookie('install_check');
        if (!$this->isSystemInstalled() && $installCheckCookie !== 'checked') {
            // 设置cookie标记，防止循环重定向
            return redirect('/install')->cookie('install_check', 'checked', 1);
        }

        // 系统已安装，尝试检查版本并更新
        try {
            // 先运行数据库修复脚本
            if (\DB::connection()->getPdo()) {
                // 先检查并修复表结构
                $this->checkAndFixConfigsTable();
                
                // 然后运行SQL更新脚本
                $this->runUpdateScripts();
                
                // 最后检查和更新版本
                try {
                    \App\Models\Config::checkAndUpdateVersion();
                } catch (\Exception $e) {
                    Log::error('CheckSystemVersion: Version check failed: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('CheckSystemVersion: System check failed: ' . $e->getMessage());
        }

        return $next($request);
    }
    
    /**
     * 手动运行SQL更新脚本
     * 
     * @author i​k​d​x​h​z
     * @version 3.1.3
     */
    private function runUpdateScripts()
    {
        try {
            $updateScripts = [
                'update.3.1.3.sql'  // 运行最新的修复脚本
            ];
            
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            
            foreach ($updateScripts as $script) {
                $scriptPath = base_path('src/install/' . $script);
                
                if (file_exists($scriptPath)) {
                    $sql = file_get_contents($scriptPath);
                    $sql = str_replace('`kldns_', '`' . $prefix, $sql);
                    
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    
                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            try {
                                DB::unprepared($statement);
                                Log::debug("CheckSystemVersion: Successfully executed SQL statement");
                            } catch (\Exception $e) {
                                // 忽略特定错误
                                if (strpos($e->getMessage(), '1050') === false &&  // 表已存在
                                    strpos($e->getMessage(), '1060') === false &&  // 列已存在
                                    strpos($e->getMessage(), '1061') === false) {  // 键已存在
                                    Log::warning("CheckSystemVersion: SQL statement error: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    Log::info("CheckSystemVersion: Successfully applied update script: " . $script);
                } else {
                    Log::warning("CheckSystemVersion: Update script not found: " . $scriptPath);
                }
            }
        } catch (\Exception $e) {
            Log::error('CheckSystemVersion: Failed to run update scripts: ' . $e->getMessage());
        }
    }

    /**
     * 检查系统是否已安装
     * 
     * @return bool
     * @author ｉｋｄｘｈｚ
     * @version 3.1.3
     */
    private function isSystemInstalled()
    {
        // 检查mysql.php配置文件是否存在且有效
        if (!file_exists(config_path('mysql.php'))) {
            return false;
        }

        // 加载mysql配置
        $mysqlConfig = include config_path('mysql.php');
        if (!is_array($mysqlConfig) || 
            empty($mysqlConfig['host']) || 
            empty($mysqlConfig['database']) || 
            empty($mysqlConfig['username'])) {
            return false;
        }
        
        // 检查.env文件中的数据库配置
        $dbDatabase = env('DB_DATABASE');
        $dbUsername = env('DB_USERNAME');
        
        // 如果.env文件有数据库配置，说明系统已安装
        if (!empty($dbDatabase) && !empty($dbUsername)) {
            return true;
        }
        
        // 否则使用mysql.php配置文件进行检查
        // 由 ikd_xhz 开发维护
        try {
            // 使用配置尝试连接数据库
            $dsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['database']};port={$mysqlConfig['port']}";
            $pdo = new \PDO($dsn, $mysqlConfig['username'], $mysqlConfig['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // 验证关键表是否存在
            $prefix = $mysqlConfig['prefix'];
            $tablesCount = 0;
            
            // 检查几个关键表是否存在
            $keyTables = ['configs', 'dns_configs', 'domains'];
            foreach ($keyTables as $table) {
                $result = $pdo->query("SHOW TABLES LIKE '{$prefix}{$table}'");
                if ($result && $result->rowCount() > 0) {
                    $tablesCount++;
                }
            }
            
            // 如果至少有两个关键表存在，认为系统已安装
            if ($tablesCount >= 2) {
                return true;
            } else {
                Log::warning("CheckSystemVersion: System appears partially installed. Found {$tablesCount} of 3 required tables.");
                return false;
            }
        } catch (\Exception $e) {
            Log::error('CheckSystemVersion: Database connection check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查并修复configs表
     * 
     * @developer 1kdxhz
     * @version   3.1.3
     */
    private function checkAndFixConfigsTable()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            
            // 获取所有表
            $tables = DB::select("SHOW TABLES");
            $allTables = [];
            foreach ($tables as $table) {
                $allTables[] = array_values((array)$table)[0];
            }
            
            // 检查各种可能的表名情况
            $correctTable = $prefix . 'configs';
            $wrongTables = [
                'configs',                     // 无前缀
                $prefix . $prefix . 'configs'  // 重复前缀
            ];
            
            // 如果正确表名不存在
            if (!in_array($correctTable, $allTables)) {
                // 查找可能的错误表名
                $sourceTable = null;
                foreach ($wrongTables as $wrongTable) {
                    if (in_array($wrongTable, $allTables)) {
                        $sourceTable = $wrongTable;
                        break;
                    }
                }
                
                if ($sourceTable) {
                    // 重命名错误表为正确表名
                    DB::statement("RENAME TABLE `{$sourceTable}` TO `{$correctTable}`");
                    Log::info("CheckSystemVersion: Renamed table from {$sourceTable} to {$correctTable}");
                } else {
                    // 如果没有可用表，创建新表
                    DB::statement("
                        CREATE TABLE IF NOT EXISTS `{$correctTable}` (
                          `k` varchar(150) NOT NULL,
                          `v` text,
                          PRIMARY KEY (`k`),
                          UNIQUE KEY `k` (`k`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    ");
                    Log::info("CheckSystemVersion: Created missing table: {$correctTable}");
                    
                    // 初始化系统版本
                    DB::statement("INSERT INTO `{$correctTable}` (`k`, `v`) VALUES ('system_version', 'v3.1.3')");
                }
            } else {
                // 正确表名存在，检查并删除错误表
                foreach ($wrongTables as $wrongTable) {
                    if (in_array($wrongTable, $allTables)) {
                        // 先合并数据
                        try {
                            DB::statement("INSERT IGNORE INTO `{$correctTable}` SELECT * FROM `{$wrongTable}`");
                            Log::info("CheckSystemVersion: Merged data from {$wrongTable} to {$correctTable}");
                        } catch (\Exception $e) {
                            Log::warning("CheckSystemVersion: Failed to merge data: " . $e->getMessage());
                        }
                        
                        // 删除错误表
                        DB::statement("DROP TABLE IF EXISTS `{$wrongTable}`");
                        Log::info("CheckSystemVersion: Dropped duplicate table: {$wrongTable}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Fix configs table failed: ' . $e->getMessage());
        }
    }
} 