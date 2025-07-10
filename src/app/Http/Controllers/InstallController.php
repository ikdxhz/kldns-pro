<?php
/**
 * 安装控制器
 * 
 * @author ⓘⓚⓓⓧⓗⓩ
 * @version 3.1.3
 */
namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

/**
 * Class InstallController
 *
 * @package App\Http\Controllers
 * @developer ⁱᵏᵈˣʰᶻ
 */
class InstallController extends Controller
{
    private $version = '3.1.3'; // 当前版本

    public function update()
    {
        try {
            // 检查数据库连接
            if (!$this->checkDatabaseConnection()) {
                return; // 如果数据库未配置，直接返回
            }
            
            // 检查表前缀重复问题
            $this->fixTablePrefixIssue();
            
            // 清理可能存在的错误表
            $this->cleanupErrorTables();
            
            // 执行版本更新
            $this->performVersionUpdate();
        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查数据库连接
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 修复表前缀重复问题
     */
    private function fixTablePrefixIssue()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $configsTable = $prefix . 'configs';
            $wrongConfigsTable = $prefix . $prefix . 'configs';
            
            // 检查表是否存在
            $tables = DB::select('SHOW TABLES');
            $tablesArray = array_map(function($table) {
                return array_values((array)$table)[0]; 
            }, $tables);
            
            // 如果错误表名存在但正确表名不存在，则重命名表
            if (in_array($wrongConfigsTable, $tablesArray) && !in_array($configsTable, $tablesArray)) {
                DB::statement("RENAME TABLE `{$wrongConfigsTable}` TO `{$configsTable}`");
                Log::info("Renamed table from {$wrongConfigsTable} to {$configsTable}");
            }
        } catch (Exception $e) {
            Log::error('Fix table prefix failed: ' . $e->getMessage());
        }
    }
    
    /**
     * 清理可能存在的错误表（重复前缀的表）
     */
    private function cleanupErrorTables()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $doublePrefix = $prefix . $prefix;
            
            // 获取所有表
            $tables = DB::select('SHOW TABLES');
            $tablesArray = array_map(function($table) {
                return array_values((array)$table)[0]; 
            }, $tables);
            
            // 标准表名列表
            $standardTables = [
                'configs', 'dns_configs', 'domain_records', 'domains', 
                'password_resets', 'user_groups', 'user_point_records', 'users'
            ];
            
