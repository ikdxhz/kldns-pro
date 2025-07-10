<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 2019/4/14
 * Time: 16:36
 * 
 * @package     App\Models
 * @author      ⓘⓚⓓⓧⓗⓩ
 * @maintainer  𝓲𝓴𝓭𝔁𝓱𝔃
 * @version     3.1.3
 */

namespace App\Models;

/**
 * 用户组模型
 * 
 * @author     𝕚𝕜𝕕𝕩𝕙𝕫
 * @version    3.1.3
 */
class UserGroup extends Model
{
    protected $primaryKey = 'gid';
    protected $guarded = ['gid'];
    
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
        $this->table = static::safeTable('user_groups');
    }
}