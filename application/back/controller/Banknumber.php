<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Controller;
use think\Validate;
use think\Loader;
use think\Url;
use think\Db;
class Banknumber extends AdminBase {
    
    public function index() {
        $list = DB::name('bankNumber')
        ->alias('n')
        ->join('mos_bank b','n.bank_id = b.id and b.isDelete = 0','left')
        ->field('n.*,b.bankname as name')
        ->where('n.isDelete',0)
        ->order('n.id desc')
        ->paginate(10);
        // 获取各渠道余额
        // 第一步获取全部结算的收入
        // 各渠道收入数组
        $income = array();
        // 各账号下商家结算支出数组
        $expendcontact = array();
        // 各账号下市场人员支出数组
        $expendmarket = array();
        // 查询各渠道收入
        $payStatements = DB::name('payStatements')->field('payMethodId,sum(balanceMoney) as summoney')->group('payMethodId')->select();
        // 以ID为键保存收入信息
        foreach($payStatements as $key => $val){
            $income[$val['payMethodId']] = $val['summoney'];
        }
        $contactBalance = DB::name('contactStatements')->field('payable_bn_id,sum(balanceMoney) as summoney')->group('payable_bn_id')->select();
        foreach($contactBalance as $key => $val){
            $expendcontact[$val['payable_bn_id']] = $val['summoney'];
        }
        $salesStatementsDay = DB::name('salesStatementsDay')->field('payable_bn_id,sum(commission) as summoney')->group('payable_bn_id')->where('isStatement',1)->select();
        foreach($salesStatementsDay as $key => $val){
            $expendmarket[$val['payable_bn_id']] = $val['summoney'];
        }
        // 获取各渠道所属账号
        $paymethodtree = array();
        $paymethod = DB::name('payMethod')->field('id,bn_id')->select();
        foreach ($paymethod as $key => $val) {
            $paymethodtree[$val['bn_id']][] = $val['id'];
        }
        // 第二步获取各渠道支出
        $this->assign('income',$income);
        $this->assign('contact',$expendcontact);
        $this->assign('market',$expendmarket);
        $this->assign('paymethod',$paymethodtree);
        $this->assign('list',$list);
        $this->assign('pages',$list->render());
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
            $save['bank_id'] = $params['bank'];
            $save['owner'] = $params['name'];
            $save['bank_number'] = $params['number'];
            $save['disable'] = $params['disable'];
            $save['isDelete'] = 0;
            $rule = [
                'bank_id'     => 'require',
                'bank_number' => 'require|max:80|regex:/^\d+[\d\s]+\d+$/',
                'owner'       => 'require|max:40',
                'disable'     => 'require|in:0,1',
            ];
            $msg = [
                'bank_id.require'       => '請选择银行',
                'bank_number.require'   => '請輸入银行账号',
                'bank_number.max'       => '银行账号太长',
                'bank_number.regex'     => '银行账号只能由数字和空格组成',
                'owner.require'         => '请输入户主名称',
                'owner.max'             => '户主名称太长',
                'disable.require'       => '请选择状态',
                'disable.in'            => '状态值不正确',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            $id = DB::name('BankNumber')->insertGetId($save);
            if($id!==false){
                Loader::model('SystemLog')->record("添加銀行账号,ID:[{$id}]");
                return $this->success('添加銀行账号成功', Url::build('Banknumber/index'));
            }else{
                return $this->error('添加銀行账号失敗');
            }
        }else{
            $bank = DB::name('bank')->field('id,bankname')->where('isDelete',0)->select();
            $this->assign('bank',$bank);
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
            $save['id']       = $params['id'];
            $save['bank_id'] = $params['bank'];
            $save['owner'] = $params['name'];
            $save['bank_number'] = $params['number'];
            $save['disable'] = $params['disable'];
            $save['isDelete'] = 0;
            $rule = [
                'bank_id'     => 'require',
                'bank_number' => 'require|max:80|regex:/^\d+[\d\s]+\d+$/',
                'owner'       => 'require|max:40',
                'disable'     => 'require|in:0,1',
            ];
            $msg = [
                'bank_id.require'       => '請选择银行',
                'bank_number.require'   => '請輸入银行账号',
                'bank_number.max'       => '银行账号太长',
                'bank_number.regex'     => '银行账号只能由数字和空格组成',
                'owner.require'         => '请输入户主名称',
                'owner.max'             => '户主名称太长',
                'disable.require'       => '请选择状态',
                'disable.in'            => '状态值不正确',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($save)) {
                return $this->error($validate->getError());
            }
            $id = DB::name('BankNumber')->where('id',$params['id'])->update($save);
            if($id!==false){
                Loader::model('SystemLog')->record("修改銀行账号,ID:[{$id}]");
                return $this->success('修改銀行账号成功', Url::build('Banknumber/index'));
            }else{
                return $this->error('修改銀行账号失敗');
            }
        }else{
            $id = $request->param('id');
            $bn = DB::name('BankNumber')->where('id',$id)->where('isDelete',0)->find();
            $bank = DB::name('bank')->field('id,bankname')->where('isDelete',0)->select();
            $this->assign('bn',$bn);
            $this->assign('bank',$bank);
            return $this->fetch();
        }
    }