            foreach ($standardTables as $table) {
                $correctTable = $prefix . $table;
                $wrongTable = $doublePrefix . $table;
                
                // 如果错误表名存在
                if (in_array($wrongTable, $tablesArray)) {
                    // 如果正确表名也存在，删除错误表
                    if (in_array($correctTable, $tablesArray)) {
                        DB::statement("DROP TABLE `{$wrongTable}`");
                        Log::info("Dropped duplicate table: {$wrongTable}");
                    } 
                    // 如果正确表名不存在，重命名错误表为正确表名
                    else {
                        DB::statement("RENAME TABLE `{$wrongTable}` TO `{$correctTable}`");
                        Log::info("Renamed table from {$wrongTable} to {$correctTable}");
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Cleanup error tables failed: ' . $e->getMessage());
        }
    }
    
    /**
     * 执行版本更新
     */
    private function performVersionUpdate()
    {
        $version = config('version', '3.0.1');
        $versionCode = intval(str_replace('.', '', $version));
        $nowVersionCode = intval(str_replace('.', '', $this->version));
        
        if ($versionCode < $nowVersionCode) {
            $list = $this->getUpdateList();
            if (!empty($list)) {
                try {
                    // 使用Laravel的DB连接，更安全可靠
                    $prefix = config('database.connections.mysql.prefix', 'kldns_');
                    
                    foreach ($list as $code) {
                        $code = $code . '';
                        $code = $code[0] . '.' . $code[1] . '.' . $code[2];
                        $sqlFile = __DIR__ . '/../../../install/update.' . $code . '.sql';
                        
                        if (file_exists($sqlFile)) {
                            $sqls = @file_get_contents($sqlFile);
                            $sqls = str_replace('`kldns_', '`' . $prefix, $sqls);
                            $sqls = explode(';', $sqls);
                            
                            foreach ($sqls as $sql) {
                                $sql = trim($sql);
                                if (!empty($sql)) {
                                    try {
                                        DB::statement($sql);
                                    } catch (Exception $e) {
                                        Log::error("SQL execution error: {$e->getMessage()}, SQL: {$sql}");
                                    }
                                }
                            }
                            
                            // 更新版本文件
                            if (!file_put_contents(__DIR__ . '/../../../config/version.php', '<?php' . PHP_EOL . 'return "' . $code . '";')) {
                                Log::error('Failed to write version file');
                            }
                            
                            // 更新数据库中的版本记录
                            try {
                                DB::table($prefix . 'configs')->updateOrInsert(
                                    ['k' => 'system_version'],
                                    ['v' => $code]
                                );
                            } catch (Exception $e) {
                                Log::error("Failed to update system version in database: " . $e->getMessage());
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::error('Database update failed: ' . $e->getMessage());
                }
            }
        }
    }

    private function getUpdateList()
    {
        $list = [];
        $dir = __DIR__ . '/../../../install/';
        $files = scandir($dir);
        foreach ($files as $file) {
            $file = explode('.', $file);
            if (count($file) == 5 && $file[4] === 'sql' && $file[0] === 'update') {
                $list[] = intval($file[1] . $file[2] . $file[3]);
            }
        }
        sort($list);
        return $list;
    }

    public function install(Request $request)
    {
        // 开发者: ikd_xhz
        if ($request->method() === 'POST') {
            $action = $request->post('action');
            switch ($action) {
                case 'mysql':
                    return $this->mysql($request);
            }
        }

        // 如果已安装，显示提示页面
        if (file_exists(config_path('mysql.php'))) {
            return view('install');
        }

        // 否则，显示带有环境检查的安装页面
        return view('install')->with('support', $this->checkSupport());
    }

    /**
     * 处理MySQL数据库安装
     * 
     * @param Request $request
     * @return array
     * @author 1kdxhz
     */
    private function mysql(Request $request)
    {
        $result = ['status' => 1, 'message' => '未知错误'];
        $mysql = [
            'host' => $request->post('host', '127.0.0.1'),
            'port' => $request->post('port', 3306),
            'database' => $request->post('database'),
            'username' => $request->post('username'),
            'password' => $request->post('password'),
            'prefix' => $request->post('prefix', 'kldns_'),
        ];

        if (empty($mysql['database']) || empty($mysql['username'])) {
            $result['message'] = '请填写完整的数据库信息';
            return $result;
        }

        try {
            $db = new PDO("mysql:host={$mysql['host']};port={$mysql['port']};charset=utf8mb4", $mysql['username'], $mysql['password']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 检查数据库是否存在，不存在则创建
            $db->exec("CREATE DATABASE IF NOT EXISTS `{$mysql['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $db->exec("USE `{$mysql['database']}`");

        } catch (Exception $e) {
            $result['message'] = '数据库连接或创建失败: ' . $e->getMessage();
            return $result;
        }

        // 写入mysql.php配置文件
        $configContent = '<?php' . PHP_EOL . '// 由安装程序生成 - i​k​d​x​h​z' . PHP_EOL . 'return ' . var_export($mysql, true) . ';';
        if (!file_put_contents(config_path('mysql.php'), $configContent)) {
            $result['message'] = '写入配置文件失败，请检查 /src/config 目录是否有写入权限';
            return $result;
        }

        // 更新.env文件
        $this->updateEnvFile($mysql);

        // 写入版本文件
        file_put_contents(config_path('version.php'), '<?php' . PHP_EOL . 'return "' . $this->version . '";');

        // 执行install.sql
        try {
            $sqls = file_get_contents(base_path('src/install/install.sql'));
            $sqls = str_replace('`kldns_', '`' . $mysql['prefix'], $sqls);
            $statements = array_filter(array_map('trim', explode(';', $sqls)));

            $success = 0;
            $error = 0;
            $errorList = [];
            foreach ($statements as $sql) {
                if ($db->exec($sql) === false) {
                    $error++;
                    $errorList[] = $db->errorInfo()[2];
                } else {
                    $success++;
                }
            }

            // 写入系统版本
            $tableName = $mysql['prefix'] . 'configs';
            $stmt = $db->prepare("INSERT INTO `{$tableName}` (`k`, `v`) VALUES ('system_version', ?) ON DUPLICATE KEY UPDATE `v` = ?");
            $stmt->execute([$this->version, $this->version]);

            $result = [
                'status' => 0, 
                'message' => '安装成功！', 
                'data' => [
                    'success' => $success,
                    'error' => $error,
                    'msg' => $errorList
                ]
            ];

        } catch (Exception $e) {
            $result['message'] = '导入数据失败: ' . $e->getMessage();
            // 如果导入失败，删除已创建的配置文件
            @unlink(config_path('mysql.php'));
        }

        return $result;
    }
    
    /**
     * 更新.env文件数据库配置
     */
    private function updateEnvFile($mysql)
    {
        try {
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                
                // 更新数据库配置
                $envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST='.$mysql['host'], $envContent);
                $envContent = preg_replace('/DB_PORT=.*/', 'DB_PORT='.$mysql['port'], $envContent);
                $envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE='.$mysql['database'], $envContent);
                $envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME='.$mysql['username'], $envContent);
                $envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD='.$mysql['password'], $envContent);
                
                // 添加数据库前缀配置
                if (strpos($envContent, 'DB_PREFIX=') !== false) {
                    $envContent = preg_replace('/DB_PREFIX=.*/', 'DB_PREFIX='.$mysql['prefix'], $envContent);
                } else {
                    $envContent .= "\nDB_PREFIX=".$mysql['prefix']."\n";
                }
                
                file_put_contents($envPath, $envContent);
                Log::info('Updated .env file with new database configuration');
            }
        } catch (Exception $e) {
            Log::error('Failed to update .env file: ' . $e->getMessage());
        }
    }

    private function checkSupport()
    {
        $list = [
            [
                'name' => 'PHP版本>=7.13',
                'support' => version_compare(PHP_VERSION, '7.1.3', '>=')
            ],
            [
                'name' => 'OpenSSL 扩展',
                'support' => function_exists('openssl_verify')
            ],
            [
                'name' => 'PDO 扩展',
                'support' => class_exists("PDO")
            ],
            [
                'name' => 'Mbstring 扩展',
                'support' => function_exists("mb_convert_encoding")
            ],
            [
                'name' => 'Ctype 扩展',
                'support' => function_exists("ctype_alnum")
            ],
            [
                'name' => '/src/config 目录写入权限',
                'support' => $this->checkPath(__DIR__ . '/../../../config')
            ],
            [
                'name' => '/src/storage 目录写入权限',
                'support' => $this->checkPath(__DIR__ . '/../../../storage')
            ],
            [
                'name' => '/.env 文件写入权限',
                'support' => $this->checkEnvWritable()
            ]
        ];
        return $list;
    }

    private function checkPath($path)
    {
        if (is_dir($path) || mkdir($path, 0755, true)) {
            return true;
        }
        return false;
    }
    
    private function checkEnvWritable()
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            return is_writable($envPath);
        }
        
        // 如果文件不存在，检查父目录是否可写
        return is_writable(dirname($envPath));
    }
}
