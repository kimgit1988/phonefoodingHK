<?php
namespace app\common\model;
use\think\Config;
use\think\Db;
use\think\Loader;
use\think\Model;
use\think\Session;
class User extends Model {

    public function userInc(array $params) {
        $save = array();
        $save['uid']         = $params['id'];
        $save['name']        = $params['name'];
        $save['nick']        = $params['nick'];
        $save['password']    = md5($params['password']);
        $save['head']        = $params['head'];
        $save['email']       = $params['email'];
        $save['mobile']      = $params['mobile'];
        $save['status']      = isset($params['status']) ? $params['status'] : 0;
        $save['create_time'] = time();
        // 判断是否商户号
        if(session('ext_user.is_contact')==0){
            if($params['contact']==0){
                $save['is_contact'] = 0;
            }else if($params['contact']==1){
                $save['is_contact'] = 1;
                if($params['method']==0){
                    $save['contact_number'] = $params['selectnumber'];
                }else if($params['method']==1){
                    $save['contact_number'] = $params['setnumber'];
                }
            }else if($params['contact']==3){
                $save['is_contact'] = 3;
                $save['commission'] = $params['commission'];
                if(isset($params['commissionId'])){
                    $save['commissionId'] = $params['commissionId'];
                }
                if(isset($params['payable_bn_id'])){
                    $save['payable_bn_id'] = $params['payable_bn_id'];
                }
                if(isset($params['mechanismId'])){
                    $save['mechanismId'] = $params['mechanismId'];
                }
                if(isset($params['mechanismAdmin'])){
                    $save['mechanismAdmin'] = $params['mechanismAdmin'];
                }
                if(isset($params['account_number'])){
                    $save['account_number'] = $params['account_number'];
                }
                if(isset($params['account_name'])){
                    $save['account_name'] = $params['account_name'];
                }
                if(isset($params['bank_id'])&&isset($params['bank_name'])){
                    $save['bank_id'] = $params['bank_id'];
                    $save['bank_name'] = $params['bank_name'];
                }
            }
        }else{
            // 员工商户号和商家相同
            $save['is_contact'] = 2;
            $save['contact_number'] = session('ext_user.contact_number');
        }
        return $this->insertGetId($save);
    }

    public function userrow(array $params) {
        $update = array();
        $update['update_time'] = time();
        $update['zid'] = $params['zid'];
        if(!empty($params['nick'])){
            $update['nick'] = $params['nick'];
        }
        if(!empty($params['password'])){
            $update['password'] = md5($params['password']);
        }
        if(!empty($params['head'])){
            $update['head'] = $params['head'];
        }
        return $this->update($update);
    }

    public function userUpe(array $params) {
        $save = array(
            'uid' => $params['id'],
            'nick'=> $params['nick'],
            'email'=>$params['email'],
            'mobile'=> $params['mobile'],
            'status' => isset($params['status']) ? $params['status'] : 0,
            'update_time' => time()
        );
        if(!empty($params['password'])){
            $save['password'] = md5($params['password']);
        }
        if(!empty($params['head'])){
            $save['head'] = $params['head'];
        }
        if(isset($params['payable_bn_id'])){
            $save['payable_bn_id'] = $params['payable_bn_id'];
        }
        if(isset($params['account_number'])){
            $save['account_number'] = $params['account_number'];
        }
        if(isset($params['account_name'])){
            $save['account_name'] = $params['account_name'];
        }
        if(isset($params['bank_id'])&&isset($params['bank_name'])){
            $save['bank_id'] = $params['bank_id'];
            $save['bank_name'] = $params['bank_name'];
        }
        if(session('ext_user.is_contact')==0){
            return $this->where("zid",$params['zid'])->update($save);
            
        }else{
            $contact_number = session('ext_user.contact_number');
            return $this->where("zid",$params['zid'])->where('contact_number',$contact_number)->update($save);
        }
    }

    public static function read($where=array()) {
        if(session('ext_user.is_contact')==0){
            return Db::name('user')->alias('a')->join(['groupdata'=>'b', 'mos_'], 'a.uid = b.id','left')->join(['contact'=>'c', 'mos_'], 'a.contact_number = c.number','left') -> field('a.uid,a.name,a.nick,a.zid,a.status,b.title,b.rules,c.name as cname')->where($where)->order('a.zid desc')->paginate(10);
        }else{
            $contact_number = session('ext_user.contact_number');
            return Db::name('user')->alias('a')->join(['groupdata'=>'b', 'mos_'], 'a.uid = b.id','left')->join(['contact'=>'c', 'mos_'], 'a.contact_number = c.number','left') -> field('a.uid,a.name,a.nick,a.zid,a.status,b.title,b.rules,c.name as cname')->where($where)->where('b.contact_number',$contact_number)->order('a.zid desc')->paginate(10);
        }
    }

