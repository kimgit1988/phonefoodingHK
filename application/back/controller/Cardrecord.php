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
class Cardrecord extends AdminBase {
    
    public function index() {
        $param = input('param.');
        $cardDB = DB::name('CardRecord');
        if(isset($param['card'])&&$param['card']!==''){
            $cardDB->where('cardId',$param['card']);
        }
        if(isset($param['cardType'])&&$param['cardType']!==''){
            $cardDB->where('cCardType',$param['cardType']);
        }
        if(isset($param['card'])&&$param['card']!==''){
            $cardDB->where('cardId',$param['card']);
        }
        if(isset($param['useType'])&&$param['useType']!==''){
            $cardDB->where('cUseType',$param['useType']);
            if($param['useType']==2&&isset($param['contactNumber'])&&$param['contactNumber']!==''){
                $cardDB->where('cContactNumber',$param['contactNumber']);
            }
        }
        if(isset($param['status'])&&$param['status']!==''){
            $cardDB->where('status',$param['status']);
        }
        if(isset($param['search'])&&$param['search']!==''){
            $cardDB->where('cName|cardCode','like','%'.$param['search'].'%');
        }
        $card = $cardDB->order('id desc')->paginate(10);
        $contact = DB::name('contact')->field('id,name,number')->where('isDelete',0)->where('disable',1)->order('id desc')->select();
        $cardlist = DB::name('cardInfo')->field('id,name,cardSN')->where('isDelete',0)->where('status',1)->order('id desc')->select();
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('cardlist',$cardlist);
        $this->assign('card',$card);
        $this->assign('pages',$card->render());
        return $this->fetch();
    }
}