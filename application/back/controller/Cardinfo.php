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
class Cardinfo extends AdminBase {
    
    public function index() {
        $param = input('param.');
        $cardDB = DB::name('CardInfo');
        if(isset($param['cardType'])&&$param['cardType']!==''){
            $cardDB->where('cardType',$param['cardType']);
        }
        if(isset($param['useType'])&&$param['useType']!==''){
            $cardDB->where('useType',$param['useType']);
            if($param['useType']==2&&isset($param['contactNumber'])&&$param['contactNumber']!==''){
                $cardDB->where('contactNumber',$param['contactNumber']);
            }
        }
        if(isset($param['status'])&&$param['status']!==''){
            $cardDB->where('status',$param['status']);
        }
        if(isset($param['search'])&&$param['search']!==''){
            $cardDB->where('name|cardSN','like','%'.$param['search'].'%');
        }
        $card = $cardDB->order('id desc')->where('isDelete',0)->paginate(10);
        $contact =DB::name('contact')->field('id,name,number')->where('isDelete',0)->where('disable',1)->order('id desc')->select();
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('card',$card);
        $this->assign('pages',$card->render());
        return $this->fetch();
    }

    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $cardSN = time().mt_rand(1000,9999);
            // 填入基本屬性
            $save = array(
                'cardSN'           => $cardSN,
                'name'             => isset($params['name'])?trim($params['name']):'',
                'notice'           => isset($params['notice'])?$params['notice']:'',
                'custom'           => isset($params['custom'])?$params['custom']:'',
                'cardCount'        => isset($params['count'])?$params['count']:'',
                'cardNumber'       => isset($params['count'])?$params['count']:'',
                'limitNumber'      => isset($params['limitNumber'])?$params['limitNumber']:'',
                'receiptStartTime' => isset($params['receiptStartTime'])?$params['receiptStartTime']:'',
                'receiptEndTime'   => isset($params['receiptEndTime'])?$params['receiptEndTime']:'',
                'status'           => isset($params['status'])?$params['status']:1,
                'isDelete'         => 0,
                'ctime'            => time(),
                'utime'            => time(),
            );

            // 根據卡卷使用類型填入屬性
            if(!empty($params['useType'])){
                $save['useType'] = $params['useType'];
                if($params['useType']==2){
                    $contact = DB::name('contact')->field('id,name,number')->where('number',$params['contactNumber'])->where('isDelete',0)->find();
                    if(!empty($contact)){
                        $save['contactName'] = $contact['name'];
                        $save['contactNumber'] = $contact['number'];
                    }else{
                        $this->error('請選擇餐廳!');
                    }
                }
            }else{
                $this->error('請選擇可用商戶!');
            }

            // 根據卡卷折扣類型填入屬性
            if(!empty($params['cardType'])){
                $save['cardType'] = $params['cardType'];
                $save['minDiscountPaid'] = isset($params['minDiscountPaid'])?$params['minDiscountPaid']:'';
                if($params['cardType']==1){
                    $save['discountRate'] = $params['discountRate'];
                    $save['maxDiscountRateMoney'] = isset($params['maxDiscountRateMoney'])?$params['maxDiscountRateMoney']:null;
                }else if($params['cardType']==2){
                    $save['discountMoney'] = $params['discountMoney'];
                }else if($params['cardType']==3){
                    $save['discountMoney'] = $params['discountMoney'];
                    $food = DB::name('Goods')->field('id,name,number')->where('id',$params['goodId'])->where('isDelete',0)->find();
                    if(!empty($food)){
                        $save['goodsId'] = $food['id'];
                        $save['goodsName'] = $food['name'];
                        $save['goodsNumber'] = $food['number'];
                    }else{
                        $this->error('請選擇菜品!');
                    }
                }
            }else{
                $this->error('請選擇卡券類型!');
            }

