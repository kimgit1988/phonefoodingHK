<?php
namespace app\common\model;
use \PDOException;
use \think\Db;
use \think\Model;

class Role extends Model
{
    protected $auto = ['status'];

    protected $type = [
        'id'          => 'integer',
        'status'      => 'integer',
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];

    /**
     * 获取状态
     * @author luffy<luffyzhao@vip.126.com>
     * @dateTime 2016-04-19T16:00:40+0800
     * @param    string                   $value [description]
     * @return   [type]                          [description]
     */
    public function getStatusAttr($value, $data)
    {
        $status = [1 => '<span class="label label-success">启用</span>', 0 => '<span class="label label-warning">禁用</span>'];
        return $status[$value];
    }

    /**
     * 获取器 获取用户数量
     * @author luffy<luffyzhao@vip.126.com>
     * @dateTime 2016-05-19T15:47:23+0800
     * @param    [type]                   $value [description]
     * @param    [type]                   $data  [description]
     * @return   [type]                          [description]
     */
    public function getUserCountAttr($value, $data)
    {
        return $this->user()->count();
    }

    /**
     * 多对多关联权限表
     * @author luffy<luffyzhao@vip.126.com>
     * @dateTime 2016-05-19T14:10:40+0800
     * @return   [type]                   [description]
     */
    public function rule()
    {
        return $this->belongsToMany('Rule', 'role_rule', 'rule_id', 'role_id');
    }

    /**
     * [user description]
     * @author luffy<luffyzhao@vip.126.com>
     * @dateTime 2016-05-20T14:36:59+0800
     * @return   [type]                   [description]
     */
    public function user()
    {
        return $this->hasMany('User', 'role_id', 'id');
    }

    /**
     * 添加角色
     * @author luffy<luffyzhao@vip.126.com>
     * @dateTime 2016-05-19T15:04:27+0800
     * @param    array                    $data [description]
     */
    public function addRole(array $data)
    {
        return Db::transaction(function () use ($data) {
            $roleModel = new Role;

            $roleId = $roleModel->save([
                'status' => $data['status'],
                'name'   => $data['name'],
                'remark' => $data['remark'],
            ]);

            if ($roleId === false) {
                throw new PDOException($roleModel->getError());
            }

            if (isset($data['rules']) && is_array($data['rules']) && !empty($data['rules'])) {
                $roleModel     = $roleModel->find($roleId);
                $data['rules'] = array_map('intval', $data['rules']);
                //插入关联表
                $roleModel->rule()->saveAll($data['rules']);
            }

            return $roleId;
        });
    }

    /**
     * [editRole description]
     * @author luffy<luffyzhao@vip.126.com>
     * @dateTime 2016-05-20T10:49:49+0800
     * @param    array                    $data [description]
     * @param    [type]                   $id   [description]
     * @return   [type]                         [description]
     */
    public function editRole(array $data)
    {
        return Db::transaction(function () use ($data) {
            // 更新
            if ($this->save([
                'status' => $data['status'],
                'name'   => $data['name'],
                'remark' => $data['remark'],
            ], ['id' => $data['id']]) === false) {
                throw new PDOException($this->getError());
            }
            //先删除关联数据
            Db::name('role_rule')->where(['role_id' => $this->id])->delete();

            if (isset($data['rules']) && is_array($data['rules']) && !empty($data['rules'])) {
                $data['rules'] = array_map('intval', $data['rules']);
                //插入关联表
                $this->rule()->saveAll($data['rules']);
            }

        });
    }

    /**
     * [deleteRole description]
     * @author luffy<luffyzhao@vip.126.com>
     * @dateTime 2016-05-20T15:03:31+0800
     * @param    [type]                   $id [description]
     * @return   [type]                       [description]
     */
    public function deleteRole($id)
    {
        $roleModel = $this->find($id);
        if ($roleModel == false) {
            $this->error = '用戶組不存在，或者已删除！';
            return false;
        }

        if ($roleModel->user()->count() > 0) {
            $this->error = '用戶組下存在用戶，不能删除！';
            return false;
        }

        return Db::transaction(function () use ($roleModel) {
            // 先删除关联中间表的数据
            \think\Db::name('role_rule')->where('role_id', $roleModel->id)->delete();

            $roleModel->delete();
        });
    }

}
