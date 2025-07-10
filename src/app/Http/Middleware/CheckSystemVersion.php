<?php

namespace App\Http\Middleware;

use App\Models\Config;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class CheckSystemVersion
{
    /**
     * 处理传入的请求，检查系统版本并在需要时执行更新
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 如果正在访问安装页面，直接通过
        if ($request->path() == 'install') {
            // 如果系统已安装并且配置有效，显示已安装提示
            if ($this->isSystemInstalled()) {
                return response('对不起，你已完成安装！如需重新安装，请删除 根目录/src/config/mysql.php 文件', 200);
            }
            return $next($request);
        }

        // 检查系统是否已安装
        if (!$this->isSystemInstalled()) {
            return redirect('/install');
        }

        // 系统已安装，尝试检查版本并更新
        try {
            // 尝试连接数据库
            if (\DB::connection()->getPdo()) {
                // 手动运行SQL更新脚本，确保表结构正确
                $this->runUpdateScripts();
                
                // 检查configs表是否存在
                $this->checkAndFixConfigsTable();
                
                // 执行版本检查和更新
                Config::checkAndUpdateVersion();
            }
        } catch (\Exception $e) {
            Log::error('System version check failed: ' . $e->getMessage());
        }

        return $next($request);
    }
    
    /**
     * 手动运行SQL更新脚本
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
                            } catch (\Exception $e) {
                                // 忽略已存在的表错误
                                if (strpos($e->getMessage(), '1050') === false) { // 1050是表已存在错误
                                    Log::warning("SQL statement error: " . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    Log::info("Successfully applied update script: " . $script);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to run update scripts: ' . $e->getMessage());
        }
    }

    /**
     * 检查系统是否已安装
     * 
     * @return bool
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
        try {
            // 使用配置尝试连接数据库
            $dsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['database']};port={$mysqlConfig['port']}";
            $pdo = new \PDO($dsn, $mysqlConfig['username'], $mysqlConfig['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // 验证数据库表是否存在
            $prefix = $mysqlConfig['prefix'];
            $result = $pdo->query("SHOW TABLES LIKE '{$prefix}configs'");
            
            return $result->rowCount() > 0;
        } catch (\Exception $e) {
            Log::error('Database connection check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查并修复configs表
     */
    private function checkAndFixConfigsTable()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            
            // 检查各种可能的表名情况
            $possibleTables = [
                'configs',                // 无前缀
                $prefix . 'configs',      // 正确前缀
                $prefix . $prefix . 'configs' // 重复前缀
            ];
            
            $existingTables = [];
            foreach ($possibleTables as $table) {
                if (\Schema::hasTable($table)) {
                    $existingTables[] = $table;
                }
            }
            
            // 处理表名问题
            if (count($existingTables) > 0) {
                // 选择正确的表名作为主表
                $mainTable = $prefix . 'configs';
                
                // 如果正确表名不存在但其他表名存在
                if (!in_array($mainTable, $existingTables)) {
                    $sourceTable = $existingTables[0]; // 使用第一个存在的表
                    DB::statement("RENAME TABLE `{$sourceTable}` TO `{$mainTable}`");
                    Log::info("Renamed table from {$sourceTable} to {$mainTable}");
                }
                // 如果有多个表存在，保留正确的表，删除其他表
                else if (count($existingTables) > 1) {
                    foreach ($existingTables as $table) {
                        if ($table != $mainTable) {
                            DB::statement("DROP TABLE IF EXISTS `{$table}`");
                            Log::info("Dropped duplicate table: {$table}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Fix configs table failed: ' . $e->getMessage());
        }
    }
} 