            if(!empty($params['distributeType'])){
                $save['distributeType'] = $params['distributeType'];
                if($params['distributeType']==1){
                    $save['distributeUrl'] = url('wxweb/index/getqrcodecard',['cardno'=>$cardSN]);
                }else if($params['distributeType']==2){
                    $save['distributeMoney'] = $params['distributeMoney'];
                }
            }else{
                $this->error('請選擇卡券派發方式!');
            }

            if(!empty($params['timeType'])){
                $save['timeType'] = $params['timeType'];
                if($params['timeType']==1){
                    if(empty($params['timeLength']['year'])&&empty($params['timeLength']['month'])&&empty($params['timeLength']['day'])&&empty($params['timeLength']['hour'])&&empty($params['timeLength']['minute'])&&empty($params['timeLength']['second'])){
                        $this->error('請輸入有效時長');
                    }
                    $timeLength = array(
                        'year' => !empty($params['timeLength']['year'])?$params['timeLength']['year']:0,
                        'month' => !empty($params['timeLength']['month'])?$params['timeLength']['month']:0,
                        'day' => !empty($params['timeLength']['day'])?$params['timeLength']['day']:0,
                        'hour' => !empty($params['timeLength']['hour'])?$params['timeLength']['hour']:0,
                        'minute' => !empty($params['timeLength']['minute'])?$params['timeLength']['minute']:0,
                        'second' => !empty($params['timeLength']['second'])?$params['timeLength']['second']:0,
                    );
                    $save['timeLength'] = implode('-', $timeLength);
                }else if($params['timeType']==2){
                    $save['validStartTime'] = isset($params['validStartTime'])?$params['validStartTime']:'';
                    $save['validEndTime']   = isset($params['validEndTime'])?$params['validEndTime']:'';
                }
            }else{
                $this->error('請選擇有效期類型');
            }

            if (loader::validate('Cardinfo')->scene('adminAdd')->check($save) === false) {
                return $this->error(loader::validate('Cardinfo')->getError());
            }
            // 補充驗證和字段設置start做一些需要驗證后才能完成的驗證和修改
            $save['receiptStartTime'] = strtotime($save['receiptStartTime']);
            $save['receiptEndTime'] = strtotime($save['receiptEndTime']);
            if($save['receiptStartTime']>=$save['receiptEndTime']){
                $this->error('領取結束時間不能早於領取開始時間');
            }
            if($params['timeType']==2){
                $save['validStartTime'] = strtotime($params['validStartTime']);
                $save['validEndTime']   = strtotime($params['validEndTime']);
                if($save['validStartTime']>=$save['validEndTime']){
                    $this->error('有效期結束時間不能早於有效期開始時間');
                }
            }
            if(isset($save['maxDiscountRateMoney'])&&$save['maxDiscountRateMoney']===''){
                $save['maxDiscountRateMoney'] = ['exp','NULL'];
            }
            if($params['cardType']==2&&$params['cardType']==3){
                if($save['discountMoney']>=$save['minDiscountPaid']){
                    $this->error('減免金額需小於最低消費金額!');
                }
            }
            // 補充驗證和字段設置end
            $id = DB::name('CardInfo')->insertGetId($save);

