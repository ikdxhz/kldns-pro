<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/15
 * Time: 13:22
 * 
 * @package    App\Http\Middleware
 * @author     ｉｋｄｘｈｚ
 * @maintainer ⓘⓚⓓⓧⓗⓩ v3.1.3
 */

namespace App\Http\Middleware;


use App\Http\Controllers\InstallController;
use App\Models\Model;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 加载系统配置中间件
 * 
 * @author     𝕚𝕜𝕕𝕩𝕙𝕫
 * @version    3.1.3
 */
class LoadSysConfig
{
    /**
     * 处理请求
     * 
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @author ⓘⓚⓓⓧⓗⓩ
     */
    public function handle(Request $request, Closure $next)
    {
        //设置信任代理IP来源
        Request::setTrustedProxies(['100.0.0.0/8'], Request::HEADER_X_FORWARDED_FOR);
        $uri = $request->getRequestUri();
        if ($uri === '/install' || $uri === '/install/') {
            // 安装页面，跳过配置加载
        } else {
            // 检查数据库连接
            if ($this->checkDatabaseConnection()) {
                // 先修复表前缀问题，再执行更新
                $this->checkAndFixTablePrefix();
                
                $c = new InstallController();
                $c->update();//更新数据库
                
                // 加载系统配置
                $this->loadSysConfig($request);
            } else if ($uri !== '/') {
                // 如果数据库连接失败且不是首页，重定向到安装页面
                return redirect('/install');
            }
        }
        return $next($request);
    }
    
    /**
     * 检查数据库连接
     * 
     * @return bool
     * @developer ¡kdxhž
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::error('Database connection failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 检查并修复表前缀问题
     * 
     * @author i​k​d​x​h​z
     * @version 3.1.3
     */
    private function checkAndFixTablePrefix()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            
            // 获取所有表，不使用Schema避免前缀问题
            $tablesResult = DB::select("SHOW TABLES");
            $allTables = [];
            
            // 转换为一维数组
            foreach ($tablesResult as $table) {
                $tableName = array_values((array)$table)[0];
                $allTables[] = $tableName;
            }
            
            // 需要检查的表基本名称
            $baseTableNames = [
                'configs', 
                'dns_configs', 
                'domains', 
                'domain_records', 
                'users',
                'user_groups'
            ];
            
            // 检查并修复每个表
            foreach ($baseTableNames as $baseTable) {
                // 计算可能的表名
                $correctTable = $prefix . $baseTable;
                $doublePrefix = $prefix . $prefix . $baseTable; 
                $noPrefix = $baseTable;
                
                // 如果错误的双前缀表存在
                if (in_array($doublePrefix, $allTables)) {
                    // 如果正确表名也存在
                    if (in_array($correctTable, $allTables)) {
                        // 尝试合并数据
                        try {
                            DB::statement("INSERT IGNORE INTO `{$correctTable}` SELECT * FROM `{$doublePrefix}`");
                            Log::info("Merged data from {$doublePrefix} to {$correctTable}");
                        } catch (\Exception $e) {
                            Log::warning("Failed to merge data: " . $e->getMessage());
                        }
                        
                        // 删除错误表
                        DB::statement("DROP TABLE IF EXISTS `{$doublePrefix}`");
                        Log::info("Dropped duplicate table: {$doublePrefix}");
                    } 
                    // 如果正确表名不存在，重命名错误表
                    else {
                        DB::statement("RENAME TABLE `{$doublePrefix}` TO `{$correctTable}`");
                        Log::info("Renamed table from {$doublePrefix} to {$correctTable}");
                    }
                }
                
                // 检查无前缀的表是否存在，但是正确前缀表不存在
                if (in_array($noPrefix, $allTables) && !in_array($correctTable, $allTables)) {
                    DB::statement("RENAME TABLE `{$noPrefix}` TO `{$correctTable}`");
                    Log::info("Added prefix: renamed {$noPrefix} to {$correctTable}");
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to check/fix table prefix: ' . $e->getMessage());
        }
    }

    /**
     * 加载系统配置
     * 
     * @param Request $request
     * @author ïkðxhz
     * @version 3.1.3
     */
    private function loadSysConfig($request)
    {
        try {
            // 直接使用正确的表名，不依赖Model::safeTable
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $tableName = $prefix . 'configs';
            
            // 先确认表是否存在
            $tableExists = DB::select("SHOW TABLES LIKE '{$tableName}'");
            
            if (empty($tableExists)) {
                Log::error("Failed to load system config: Table '{$tableName}' does not exist");
                return;
            }
            
            // 直接使用DB查询，避免模型带来的表前缀问题
            $configs = DB::table($tableName)->get();
            
            $_configs = [];
            foreach ($configs as $config) {
                if (substr($config->k, 0, 6) === 'array_') {
                    $_configs[substr($config->k, 6)] = json_decode($config->v, true);
                } else {
                    $_configs[$config->k] = $config->v;
                }
            }
            \config(['sys' => $_configs]);
        } catch (\Exception $e) {
            Log::error('Failed to load system config: ' . $e->getMessage());
        }
    }
}