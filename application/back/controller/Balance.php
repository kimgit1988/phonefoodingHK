<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\Db;
use think\Url;
use think\File;
use think\Loader;
use think\Request;
use think\Validate;
use think\Controller;
class Balance extends AdminBase {
    
    public function contactbalance() {
        $param = input('param.');
        $where = array();
        if(isset($param['contact'])&&$param['contact']!==''){
            $where['b.contactNumber'] = $param['contact'];
        }
        if(isset($param['paymethod'])&&$param['paymethod']!==''){
            $where['b.payMethodId'] = $param['paymethod'];
        }
        if(isset($param['type'])&&$param['type']!==''){
            $where['b.balanceType'] = $param['type'];
        }
        if(isset($param['contactstatus'])&&$param['contactstatus']!==''){
            $where['b.isMerSettlements'] = $param['contactstatus'];
        }
        if(isset($param['paystatus'])&&$param['paystatus']!==''){
            $where['b.isPaySettlements'] = $param['paystatus'];
        }
        if(isset($param['change'])&&$param['change']!==''){
            $where['b.balanceChange'] = $param['change'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['b.balanceNumber'] = ['like','%'.$param['search'].'%'];
        }
        if(isset($param['calcaccount'])&&$param['calcaccount']!==''){
            $where['b.merCalcAccountDate'] = $param['calcaccount'];
        }
        if(isset($param['account'])&&$param['account']!==''){
            $where['b.merAccountDate'] = $param['account'];
        }
        if(isset($param['paycalcaccount'])&&$param['paycalcaccount']!==''){
            $where['b.payCalcAccountDate'] = $param['paycalcaccount'];
        }
        if(isset($param['payaccount'])&&$param['payaccount']!==''){
            $where['b.payAccountDate'] = $param['payaccount'];
        }
        if(isset($param['payStart'])&&$param['payStart']!==''&&isset($param['payEnd'])&&$param['payEnd']!==''){
            $where['b.payTime'] = ['between',$param['payStart'].','.$param['payEnd']];
        }else if(isset($param['payStart'])&&$param['payStart']!==''){
            $where['b.payTime'] = ['>=',$param['payStart']];
        }else if(isset($param['payEnd'])&&$param['payEnd']!==''){
            $where['b.payTime'] = ['<=',$param['payEnd']];
        }
        if(isset($param['ctimeStart'])&&$param['ctimeStart']!==''&&isset($param['ctimeEnd'])&&$param['ctimeEnd']!==''){
            $where['b.ctime'] = ['between',$param['ctimeStart'].','.$param['ctimeEnd']];
        }else if(isset($param['ctimeStart'])&&$param['ctimeStart']!==''){
            $where['b.ctime'] = ['>=',$param['ctimeStart']];
        }else if(isset($param['ctimeEnd'])&&$param['ctimeEnd']!==''){
            $where['b.ctime'] = ['<=',$param['ctimeEnd']];
        }
        $balance = DB::name('contactBalance')
        ->alias('b')
        ->join('mos_pay_method p','b.payMethodId = p.id','left')
        ->join('mos_contact c','b.contactNumber = c.number','left')
        ->field('*,b.id as id,c.name as name,b.ctime as ctime,p.name as pname')
        ->where($where)
        ->paginate(10,false,['query'=>$param]);
        $paymethod = DB::name('payMethod')->field('id,name')->where('disable',1)->where('isDelete',0)->select();
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        $balanceType = ['1'=>'交易','2'=>'提现','3'=>'手续费'];
        $balanceChange = ['1'=>'收入','2'=>'支出'];
        // var_dump($param);
        $this->assign('param',$param);
        $this->assign('lists',$balance);
        $this->assign('paymethod',$paymethod);
        $this->assign('pages',$balance->render());
        $this->assign('contact',$contact);
        $this->assign('balanceType',$balanceType);
        $this->assign('balanceChange',$balanceChange);
        return $this->fetch();
    }

    public function contactstatements() {
        $param = input('param.');
        $where = array();
        if(isset($param['contact'])&&$param['contact']!==''){
            $where['s.contactNumber'] = $param['contact'];
        }
        if(isset($param['account'])&&$param['account']!==''){
            $where['s.merAccountDate'] = $param['account'];
        }
        if(isset($param['ctimeStart'])&&$param['ctimeStart']!==''&&isset($param['ctimeEnd'])&&$param['ctimeEnd']!==''){
            $where['s.ctime'] = ['between',$param['ctimeStart'].','.$param['ctimeEnd']];
        }else if(isset($param['ctimeStart'])&&$param['ctimeStart']!==''){
            $where['s.ctime'] = ['>=',$param['ctimeStart']];
        }else if(isset($param['ctimeEnd'])&&$param['ctimeEnd']!==''){
            $where['s.ctime'] = ['<=',$param['ctimeEnd']];
        }
        $statements = DB::name('contactStatements')
        ->alias('s')
        ->join('mos_contact c','s.contactNumber = c.number','left')
        ->field('*,s.id as id,c.name as name,s.ctime as ctime')
        ->where($where)
        // ->fetchsql(true)
        // ->select();
        // var_dump($statements);die;
        ->paginate(10,false,['query'=>$param]);
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('lists',$statements);
        $this->assign('pages',$statements->render());
        return $this->fetch();
    }

    public function channlestatements() {
        $param = input('param.');
        $where = array();
        if(isset($param['paymethod'])&&$param['paymethod']!==''){
            $where['s.payMethodId'] = $param['paymethod'];
        }
        if(isset($param['account'])&&$param['account']!==''){
            $where['s.payAccountDate'] = $param['account'];
        }
        if(isset($param['ctimeStart'])&&$param['ctimeStart']!==''&&isset($param['ctimeEnd'])&&$param['ctimeEnd']!==''){
            $where['s.ctime'] = ['between',$param['ctimeStart'].','.$param['ctimeEnd']];
        }else if(isset($param['ctimeStart'])&&$param['ctimeStart']!==''){
            $where['s.ctime'] = ['>=',$param['ctimeStart']];
        }else if(isset($param['ctimeEnd'])&&$param['ctimeEnd']!==''){
            $where['s.ctime'] = ['<=',$param['ctimeEnd']];
        }
        $balance = DB::name('payStatements')
        ->alias('s')
        ->join('mos_pay_method p','s.payMethodId = p.id','left')
        ->field('*,s.id as id,p.name as name,s.ctime as ctime')
        ->where($where)
        ->paginate(10,false,['query'=>$param]);
        $paymethod = DB::name('payMethod')->field('id,name')->where('disable',1)->where('isDelete',0)->select();
        $this->assign('param',$param);
        $this->assign('lists',$balance);
        $this->assign('paymethod',$paymethod);
        $this->assign('pages',$balance->render());
        return $this->fetch();
    }

    public function sendxls(){
        $request = Request::instance();
        if ($request->isPost()) {
            $param = $request->param();
            $res = array();
            $list = array();
            $contactNumber = array();
            $rule = [
                'account' => 'require|dateFormat:Y-m-d',
            ];
            $msg = [
                'account.require' => '請選擇結算日期',
                'account.dateFormat' => '結算日期值不正確',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($param)) {
                return $this->error($validate->getError());
            }
            $statements = DB::name('contactStatements')
            ->alias('s')
            ->join('mos_contact c','s.contactNumber = c.number','left')
            ->join('mos_user u','s.contactNumber = u.contact_number','left')
            ->field('s.*,c.name,u.email,u.nick as nick,s.id as id,c.name as name,s.ctime as ctime')
            ->where('s.merAccountDate',$param['account'])
            ->where('u.is_contact',1)
            ->select();
            if(empty($statements)){
                return $this->error('該日期還沒有結算數據');
            }
            foreach ($statements as $key => $val) {
                $list[$val['contactNumber']] = $val;
                $list[$val['contactNumber']]['_balance'] = array();
                $list[$val['contactNumber']]['xlsname']  = md5($val['contactNumber'].date('YmdHis').mt_rand(000000,999999)).'.xls';
                $list[$val['contactNumber']]['path']     = ROOT_PATH.'public/create/xls/tmp/'.date("Ymd").'/';
                $contactNumber[] = $val['contactNumber'];
            }
            $balance = DB::name('contactBalance')
            ->where('merAccountDate',$param['account'])
            ->where('contactNumber','in',$contactNumber)
            ->select();
            foreach ($balance as $k => $v) {
                $list[$v['contactNumber']]['_balance'][] = $v;
            }
            foreach($list as $val){
                @$res[$val['contactNumber']][] = excelXls($val);
                @$send[$val['contactNumber']][] = sendEmail($val['path'].$val['xlsname'],$val['email'],$val['nick'],$val['name'],$val['name'].'日結單');
                unlink($val['path'].$val['xlsname']);
            }
            return $this->success('發送成功');
        }else{
            return $this->fetch();
        }
    }

    public function downelx(){
        $id = input('id');
        $statements = DB::name('contactStatements')
            ->alias('s')
            ->join('mos_contact c','s.contactNumber = c.number','left')
            ->join('mos_user u','s.contactNumber = u.contact_number','left')
            ->field('s.*,c.name,u.email,u.nick as nick,s.id as id,c.name as name,s.ctime as ctime')
            ->where('u.is_contact',1)
            // ->fetchsql(true)
            ->find($id);
        $statements['_balance'] = array();
        $statements['xlsname']  = md5($statements['contactNumber'].date('YmdHis').mt_rand(000000,999999)).'.xls';
        $statements['path']     = ROOT_PATH.'public/create/xls/tmp/'.date("Ymd").'/';
        $balance = DB::name('contactBalance')
            ->where('merAccountDate',$statements['merAccountDate'])
            ->where('contactNumber',$statements['contactNumber'])
            ->select();

        foreach ($balance as $k => $v) {
            $statements['_balance'][] = $v;
        }
        $res = excelXls($statements,1);
    }

    public function sale(){
        $param = input('param.');
        $where = array();
        if(isset($param['mechanism'])&&$param['mechanism']!==''){
            $where['salesId'] = $param['mechanism'];
        }
        if(isset($param['date'])&&$param['date']!==''){
            $where['balanceDate'] = $param['date'];
        }
        if(isset($param['status'])&&$param['status']!==''){
            $where['isStatement'] = $param['status'];
        }
        $statements = DB::name('salesStatementsDay')->where($where)->paginate(10,false,['query'=>$param]);
        $mechanism = DB::name('user')
        ->alias('u')
        ->join('mos_mechanism m','u.mechanismId = m.id','left')
        ->field('u.zid as id,m.id as mid,m.name as mname,u.nick as nick,u.mechanismId')
        ->where('u.mechanismAdmin = 1 OR u.mechanismId is null OR u.mechanismId = 0')
        ->where('u.is_contact',3)
        ->order('u.zid desc')
        ->select();
        $this->assign('param',$param);
        $this->assign('lists',$statements);
        $this->assign('mechanism',$mechanism);
        $this->assign('pages',$statements->render());
        return $this->fetch();
    }

    public function salemonth(){
        $param = input('param.');
        $where = array();
        if(isset($param['mechanism'])&&$param['mechanism']!==''){
            $where['salesId'] = $param['mechanism'];
        }
        if(isset($param['date'])&&$param['date']!==''){
            $where['balanceDate'] = $param['date'];
        }
        $statements = DB::name('salesStatementsTotal')->where($where)->paginate(10,false,['query'=>$param]);
        $mechanism = DB::name('user')
        ->alias('u')
        ->join('mos_mechanism m','u.mechanismId = m.id','left')
        ->field('u.zid as id,m.id as mid,m.name as mname,u.nick as nick,u.mechanismId')
        ->where('u.mechanismAdmin = 1 OR u.mechanismId is null OR u.mechanismId = 0')
        ->where('u.is_contact',3)
        ->order('u.zid desc')
        // ->fetchsql(true)
        // ->select();var_dump($mechanism);die;
        ->select();
        $this->assign('param',$param);
        $this->assign('lists',$statements);
        $this->assign('mechanism',$mechanism);
        $this->assign('pages',$statements->render());
        return $this->fetch();
    }

}