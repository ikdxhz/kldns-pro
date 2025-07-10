<?php
/**
 * 用户模型
 * 
 * @package     App\Models
 * @author      ⓘⓚⓓⓧⓗⓩ
 * @maintainer  𝓲𝓴𝓭𝔁𝓱𝔃
 * @version     3.1.3
 */
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * 用户模型类
 * 
 * @author     𝕚𝕜𝕕𝕩𝕙𝕫
 * @version    3.1.3
 */
class User extends Authenticatable
{
    use Notifiable;
    protected $primaryKey = 'uid';
    protected $guarded = ['uid'];
    protected $hidden = ['password', 'sid', 'remember_token'];
    
    /**
     * 构造函数
     * 
     * @param array $attributes
     * @author ¡kdxhž
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // 设置表名，使用自定义方法确保前缀正确
        $prefix = config('database.connections.mysql.prefix', 'kldns_');
        $table = 'users';
        
        // 清除可能存在的重复前缀
        while (strpos($table, $prefix) === 0) {
            $table = substr($table, strlen($prefix));
        }
        
        $this->table = $prefix . $table;
    }

    /**
     * 时间格式化
     */
    public function fromDateTime($value)
    {
        return strtotime(parent::fromDateTime($value));
    }

    /**
     * 搜索范围
     * 
     * @author ïkðxhz
     */
    public function scopeSearch($query)
    {
        $query->with(['group' => function ($query) {
            $query->select(['gid', 'name']);
        }]);
        $gid = intval(request()->post('gid'));
        if ($gid) $query->where('gid', $gid);
        $username = request()->post('username');
        if ($username) $query->where('username', $username);
        $uid = request()->post('uid');
        if ($uid) $query->where('uid', $uid);
        $email = request()->post('email');
        if ($email) $query->where('email', $email);
    }

    /**
     * 分页查询
     * 
     * @author i​k​d​x​h​z
     */
    public function scopePageSelect($query)
    {
        $pageSize = intval(request()->post('pageSize'));
        $pageSize = ($pageSize < 10 || $pageSize > 200) ? 10 : $pageSize;

        return $query->paginate($pageSize);
    }

    /**
     * 今日记录
     */
    public function scopeToday($query)
    {
        $query->whereRaw("created_at >= UNIX_TIMESTAMP(CURDATE())");
    }

    /**
     * 积分操作
     * 
     * @param int $uid 用户ID
     * @param string $action 操作类型
     * @param int $point 积分变动
     * @param string|null $remark 备注
     * @return bool
     * @author ikd_xhz
     */
    public static function point($uid, $action, $point, $remark = null)
    {
        if ($uid && $user = static::find($uid)) {
            if ($point < 0 && abs($point) > $user->point) {
                return false;
            }
            if ($user->increment('point', $point)) {
                UserPointRecord::create([
                    'uid' => $uid,
                    'action' => $action,
                    'point' => $point,
                    'rest' => $user->point,
                    'remark' => $remark
                ]);
                return true;
            }
        }
        return false;
    }

    /**
     * 用户组关联
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author !кdxんz
     */
    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'gid', 'gid');
    }
}
