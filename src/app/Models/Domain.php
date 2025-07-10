<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:36
 * 
 * @package     App\Models
 * @author      ｉｋｄｘｈｚ
 * @maintainer  !кdxんz
 * @version     3.1.3
 */

namespace App\Models;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 域名模型
 * 
 * @author      𝕚𝕜𝕕𝕩𝕙𝕫
 * @version     3.1.3
 */
class Domain extends Model
{
    protected $primaryKey = 'did';
    protected $guarded = ['did'];
    
    /**
     * 构造函数
     * 
     * @param array $attributes
     * @author ¡kdxhž
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // 设置表名，使用safeTable方法确保前缀正确
        $this->table = static::safeTable('domains');
    }

    /**
     * 可用域名查询范围
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $gid 用户组ID
     * @return void
     * @author ïkðxhz
     */
    public function scopeAvailable($query, $gid = 0)
    {
        $gid = $gid ? $gid : (Auth::check() ? Auth::user()->gid : 0);
        $query->where('groups', '0');
        if ($gid > 0) {
            $query->orWhereRaw(DB::raw("FIND_IN_SET('{$gid}',groups)"));
        }
    }

    /**
     * DNS配置关联
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author i​k​d​x​h​z
     */
    public function dnsConfig()
    {
        // 优先通过dns_config_id关联
        if (!empty($this->dns_config_id)) {
            return $this->belongsTo(DnsConfig::class, 'dns_config_id', 'id');
        }
        // 兼容旧数据，通过dns字段关联
        return $this->belongsTo(DnsConfig::class, 'dns', 'dns');
    }
}