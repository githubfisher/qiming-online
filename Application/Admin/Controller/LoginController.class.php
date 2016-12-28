<?php
namespace Admin\Controller;
use Think\Controller;
class LoginController extends Controller {
	public function index(){
		$this->display();
	}
	 public function login(){
    	$post = I();
    	$user = $post['user'];
    	$pwd = $post['pwd'];
    	if($user && $pwd){
    		//查询
	    	$admin = D('admin');
	    	$where = array(
	    		'user' => $user,
	    		'pwd' => $pwd
	    	);
	    	$result = $admin->where($where)->find();
	    	$sql = $admin->getLastsql();
	    	// logger('SQL:'.$sql); //debug/
	    	// logger('result:'.$result); //debug
	    	if($result){
	    		session('uid',$result['id']);
	    		session('user',$result['user']);
	    		//登录信息写入数据库
	    		$data = array(
	    			'login_time' => time(),
	    			'login_ip' => get_client_ip(),
	    			'times' => $result['times'] + 1
	    		);
	    		$res = $admin->where($where)->save($data);
	    		if($res){
	    			logger('用户登录信息写入完成！');
	    		}else{
	    			logger('用户登录信息写入失败！');
	    		}
	    		//$this->redirect('Admin/index/show');
	    		//如果是普通用户转到其用户页面，如果是管理转到管理页面
	    		if($result['user'] == 'root'){
	    			$this->redirect('Admin/manage/index');
	    		}else{
	    			$this->redirect('Admin/index/show');
	    		}
	    	}else{
	    		$this->error('用户名或密码错误，请重新登录！');
	    	}
    	}else{
    		$this->error('用户信息不全，请重新登录！');
    	}
    }
    //测试
    public function ceshi(){
    	$this->display('Index/show');
    }
    public function logout(){
    	session(NULL);
		if($_SESSION == array()){
			logger("退出成功\n");
			$this->redirect('Admin/login/index');
		}
    }
}