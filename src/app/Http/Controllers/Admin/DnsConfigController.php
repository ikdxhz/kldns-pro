<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:42
 */

namespace App\Http\Controllers\Admin;


use App\Klsf\Dns\Helper;
use App\Models\DnsConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DnsConfigController extends Controller
{
    public function post(Request $request)
    {
        // 确保表结构正确
        $this->ensureTableStructure();
        
        $action = $request->post('action');
        switch ($action) {
            case 'all':
                return $this->all($request);
            case 'store':
                return $this->store($request);
            case 'select':
                return $this->select($request);
            case 'delete':
                return $this->delete($request);
            default:
                return ['status' => -1, 'message' => '对不起，此操作不存在！'];
        }
    }
    
    /**
     * 确保表结构正确
     */
    private function ensureTableStructure()
    {
        try {
            $prefix = config('database.connections.mysql.prefix', 'kldns_');
            $table = $prefix . 'dns_configs';
            
            // 检查表是否存在
            if (!Schema::hasTable($table)) {
                // 创建表
                Schema::create($table, function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name', 150);
                    $table->string('dns', 150);
                    $table->string('config', 1024)->nullable();
                    $table->integer('created_at')->unsigned()->nullable();
                    $table->integer('updated_at')->unsigned()->nullable();
                    $table->unique(['name', 'dns']);
                });
                Log::info("Created missing table: {$table}");
            } else {
                // 检查name字段是否存在
                if (!Schema::hasColumn($table, 'name')) {
                    Schema::table($table, function (Blueprint $table) {
                        $table->string('name', 150)->after('id')->default('');
                    });
                    // 更新已有记录的name字段
                    \DB::statement("UPDATE `{$prefix}dns_configs` SET `name` = CONCAT(dns, '-默认配置') WHERE `name` = '' OR `name` IS NULL");
                    Log::info("Added missing name column to: {$table}");
                }
                
                // 检查主键是否正确
                if (!Schema::hasColumn($table, 'id')) {
                    // 复杂操作，使用原始SQL
                    \DB::statement("
                        ALTER TABLE `{$prefix}dns_configs` 
                        ADD COLUMN `id` int(10) unsigned NOT NULL AUTO_INCREMENT FIRST,
                        DROP PRIMARY KEY,
                        ADD PRIMARY KEY (`id`)
                    ");
                    Log::info("Fixed primary key for table: {$table}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to ensure table structure: " . $e->getMessage());
        }
    }

    private function all(Request $request)
    {
        $list = Helper::getList();
        $data = [];
        foreach ($list as $dns) {
            $_dns = Helper::getModel($dns);
            $data[$dns] = $_dns->configInfo();
        }
        return ['status' => 0, 'message' => '', 'data' => $data];
    }

    private function store(Request $request)
    {
        $result = ['status' => -1];
        $dns = $request->post('dns');
        $name = $request->post('name');
        $config = $request->post('config');
        $id = $request->post('id');
        
        if (!$dns) {
            $result['message'] = '请选择域名解析平台';
        } elseif (!$name) {
            $result['message'] = '请输入配置名称';
        } elseif (!$_dns = Helper::getModel($dns)) {
            $result['message'] = '暂不支持此域名解析平台';
        } else {
            $_dns->config($config);
            list($check, $error) = $_dns->check();
            if (!$check) {
                $result['message'] = '请检查配置是否正确：' . $error;
            } else {
                try {
                    if ($id && $row = DnsConfig::find($id)) {
                        $row->name = $name;
                        $row->dns = $dns;
                        $row->config = json_encode($config);
                        $row->save();
                    } else {
                        // 检查配置名称是否已存在
                        // 由 ikd-xhz 修复：使用DB门面和参数绑定防止SQL注入和类型错误
                        $exists = \DB::table('dns_configs')
                                    ->where('name', '=', $name)
                                    ->where('dns', '=', $dns)
                                    ->first();
                        
                        if ($exists) {
                            $result['message'] = '该平台下已存在同名配置';
                            return $result;
                        }
                        
                        DnsConfig::create([
                            'name' => $name,
                            'dns' => $dns,
                            'config' => json_encode($config)
                        ]);
                    }
                    $result = ['status' => 0, 'message' => '保存成功'];
                } catch (\Exception $e) {
                    Log::error("Failed to store DNS config: " . $e->getMessage());
                    $result['message'] = '保存失败，请检查系统日志';
                }
            }
        }
        return $result;
    }

    private function select(Request $request)
    {
        $data = DnsConfig::orderBy('created_at', 'desc')->pageSelect();
        return ['status' => 0, 'message' => '', 'data' => $data];
    }

    private function delete(Request $request)
    {
        $result = ['status' => -1];
        $id = $request->post('id');
        if (!$id || !$row = DnsConfig::find($id)) {
            $result['message'] = '接口配置不存在';
        } elseif ($row->delete()) {
            $result = ['status' => 0, 'message' => '删除成功'];
        } else {
            $result['message'] = '删除失败，请稍后再试！';
        }
        return $result;
    }
}