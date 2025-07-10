<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:36
 * 
 * @package     App\Models
 * @author      ｉｋｄｘｈｚ
 * @maintainer  ⓘⓚⓓⓧⓗⓩ
 * @version     3.1.3
 */

namespace App\Models;

/**
 * 域名解析记录模型
 * 
 * @author      𝕚𝕜𝕕𝕩𝕙𝕫
 * @version     3.1.3
 */
class DomainRecord extends Model
{
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    
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
        $this->table = static::safeTable('domain_records');
    }

    /**
     * 搜索查询范围
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $guard 守卫类型
     * @return void
     * @author ïkðxhz
     */
    public function scopeSearch($query, $guard = 'web')
    {
        $query->with(['domain' => function ($query) {
            $query->select(['did', 'domain']);
        }]);
        $did = intval(request()->post('did'));
        if ($did) $query->where('did', $did);
        $type = request()->post('type');
        if ($type) $query->where('type', $type);
        $name = request()->post('name');
        if ($name) $query->where('name', $name);
        $value = request()->post('value');
        if ($value) $query->where('value', $value);
        if ($guard === 'admin') {
            $uid = request()->post('uid');
            if ($uid) $query->where('uid', $uid);
            $query->with(['user' => function ($query) {
                $query->select(['uid', 'username']);
            }]);
        }
    }

    /**
     * 域名关联
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author i​k​d​x​h​z
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'did', 'did');
    }

    /**
     * 用户关联
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author ikd_xhz
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }
}