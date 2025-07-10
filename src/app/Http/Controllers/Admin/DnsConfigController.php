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

class DnsConfigController extends Controller
{
    public function post(Request $request)
    {
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
                if ($id && $row = DnsConfig::find($id)) {
                    $row->name = $name;
                    $row->dns = $dns;
                    $row->config = json_encode($config);
                    $row->save();
                } else {
                    // 检查配置名称是否已存在
                    if (DnsConfig::where('name', $name)->where('dns', $dns)->first()) {
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