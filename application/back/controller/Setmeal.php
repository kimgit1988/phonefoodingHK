<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Category;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Setmeal extends AdminBase {

    public function index() {
        $param = input('param.');
        $where = array();
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        if(isset($param['contact'])&&$param['contact']!==''){
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('contactNumber',$param['contact'])->select();
            $where['s.contactNumber'] = $param['contact'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['s.name'] = ['like','%'.$param['search'].'%'];
        }
        //套餐首页查询套餐
        $meal = DB::name('SetMeal')
            ->alias('s')
            ->join('mos_contact c','s.contactNumber = c.number and c.isDelete = 0','left')
            ->field('s.*,c.name as contactName')
            ->where($where)
            ->where('s.isDelete',0)
            ->order('s.id desc')
            ->paginate(10,false,['query'=>$param]);
        $this->assign('meal',$meal);
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('pages',$meal->render());
        return $this->fetch();
    }

    /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $time = time();
            $save = array(
                'name' => $params['name'],
                'totlePrice' => $params['totlePrice'],
                'contactNumber' => $params['contactNumber'],
                'status' => $params['status'],
                'ctime'=>$time,
                'utime'=>$time,
            );
            if (loader::validate('Setmeal')->scene('adminAdd')->check($save) === false) {
                return $this->error(loader::validate('Setmeal')->getError());
            }
            $id = DB::name('SetMeal')->insertGetId($save);
            if($id!==false){
                Loader::model('SystemLog')->record("添加套餐,ID:[{$id}]");
                return $this->success('添加套餐成功', Url::build('Setmeal/index'));
            }else{
                return $this->error('添加套餐失敗');
            }
        }else{
            $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
            $this->assign('contact',$contact);
            return $this->fetch();
        }
    }


    /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */

    public function edit() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $time = time();
            $save = array(
                'id'=>$params['id'],
                'name' => $params['name'],
                'totlePrice' => $params['totlePrice'],
                'contactNumber' => $params['contactNumber'],
                'status' => $params['status'],
                'utime'=>$time,
            );
            if (loader::validate('Setmeal')->scene('adminEdit')->check($save) === false) {
                return $this->error(loader::validate('Setmeal')->getError());
            }
            $res = DB::name('SetMeal')->where('id',$params['id'])->update($save);
            if($res!==false){
                Loader::model('SystemLog')->record("修改套餐,ID:[{$params['id']}]");
                return $this->success('修改套餐成功', Url::build('Setmeal/index'));
            }else{
                return $this->error('修改套餐失敗');
            }
        }else{
            $id = $request->param('id');
            $meal = DB::name('SetMeal')->where('id',$id)->where('isDelete',0)->find();
            $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
            $this->assign('contact',$contact);
            $this->assign('meal',$meal);
            return $this->fetch();
        }
    }

    public function info(){
        // 套餐配置页面
        $request = Request::instance();
        if ($request->isPost()) {
            $param = input('param.');
            $id = $param['id'];
            // 将分类ID保存进category 其他改为删除状态
            $category = DB::name('SetMealCategory')->where('mid',$id)->update(['isDelete'=>1]);
            // 将分类菜品ID保存进foodID 其他改为删除状态
            $food = DB::name('SetMealInfo')->where('mid',$id)->update(['isDelete'=>1]);
            $time = time();
            foreach ($param['categoryList'] as $key => $val) {
                if(isset($val['id'])){
                    $update = [
                        'id'=>$val['id'],
                        'mid'=>$id,
                        'name'=>$val['name'],
                        'categoryMaxNumber'=>$val['categoryMax'],
                        'goodsMaxNumber'=>$val['foodsMax'],
                        'sort'=>$val['sort'],
                        'utime'=>$time,
                        'isDelete'=>0,
                    ];
                    $res = DB::name('SetMealCategory')->where('id',$val['id'])->update($update);
                    if ($res) {
                        if (!empty($val['food'])) {
                            foreach ($val['food'] as $k => $v) {
                                if(isset($v['id'])){
                                    // 已有的分类菜品
                                    $data = [
                                        'id'=>$v['id'],
                                        'mid'=>$id,
                                        'cid'=>$val['id'],
                                        'gid'=>$v['foodid'],
                                        'utime'=>$time,
                                        'isDelete'=>0,
                                    ];
                                    $res = DB::name('SetMealInfo')->where('id',$v['id'])->update($data);
                                }else{
                                    // 新增分类菜品
                                    $data = [
                                        'mid'=>$id,
                                        'cid'=>$val['id'],
                                        'gid'=>$v['foodid'],
                                        'utime'=>$time,
                                        'isDelete'=>0,
                                    ];
                                    $res = DB::name('SetMealInfo')->insert($data);
                                }

                            }
                        }

                    }
                }else{
                    $insert = [
                        'mid'=>$id,
                        'name'=>$val['name'],
                        'categoryMaxNumber'=>$val['categoryMax'],
                        'goodsMaxNumber'=>$val['foodsMax'],
                        'sort'=>$val['sort'],
                        'ctime'=>$time,
                        'utime'=>$time,
                        'isDelete'=>0,
                    ];
                    $cid = DB::name('SetMealCategory')->insertGetId($insert);
                    if ($cid) {
                        if (!empty($val['food'])) {
                            foreach ($val['food'] as $k => $v) {
                                $data = [
                                    'mid'=>$id,
                                    'cid'=>$cid,
                                    'gid'=>$v['foodid'],
                                    'ctime'=>$time,
                                    'utime'=>$time,
                                    'isDelete'=>0,
                                ];
                                $res = DB::name('SetMealInfo')->insert($data);
                            }
                        }
                    }
                }
            }
            Loader::model('SystemLog')->record("配置套餐,ID:[{$id}]");
            return $this->success('配置套餐成功', Url::build('Setmeal/index'));
        }else{
            $id = $request->param('id');
            $list = array();
            $meal = DB::name('SetMeal')->where('id',$id)->where('isDelete',0)->find();
            $category = DB::name('SetMealCategory')->where('mid',$id)->where('isDelete',0)->order('sort asc,id desc')->select();
            $info = DB::name('SetMealInfo')
                ->alias('s')
                ->join('mos_goods g','s.gid = g.id and g.isDelete = 0','left')
                ->field('s.*,g.name as goodsName,g.id as goodsId')
                ->where('s.mid',$id)
                ->where('s.isDelete',0)
                ->order('s.sort asc,s.id desc')
                ->select();
            $goods = DB::name('Goods')->where('contactNumber',$meal['contactNumber'])->where('isDelete',0)->select();
            foreach ($category as $k => $v) {
                $list[$v['id']] = $v;
            }
            foreach ($info as $key => $val) {
                // 有这个菜品才放入数组
                if(!empty($val['goodsId'])){
                    $list[$val['cid']]['_child'][] = $val;
                }
            }
            $number = 0;
            foreach ($list as $lk => $lv) {
                $list[$lk]['arrkey'] = $number;
                $number++;
            }
            $categoryNumber = count($list);
            $this->assign('categoryNumber',$categoryNumber);
            $this->assign('meal',$meal);
            $this->assign('list',$list);
            $this->assign('goods',$goods);
            return $this->fetch();
        }
    }
    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function destroy() {
        $request = Request::instance();
        $id = $request->param('id');
        $res = DB::name('SetMeal')->where('id',$id)->update(['isDelete'=>1]);
        $res = DB::name('SetMealCategory')->where('mid',$id)->update(['isDelete'=>1]);
        $res = DB::name('SetMealInfo')->where('mid',$id)->update(['isDelete'=>1]);
        Loader::model('SystemLog')->record("套餐删除,ID:[{$id}]");
        return $this->success('套餐删除成功', Url::build('Setmeal/index'));
    }
}