    public static function row($zid) {
        return Db::name('user')->alias('a')->join(['groupdata'=>'b', 'mos_'], 'a.uid = b.id')->where('a.zid',$zid)-> field('name,nick,status,title,head')->find();
    }

    public static function login($name, $password,$prex) {
        $where['name'] = $name;
        // $where['password'] = md5($password);
        // $where['status'] = 1;
        $user = loader::model("user")->where('is_contact',0)->where($where)->find();
        if ($user) {
            if(md5($password)==$user['password']){
                if($user['user_status']==1){
                    unset($user["password"]);
                    session($prex, $user);
                    return 0;
                }else{
                    return 2;
                }
            }else{
                return 3;
            }
            
        } else {
            return 1;
        }
    }

    public static function courtlogin($name, $password,$prex) {
        $where['name'] = $name;
        $user = DB::name("user")->where('is_contact',4)->where($where)->find();
        $court = DB::name('FoodCourt')->field('id as courtId,name as courtName,disable')->where('number',$user['contact_number'])->find();
        if ($user&&$court) {
            if(md5($password)==$user['password']){
                if($user['status']==1&&$court['disable']==1){
                    $user['courtId'] = $court['courtId'];
                    $user['courtName'] = $court['courtName'];
                    unset($user["password"]);
                    session($prex, $user);
                    return 0;
                }else{
                    return 2;
                }
            }else{
                return 3;
            }
            
        } else {
            return 1;
        }
    }

    public static function mobileLogin($name, $password, $prex) {
        $where['name'] = $name;
        // $where['password'] = md5($password);
        $user = loader::model("user")->where('is_contact',['=',1],['=',2],'or')->where($where)->find();
        if ($user) {
            if(md5($password)==$user['password']){
                if($user['user_status']==1){
                    $contact = Db::name("contact")->where('number',$user['contact_number'])->find();
                    $user['laterPay'] = $contact['laterPay'];
                    unset($user["password"]);
                    session($prex, $user);
                    $return['code'] = 1;
                }else{
                    $return['code']    = 0;
                    $return['userid']  = $user['zid'];
                    $return['number']  = $user['is_contact'];
                    $return['contact'] = $user['contact_number'];
                }
            }else{
                $return['code'] = 3;
            }
            
        } else {
            $return['code'] = 2;
        }
        return $return;
    }

    public static function printerLogin($printer, $prex) {
        $where['contact_number'] = $printer['contactNumber'];
        // $where['password'] = md5($password);
        $user = loader::model("user")->where('is_contact',1)->where($where)->find();
        if ($user) {
            if($user['user_status']==1){
                unset($user["password"]);
                session($prex, $user);
                $return['code'] = 1;
            }else{
                $return['code']    = 0;
                $return['userid']  = $user['zid'];
                $return['number']  = $user['is_contact'];
                $return['contact'] = $user['contact_number'];
            }
        } else {
            $return['code'] = 2;
        }
        return $return;
    }


    public static function marketlogin($name, $password,$prex) {
        $where['name'] = $name;
        // $where['password'] = md5($password);
        // $where['status'] = 1;
        $user = loader::model("user")->where('is_contact',3)->where($where)->find();
        if ($user) {
            if(md5($password)==$user['password']){
                if($user['user_status']==1){
                    unset($user["password"]);
                    session($prex, $user);
                    //若为机构主账号，查出下属账号id，并保存在session中
                    if($user['mechanismAdmin']==1){
                        $staff = DB::name("user")->field('zid')->where('zid','neq',$user['zid'])->where('is_contact',3)->where('mechanismId',$user['mechanismId'])->select();
                        $staffid = array();
                        foreach ($staff as $key => $val) {
                            $staffid[] = $val['zid'];
                            session($prex.'.staff', $staffid);
                        }
                    }
                    return 0;
                }else{
                    return 2;
                }
            }else{
                return 3;
            }
            
        } else {
            return 1;
        }
    }

    // 保留原始状态数值
    public function getUserStatusAttr($value,$data) {
        return $data['status'];
    }

    public function getStatusAttr($value, $data) {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$value];
    }

    public function deleteuser($id) {
        $articleRow = self::get($id);
        $filename = ".".$articleRow->head;
        if (is_file($filename) && file_exists($filename)) {
            unlink($filename);
        }
        return $articleRow->delete();
    }


}