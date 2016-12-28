<?php
namespace Home\Controller;
use Think\Controller;
class PowerCheckController extends Controller {
    public function _initialize(){
        header("content-type:text/html; charset=utf-8;");
    }
    //授权判断
    public function index(){
       // 查询授权结果是否存在session中
        if(!session('?secretcode')){
            logger('SESSION_CHECK: session不存在授权码信息');
            // 不存在，则查看是否接收到授权码，生成新判断
            $post = I();
            $code = $post['secretcode'];
            if($code){
                logger('SESSION_CHECK: GET中获得授权码--->检验合法性');
                //判断授权码是否合法
                $admin = D('admin');
                $where = array(
                    'secretcode' => $code
                );
                $result = $admin->where($where)->find();
                if($result){
                    logger("SESSION_CHECK: 授权码合法 YES\n");
                    // 授权码合法，查看权限
                    session('secretcode',$code); //将授权码写入session
                    session('power',$result['power_id']);
                    session('uid',$result['id']);
                }else{
                    logger("SESSION_CHECK: 授权码不合法 NO\n");
                    // 授权码非法
                    session('code',0);
                    session('uid',$result['id']);
                }
            }else{
                logger("SESSION_CHECK: 也不能获取授权码。非法访问！非法访问！\n");
                //非法访问
                session('code',0);
                session('uid',0);
            }
        }
        //存在的情况，让各个页面自己去判断授权情况
    }
}
?>