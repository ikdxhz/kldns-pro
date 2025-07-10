<?php

namespace App\Http\Middleware;

use App\Models\Config;
use Closure;

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
        // 安装检查：如果系统未安装，允许访问安装页面
        if (!file_exists(config_path('mysql.php')) && $request->path() == 'install') {
            return $next($request);
        }

        // 如果系统已安装，检查版本并更新
        try {
            if (file_exists(config_path('mysql.php'))) {
                // 尝试连接数据库
                try {
                    \DB::connection()->getPdo();
                    
                    // 检查表是否存在
                    if (!\Schema::hasTable('configs')) {
                        // 如果configs表不存在，可能是旧版本，尝试根据旧表前缀查询
                        $prefix = config('database.connections.mysql.prefix', 'kldns_');
                        if (\Schema::hasTable($prefix . 'configs')) {
                            // 设置正确的表名
                            \DB::statement("ALTER TABLE {$prefix}configs RENAME TO configs");
                        }
                    }
                    
                    // 执行版本检查和更新
                    Config::checkAndUpdateVersion();
                    
                } catch (\Exception $e) {
                    \Log::error('Database connection failed: ' . $e->getMessage());
                    // 数据库连接失败，重定向到安装页面
                    if ($request->path() != 'install') {
                        return redirect('/install');
                    }
                }
            } else {
                // 系统未安装，重定向到安装页面
                if ($request->path() != 'install') {
                    return redirect('/install');
                }
            }
        } catch (\Exception $e) {
            \Log::error('System version check failed: ' . $e->getMessage());
        }

        return $next($request);
    }
} 