            if($id!==false){
                Loader::model('SystemLog')->record("添加卡券,ID:[{$id}]");
                return $this->success('添加卡券成功', Url::build('Cardinfo/index'));
            }else{
                return $this->error('添加卡券失敗');
            }
        }else{
            $contact =DB::name('contact')->field('id,name,number')->where('isDelete',0)->where('disable',1)->order('id desc')->select();
            $this->assign('contact',$contact);
            return $this->fetch();
        }
    }

    public function edit() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $save['id']       = $params['id'];
            // 填入基本屬性
            $save = array(
                'id'               => isset($params['id'])?$params['id']:'',
                'name'             => isset($params['name'])?trim($params['name']):'',
                'notice'           => isset($params['notice'])?$params['notice']:'',
                'custom'           => isset($params['custom'])?$params['custom']:'',
                'limitNumber'      => isset($params['limitNumber'])?$params['limitNumber']:'',
                'receiptStartTime' => isset($params['receiptStartTime'])?$params['receiptStartTime']:'',
                'receiptEndTime'   => isset($params['receiptEndTime'])?$params['receiptEndTime']:'',
                'status'           => isset($params['status'])?$params['status']:1,
                'isDelete'         => 0,
                'utime'            => time(),
            );

            // 根據卡卷使用類型填入屬性
            if(!empty($params['useType'])){
                $save['useType'] = $params['useType'];
                if($params['useType']==2){
                    $contact = DB::name('contact')->field('id,name,number')->where('number',$params['contactNumber'])->where('isDelete',0)->find();
                    if(!empty($contact)){
                        $save['contactName'] = $contact['name'];
                        $save['contactNumber'] = $contact['number'];
                    }else{
                        $this->error('請選擇餐廳!');
                    }
                }
            }else{
                $this->error('請選擇可用商戶!');
            }

            // 根據卡卷折扣類型填入屬性
            if(!empty($params['cardType'])){
                $save['cardType'] = $params['cardType'];
                $save['minDiscountPaid'] = isset($params['minDiscountPaid'])?$params['minDiscountPaid']:'';
                if($params['cardType']==1){
                    $save['discountRate'] = $params['discountRate'];
                    $save['maxDiscountRateMoney'] = isset($params['maxDiscountRateMoney'])?$params['maxDiscountRateMoney']:'';
                }else if($params['cardType']==2){
                    $save['discountMoney'] = $params['discountMoney'];
                }else if($params['cardType']==3){
                    $save['discountMoney'] = $params['discountMoney'];
                    $food = DB::name('Goods')->field('id,name,number')->where('id',$params['goodId'])->where('isDelete',0)->find();
                    if(!empty($food)){
                        $save['goodsId'] = $food['id'];
                        $save['goodsName'] = $food['name'];
                        $save['goodsNumber'] = $food['number'];
                    }else{
                        $this->error('請選擇菜品!');
                    }
                }
            }else{
                $this->error('請選擇卡券類型!');
            }

            if(!empty($params['distributeType'])){
                $save['distributeType'] = $params['distributeType'];
                if($params['distributeType']==1){
                    $card = DB::name('CardInfo')->field('cardSN')->where('id',$params['id'])->find();
                    $save['distributeUrl'] = 'http://'.$_SERVER['HTTP_HOST'].url('wxweb/index/getqrcodecard',['cardno'=>$card['cardSN']]);
                }else if($params['distributeType']==2){
                    $save['distributeMoney'] = $params['distributeMoney'];
                }
            }else{
                $this->error('請選擇卡券派發方式!');
            }

            if(!empty($params['timeType'])){
                $save['timeType'] = $params['timeType'];
                if($params['timeType']==1){
                    if(empty($params['timeLength']['year'])&&empty($params['timeLength']['month'])&&empty($params['timeLength']['day'])&&empty($params['timeLength']['hour'])&&empty($params['timeLength']['minute'])&&empty($params['timeLength']['second'])){
                        $this->error('請輸入有效時長');
                    }
                    $timeLength = array(
                        'year' => !empty($params['timeLength']['year'])?$params['timeLength']['year']:0,
                        'month' => !empty($params['timeLength']['month'])?$params['timeLength']['month']:0,
                        'day' => !empty($params['timeLength']['day'])?$params['timeLength']['day']:0,
                        'hour' => !empty($params['timeLength']['hour'])?$params['timeLength']['hour']:0,
                        'minute' => !empty($params['timeLength']['minute'])?$params['timeLength']['minute']:0,
                        'second' => !empty($params['timeLength']['second'])?$params['timeLength']['second']:0,
                    );
                    $save['timeLength'] = implode('-', $timeLength);
                }else if($params['timeType']==2){
                    $save['validStartTime'] = isset($params['validStartTime'])?$params['validStartTime']:'';
                    $save['validEndTime']   = isset($params['validEndTime'])?$params['validEndTime']:'';
                }
            }else{
                $this->error('請選擇有效期類型');
            }

            if (loader::validate('Cardinfo')->scene('adminEdit')->check($save) === false) {
                return $this->error(loader::validate('Cardinfo')->getError());
            }
            // 補充驗證和字段設置start做一些需要驗證后才能完成的驗證和修改
            $save['receiptStartTime'] = strtotime($save['receiptStartTime']);
            $save['receiptEndTime'] = strtotime($save['receiptEndTime']);
            if($save['receiptStartTime']>=$save['receiptEndTime']){
                $this->error('領取結束時間不能早於領取開始時間');
            }
            if($params['timeType']==2){
                $save['validStartTime'] = strtotime($params['validStartTime']);
                $save['validEndTime']   = strtotime($params['validEndTime']);
                if($save['validStartTime']>=$save['validEndTime']){
                    $this->error('有效期結束時間不能早於有效期開始時間');
                }
            }
            if($params['cardType']==2&&$params['cardType']==3){
                if($save['discountMoney']>=$save['minDiscountPaid']){
                    $this->error('減免金額需小於最低消費金額!');
                }
            }
            if(isset($save['maxDiscountRateMoney'])&&$save['maxDiscountRateMoney']===''){
                $save['maxDiscountRateMoney'] = ['exp','NULL'];
            }
            // 補充驗證和字段設置end
            $res = DB::name('CardInfo')->where('id',$save['id'])->update($save);

            if($res!==false){
                Loader::model('SystemLog')->record("编辑卡券,ID:[{$save['id']}]");
                return $this->success('編輯卡券成功', Url::build('Cardinfo/index'));
            }else{
                return $this->error('編輯卡券失敗');
            }
        }else{
            $id = $request->param('id');
            $card = DB::name('CardInfo')->where('id',$id)->find();
            $contact =DB::name('contact')->field('id,name,number')->where('isDelete',0)->where('disable',1)->order('id desc')->select();
            if(!empty($card['timeLength'])){
                $card['timeLengthList'] = explode('-', $card['timeLength']);
            }
            if($card['useType']==2){
                $goods = DB::name('goods')->field('id,name,number')->where('isDelete',0)->where('disable',1)->where('contactNumber',$card['contactNumber'])->select();
            }else{
                $goods = array();
            }
            $this->assign('goods',$goods);
            $this->assign('contact',$contact);
            $this->assign('card',$card);
            return $this->fetch();
        }
    }

    public function downqrcode(){
        // 下载二维码
        $id = input('id');
        $card = DB::name('CardInfo')->where('id',$id)->where('distributeType',1)->where('isDelete',0)->find();
        if($card){
            if($card['useType']==2){
                // 如果指定商户获取商户二维码;
                $contact = DB::name('contact')->field('id,number,logoUrl')->where('number',$card['contactNumber'])->find();
                $logo = $contact['logoUrl'];
            }else{
                // 否则为空,函数选择系统二维码
                $logo = '';
            }
            $res = add_wx_web_qrcode($card['distributeUrl'],$logo,'CardInfo/qrcode');
            $root_path = config('Rootpath');
            $filename = str_replace($root_path,ROOT_PATH . 'public',$res['msg']);
            $name="卡券二维码.png";
            header('Content-Disposition: attachment; filename='.$name);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Length: '.filesize($filename));
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            readfile($filename);
        }else{
            $this->error('该类型卡券不能生成二维码领取!');
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
        $res = DB::name('CardInfo')->where('id',$id)->update(['isDelete'=>1,'utime'=>time()]);
        Loader::model('SystemLog')->record("卡券删除,ID:[{$id}]");
        return $this->success('卡券删除成功', Url::build('cardinfo/index'));
    }
}