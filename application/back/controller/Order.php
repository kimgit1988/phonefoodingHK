<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Order extends AdminBase {
    
    public function index() {
        $param = input('get.');
        $where = array();
        $category = array();
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        if(isset($param['contact'])&&$param['contact']!==''){
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('contactNumber',$param['contact'])->select();
            $where['contactNumber'] = $param['contact'];
        }
        if(isset($param['status'])&&$param['status']!==''){
            $where['orderStatus'] = $param['status'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['orderSN'] = ['like','%'.$param['search'].'%'];
        }
        if(isset($param['orderstart'])&&$param['orderstart']!==''&&isset($param['orderend'])&&$param['orderend']!==''){
            $where['createTime'] = ['between',strtotime($param['orderstart']).','.strtotime($param['orderend'])];
        }else if(isset($param['orderstart'])&&$param['orderstart']!==''){
            $where['createTime'] = ['>=',strtotime($param['orderstart'])];
        }else if(isset($param['orderend'])&&$param['orderend']!==''){
            $where['createTime'] = ['<=',strtotime($param['orderend'])];
        }
        if(isset($param['paystart'])&&$param['paystart']!==''&&isset($param['payend'])&&$param['payend']!==''){
            $where['payTime'] = ['between',strtotime($param['paystart']).','.strtotime($param['payend'])];
        }else if(isset($param['paystart'])&&$param['paystart']!==''){
            $where['payTime'] = ['>=',strtotime($param['paystart'])];
        }else if(isset($param['payend'])&&$param['payend']!==''){
            $where['payTime'] = ['<=',strtotime($param['payend'])];
        }
        // 管理员能看到全部订单
        $Order = Loader::model('WxOrder')->where(['isDelete'=>0])->where($where)->order('id desc')->paginate(10,false,['query'=>$param]);
        $sum = Loader::model('WxOrder')->where(['isDelete'=>0])->where($where)->order('id desc')->sum('moneyPaid');
        $new = Loader::model('WxOrder')->field('id')->where(['isDelete'=>0])->order('id desc')->find();
        $this->assign('param',$param);
        $this->assign('NewId',$new['id']);
        $this->assign('contact',$contact);
        $this->assign('lists', $Order);
        $this->assign('sum',$sum);
        $this->assign('pages',$Order->render());
        return $this->fetch();
    }

    public function detail(){
        $request = Request::instance();
        $id = $request->param('id');
        if(session('ext_user.is_contact')==0){
            $Order = Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>$id])->find();
            $food  = DB::name('WxOrderGoods')->where(['orderSN'=>$Order['orderSN']])->select();
        }else{
            $contact_number = session('ext_user.contact_number');
            $Order = Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>$id])->where('contactNumber',$contact_number)->find();
            $food  = DB::name('WxOrderGoods')->where(['orderSN'=>$Order['orderSN']])->where('contactNumber',$contact_number)->select();
        }
        $food_list = array();
        foreach ($food as $key => $val) {
            // 非套餐
            if($val['goodsType']<3){
                $food_list['food_'.$val['id']] = $val;
            // 套餐基本信息
            }else if($val['goodsType']==3){
                if(!empty($food_list['meal_'.$val['groupNumber']]['_food'])){
                    $val['_food'] = $food_list['meal_'.$val['groupNumber']]['_food'];
                }
                $food_list['meal_'.$val['groupNumber']] = $val;
            // 套餐菜品
            }else{
                $food_list['meal_'.$val['groupNumber']]['_food'][] = $val;
            }
        }
        $food = $food_list;
            
        if(empty($Order)){
            return $this->error('未找到订单!');
        }
        $this->assign('order',$Order);
        $this->assign('foodlist',$food);
        return $this->fetch();
    }

    public function confirm(){
        $request = Request::instance();
        $param = $request->param();
        $status = $param['action']+1;
        if(session('ext_user.is_contact')==0){
            if ($Order = Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>$param['id'],'orderStatus'=>$param['action']])->update(['orderStatus'=>$status]) === false) {
                return $this->error(loader::model('WxOrder')->getError());
            }
        }else{
            $contact_number = session('ext_user.contact_number');
            if (Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>$param['id'],'orderStatus'=>$param['action']])->where('contactNumber',$contact_number)->update(['orderStatus'=>$status]) === false) {
                return $this->error(loader::model('WxOrder')->getError());
            }
        }
        
        Loader::model('SystemLog')->record("修改订单状态:[{$param['id']}]为[{$status}]");
        return $this->success('订单状态修改成功', Url::build('Order/index'));
    }

    public function cancel(){
        $request = Request::instance();
        $param = $request->param();
        $status = 0;
        if(session('ext_user.is_contact')==0){
            //实例化对象，然后自定义封装方法userrow。具体看源码。
            if ($Order = Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>$param['id']])->update(['orderStatus'=>$status]) === false) {
                return $this->error(loader::model('WxOrder')->getError());
            }
        }else{
            $contact_number = session('ext_user.contact_number');
            //实例化对象，然后自定义封装方法userrow。具体看源码。
            if (Loader::model('WxOrder')->where(['isDelete'=>0,'id'=>$param['id']])->where('contactNumber',$contact_number)->update(['orderStatus'=>$status]) === false) {
                return $this->error(loader::model('WxOrder')->getError());
            }
        }
        
        Loader::model('SystemLog')->record("订单取消:[{$param['id']}]");
        return $this->success('订单取消成功', Url::build('Order/index'));
    }

    public function destroy() {
        $request = Request::instance();
        $id = $request->param('id');
        if (Loader::model('WxOrder')->deleteOrder($id) === false) {
            return $this->error(Loader::model('WxOrder')->getError());
        }
        Loader::model('SystemLog')->record("订单删除,ID:[{$id}]");
        return $this->success('订单删除成功', Url::build('Order/index'));
    }
    
}