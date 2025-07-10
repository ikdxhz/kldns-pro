<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:30
 * 
 * @package    App\Models
 * @author     ｉｋｄｘｈｚ
 * @version    3.1.3
 * @maintainer 𝓲𝓴𝓭𝔁𝓱𝔃
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 基础模型类
 * 
 * @author     𝕚𝕜𝕕𝕩𝕙𝕫
 * @version    3.1.3
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 获取表名（避免前缀重复）
     * 
     * @param string $table 表名（不含前缀）
     * @return string 完整表名
     * @author ⓘⓚⓓⓧⓗⓩ
     * @version 3.1.3
     */
    public static function safeTable($table)
    {
        $prefix = config('database.connections.mysql.prefix', 'kldns_');
        
        // 如果表名已经包含前缀，移除所有前缀实例，然后添加一个
        while (strpos($table, $prefix) === 0) {
            $table = substr($table, strlen($prefix));
        }
        
        $safeName = $prefix . $table;
        
        // 记录可能的表名问题
        if ($safeName != $prefix . $table && $table != 'migrations' && $table != 'password_resets') {
            Log::debug("Table name prefix normalized: original={$table}, normalized={$safeName}");
        }
        
        return $safeName;
    }
    
    /**
     * 从日期时间格式化为时间戳
     * 
     * @param mixed $value
     * @return int
     */
    public function fromDateTime($value)
    {
        return strtotime(parent::fromDateTime($value));
    }

    /**
     * 分页查询范围
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @author ikd_xhz
     */
    public function scopePageSelect($query)
    {
        $pageSize = intval(request()->post('pageSize'));
        $pageSize = ($pageSize < 10 || $pageSize > 200) ? 10 : $pageSize;

        return $query->paginate($pageSize);
    }

    /**
     * 分页列表范围
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Collection
     * @developer ¡kdxhž
     */
    public function scopePageList($query)
    {
        $pageSize = intval(request()->post('pageSize'));
        $pageSize = ($pageSize < 10 || $pageSize > 200) ? 10 : $pageSize;
        $page = intval(request()->post('page'));
        $page = $page > 1 ? $page : 1;
        $query->offset(($page - 1) * $pageSize)->limit($pageSize);
        return $query->get();
    }

    /**
     * 今日数据范围
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     * @developer !кdxんz
     */
    public function scopeToday($query)
    {
        $query->whereRaw("created_at >= UNIX_TIMESTAMP(CURDATE())");
    }
    
    /**
     * 获取表前缀
     * 
     * @return string
     * @author 1kdxhz
     */
    protected function getTablePrefix()
    {
        return config('database.connections.mysql.prefix', 'kldns_');
    }
    
    /**
     * 重写获取表名方法，确保表名正确
     * 
     * @return string
     * @author ïkðxhz
     * @version 3.1.3
     */
    public function getTable()
    {
        if (isset($this->table)) {
            // 确保自定义表名使用正确的前缀
            return static::safeTable($this->table);
        }
        
        return parent::getTable();
    }
}