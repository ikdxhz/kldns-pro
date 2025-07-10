<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/15
 * Time: 13:22
 */

namespace App\Http\Middleware;


use App\Http\Controllers\InstallController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LoadSysConfig
{

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
                $c = new InstallController();
                $c->update();//更新数据库
                
                // 检查并修复表前缀问题
                $this->checkAndFixTablePrefix();
                
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
     * @return bool
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
     */
    private function checkAndFixTablePrefix()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $correctTable = $prefix . 'configs';
            $wrongTable = $prefix . $prefix . 'configs';
            
            // 使用原生SQL检查表是否存在，避免Schema前缀问题
            $tables = DB::select("SHOW TABLES");
            $tablesArray = array_map(function($table) {
                return array_values((array)$table)[0]; 
            }, $tables);
            
            // 如果错误表名存在
            if (in_array($wrongTable, $tablesArray)) {
                // 如果正确表名也存在，删除错误表
                if (in_array($correctTable, $tablesArray)) {
                    // 尝试先合并数据
                    try {
                        DB::statement("INSERT IGNORE INTO `{$correctTable}` SELECT * FROM `{$wrongTable}`");
                    } catch (\Exception $e) {
                        Log::warning("Failed to merge data: " . $e->getMessage());
                    }
                    
                    DB::statement("DROP TABLE `{$wrongTable}`");
                    Log::info("Dropped duplicate table: {$wrongTable}");
                } 
                // 如果正确表名不存在，重命名错误表为正确表名
                else {
                    DB::statement("RENAME TABLE `{$wrongTable}` TO `{$correctTable}`");
                    Log::info("Renamed table from {$wrongTable} to {$correctTable}");
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check/fix table prefix: ' . $e->getMessage());
        }
    }

    /**
     * 加载系统配置
     * @param $request
     */
    private function loadSysConfig($request)
    {
        try {
            // 直接使用DB查询，避免模型带来的表前缀问题
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $configs = DB::table($prefix . 'configs')->get();
            
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