<?php
namespace app\common\model;
use think\Model;

class Attr extends Model {

  /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function attrAdd(array $params) {
        return $this->save([
            'attr_name' => $params['attr_name'],
        ]);
    }

  /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function attrEdit(array $params) {
        return $this->update(
        ['attr_name' => $params['attr_name'],], 
        ['aid' => $params['aid']]);
    }

  /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public static function read() {
        return self::field('aid,attr_name');
    }

  /**
     * [index description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function deleteAttr($aid) {
        $articleRow = self::get($aid);
        return $articleRow->delete();
    }


}