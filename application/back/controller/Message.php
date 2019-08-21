<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use think\File;
use think\Request;
use think\Validate;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Message extends AdminBase {
    
    public function index() {
        $param = input('get.');
        $db = DB::name('Message');
        if(isset($param['contact'])&&$param['contact']!==''){
            $db->where('m.message_contact_number',$param['contact']);
        }
        if(isset($param['status'])&&$param['status']!==''){
            $db->where('m.message_status',$param['status']);
        }
        if(isset($param['search'])&&$param['search']!==''){
            $db->where('m.message_name|m.message_phone|m.message_email','like','%'.$param['search'].'%');
        }
        $message = $db->alias('m')->field('m.*,c.name')->join('mos_contact c','m.message_contact_number = c.number','left')->order('m.id desc')->where(['m.isDelete'=>0])->paginate(10,false,['query'=>$param]);
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        $this->assign('message', $message);
        $this->assign('param', $param);
        $this->assign('contact',$contact);
        $this->assign('pages',$message->render());
        return $this->fetch();
    }

    public function replylist(){
        $request = Request::instance();
        if ($request->isPost()) {
            $param = input('param.');
            $aid = session('ext_user.zid');
            $reply = [
                'reply_mid'     => $param['id'],
                'reply_aid'     => $aid,
                'reply_type'    => 2,
                'reply_content' => !empty($param['reply'])?$param['reply']:'',
                'reply_ctime'   => time(),
            ];
            $rule = [
                'reply_mid'     => 'require|number',
                'reply_content' => 'require|max:250',
            ];
            $msg = [
                'reply_mid.require'     => '頁面錯誤',
                'reply_mid.number'      => '頁面錯誤',
                'reply_content.require' => '請輸入問題',
                'reply_content.max'     => '問題不能超過250字',
            ];
            $validate = new Validate($rule, $msg);
            if (!$validate->check($reply)) {
                return $this->error($validate->getError());
            }
            $res = 1;
            // 启动事务
            Db::startTrans();
            try{
                DB::name('messageReply')->insert($reply);
                Db::name('message')->where('id',$param['id'])->update(['message_status'=>2]);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                $res = 0;
                // 回滚事务
                Db::rollback();
            }
            if($res){
                return $this->success('提交問題成功',url('message/replylist',['id'=>$param['id']]));
            }else{
                return $this->error('提交問題失敗');
            }
        }else{
            $id = input('id');
            $update = DB::name('messageReply')
                    ->where('reply_mid',$id)
                    ->where('reply_status','neq',1)
                    ->where('reply_type',1)
                    ->where('isDelete',0)
                    ->order('id asc')
                    ->update(['reply_status'=>1]);
            $message = DB::name('Message')
                ->alias('m')
                ->field('m.*,c.name')
                ->join('mos_contact c','m.message_contact_number = c.number','left')
                ->where(['m.isDelete'=>0])
                ->where('m.id',$id)
                ->where('m.isDelete',0)
                ->order('m.id desc')
                ->find();
            $reply = DB::name('MessageReply')->alias('r')->field('r.*,u.name')->join('mos_user u','r.reply_aid = u.zid','left')->order('r.id desc')->where('r.isDelete',0)->where('reply_mid',$id)->order('r.id asc')->select();
            $this->assign('message', $message);
            $this->assign('reply',$reply);
            return $this->fetch('reply');
        }
    }

}