    public function detail(){
        $request = Request::instance();
        $param = $request->param();
        if(empty($param['id'])){
            $this->error('请选择正确的账号');
        }
        $where = array();
        $bn = DB::name('BankNumber')
        ->alias('n')
        ->where('n.id',$param['id'])
        ->join('mos_bank b','n.bank_id = b.id and b.isDelete = 0','left')
        ->field('n.*,b.bankname as name')
        ->where('n.isDelete',0)
        ->find();
        // 获取账号下所有渠道
        $method = array();
        $methodNum = 0;
        $paymethod = DB::name('payMethod')->field('id,bn_id')->where('bn_id',$param['id'])->select();
        // 计算余额
        foreach ($paymethod as $key => $val) {
            $method[] = $val['id'];
        }
        if(!empty($method)){
            $payStatements = DB::name('payStatements')->field('sum(balanceMoney) as summoney')->where('payMethodId','in',$method)->find();
        }else{
            $payStatements['summoney'] = 0;
        }
        
        $contactBalance = DB::name('contactStatements')->field('sum(balanceMoney) as summoney')->where('payable_bn_id',$param['id'])->find();
        $salesStatementsDay = DB::name('salesStatementsDay')->field('sum(commission) as summoney')->where('payable_bn_id',$param['id'])
            ->where('isStatement',1)->find();
        $bn_ye = $payStatements['summoney']-$contactBalance['summoney']-$salesStatementsDay['summoney'];
        $items = [1,2,3,4,5,6,7,8,9,10];
        if(!empty($param['start'])&&!empty($param['end'])){
            if(!empty($method)){
                $paystatements = DB::name('payStatements')->where('payAccountDate','between',[$param['start'],$param['end']])->where('payMethodId','in',$method)->select();
            }else{
                $paystatements = [];
            }
            $contactbalance = DB::name('contactStatements')->where('merAccountDate','between',[$param['start'],$param['end']])->where('payable_bn_id',$param['id'])->select();
            $salesstatementsday = DB::name('salesStatementsDay')
            ->where('isStatement',1)
            ->where('balanceDate','between',[$param['start'],$param['end']])
            ->where('payable_bn_id',$param['id'])->select();
        }else if(!empty($param['start'])){
            if(!empty($method)){
                $paystatements = DB::name('payStatements')->where('payAccountDate','>=',$param['start'])->where('payMethodId','in',$method)->select();
            }else{
                $paystatements = [];
            }
            $contactbalance = DB::name('contactStatements')->where('merAccountDate','>=',$param['start'])->where('payable_bn_id',$param['id'])->select();
            $salesstatementsday = DB::name('salesStatementsDay')
            ->where('isStatement',1)
            ->where('balanceDate','>=',$param['start'])
            ->where('payable_bn_id',$param['id'])->select();
        }else if(!empty($param['end'])){
            if(!empty($method)){
                $paystatements = DB::name('payStatements')->where('payAccountDate','<=',$param['end'])->where('payMethodId','in',$method)->select();
            }else{
                $paystatements = [];
            }
            $contactbalance = DB::name('contactStatements')->where('merAccountDate','<=',$param['end'])->where('payable_bn_id',$param['id'])->select();
            $salesstatementsday = DB::name('salesStatementsDay')
            ->where('isStatement',1)
            ->where('balanceDate','<=',$param['end'])
            ->where('payable_bn_id',$param['id'])->select();
        }else{
            if(!empty($method)){
                $paystatements = DB::name('payStatements')->where('payMethodId','in',$method)->select();
            }else{
                $paystatements = [];
            }
            $contactbalance = DB::name('contactStatements')->where('payable_bn_id',$param['id'])->select();
            $salesstatementsday = DB::name('salesStatementsDay')->where('payable_bn_id',$param['id'])->select();
        }
        // 三个表数组合并然后排序
        $items = array();
        foreach ($paystatements as $k1 => $val1) {
            $val1['type'] = 1;
            $val1['time'] = strtotime($val1['payAccountDate']);
            $items[] = $val1;
        }
        foreach ($contactbalance as $k => $val2) {
            $val2['type'] = 2;
            $val2['time'] = strtotime($val2['merAccountDate']);
            $items[] = $val2;
        }

        foreach ($salesstatementsday as $k => $val3) {
            $val3['type'] = 3;
            $val3['time'] = strtotime($val3['ctime']);
            $items[] = $val3;
        }
        if(!empty($items)){
            $items = my_sort($items,'time',SORT_DESC,SORT_NUMERIC);
        }
        vendor("Pg.Pg");
        //['simple'=>false ,'allCounts'=>true,'nowAllPage' => true,'toPage'=>true,'prev_mark'=> '«', 'next_mark'=>'»']
        $pagination = new \Pg\Pg($items,10,['simple'=>false ,'allCounts'=>false,'nowAllPage' => false,'toPage'=>false]);
        // $pagination = new pagination($items,1,['simple'=>false ,'allCounts'=>true,'nowAllPage' => true,'toPage'=>true]);
        //分页后的数组
        $item = $pagination ->getItem();
        //分页的样式
        $page = $pagination ->render();
        $this->assign('param',$param);
        $this->assign('bn_ye',$bn_ye);
        $this->assign('page',$page);
        $this->assign('item',$item);
        $this->assign('bn',$bn);
        return $this->fetch();
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
        $res = DB::name('BankNumber')->where('id',$id)->update(['isDelete'=>1]);
        Loader::model('SystemLog')->record("銀行账号删除,ID:[{$id}]");
        return $this->success('銀行账号删除成功', Url::build('banknumber/index'));
    }
}