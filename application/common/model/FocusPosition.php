<?php
namespace app\common\model;
use \think\Model;
class FocusPosition extends Model
{
    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];

    /**
     * 添加位置
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function positionAdd(array $params)
    {
        return $this->save([
            'code' => $params['code'],
            'name' => $params['name'],
            'create_time' => time(),
        ]);
    }

    /**
     * 修改位置
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function positionEdit(array $params)
    {
        return $this->update([
            'code' => $params['code'],
            'name' => $params['name'],
            'update_time' => time(),
        ], ['id' => $params['id']]);
    }

    /**
     * 删除位置
     * @author Zcc<2351976426@qq.com>
     * @dateTime 201610
     * @param    [type]                   $id [description]
     * @return   [type]                       [description]
     */
    public function deletePosition($id)
    {
        if (self::get($id)->focus()->count() > 0) {
            $this->error = '位置下还有焦点图,不能删除';
            return false;
        }
        $position= self::get($id);
        return $position->delete(); 
    }

    /**
     * [focus description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return   [type]                   [description]
     */
    public function focus()
    {
        return $this->hasMany('Focus', 'position_id', 'id');
    }

    /**
     * 获取状态
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    strings                   $value [description]
     * @return   [type]                          [description]
     */
    protected function getStatusAttr($value)
    {
        $status = [0 => '禁用', 1 => '啟用'];
        return $status[$value];
    }

    /**
     * 获取排序
     * @param  [type] $sort [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
//  protected function getSortAttr($sort, $data)
//  {
//      return '<input type="text" value="' . $sort . '" class="sort"/>';
//  }

}
