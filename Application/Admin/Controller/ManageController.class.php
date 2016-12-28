<?php
namespace Admin\Controller;
use Think\Controller;
class ManageController extends Controller {
	public function _initialize(){
        logger("访问后台!"); //debug
        logger("携带数组：".var_export($_POST,TRUE)); //debug
        Vendor("Page.page");
		header("content-type:text/html; charset=utf-8;");
		if(!session('?uid')){
			$this->redirect('Admin/login/index');
		}
	}
    public function index(){
        logger('查询用户信息');
        $admin = D('admin');
        // $where = array();
        // $result = $admin->where($where)->select();
        //分页
        $limit = 15;
        $count = $admin->where($where)->count();
        $page = new \Page($count,$limit);
        $list = $admin->where($where)->order('id desc')->limit($page->firstRow.','.$page->listRows)->cache(true,60)->select();
        $show = $page->show();
        $this->assign('user',session('user'));
        $this->assign('list',$list);
        $this->assign('page',$show);
        // logger('chaxun:'.var_export($result,TRUE));//debug
        $this->assign('xid',1); //顺序序号
        $this->assign('admin',$result);
    	$this->display();
    }
    //添加用户
    public function add_user(){
        logger("添加用户-->");
        $post = I();
        $user = $post['user'];
        $power_id = $post['power_id'];
        $store_name = $post['storename'];
        $deadline = (int)$post['deadline'];
        if($user && $power_id && $deadline){
            //连接用户表
            $admin = D('admin');
            $deadline_time = time() + 3600*24*$deadline;
            // $power_id_arr = explode(' ',$power_id);
            // $power_id_string = '';
            // foreach($power_id_arr as $k =>$v){
            //     if($k == 0){
            //         $power_id_string .= $v;
            //     }else{
            //         $power_id_string .= ','.$v;
            //     } 
            // }
            // logger('22222__'.$deadline_time); //debug
            $data = array(
                'user' => $user,
                'pwd' => substr($user,5,6),
                'secretcode' => randon_secret(8),
                'register_time' => time(),
                'deadline_time' => $deadline_time,
                'store_name' => $store_name,
                'power_id' => $power_id
            );
            logger("即将存储数组：".var_export($data,TRUE));//debug
            $add_user_result = $admin->add($data);
            if($add_user_result = 1){
                $ajax_return = array(
                    'status' => 1,
                    'info' => '添加用户成功！'
                );
                logger("添加用户成功！\n");
            }else{
                $ajax_return = array(
                    'status' => 0,
                    'info' => '添加用户失败！'
                );
                logger("添加用户失败！\n");
            }
            $this->ajaxReturn($ajax_return,'JSON');
        }else{
            $this->error('提交数据错误，请联系管理员！');
        }
    }
    // 删除用户
    public function del_user(){
        logger('删除用户：'.$id);
        $post = I();
        $id = $post['id'];
        if(IS_AJAX){
            $admin = D('admin');
            $where = array(
                'id' => $id
            );
            $result = $admin->where($where)->delete();
            if($result){
                $ajax_return = array(
                    'status' => 1,
                    'info' => '删除成功！'
                );
                logger("删除用户成功！\n");
            }else{
                $ajax_return = array(
                    'status' => 0,
                    'info' => '删除失败！'
                );
                logger("删除用户失败！\n");
            }
            $this->ajaxReturn($ajax_return,'JSON');
        }else{
            $this->error('提交数据错误，请联系管理员！');
        }
    }
    // 编辑用户信息
    public function update_user(){
        logger('编辑用户信息');
        if(IS_AJAX){
            $post = I();
            $id = $post['id'];
            logger('编辑用户：'.$id);
            $store_name = $post['store_name'];
            $user = $post['user'];
            $register_time = $post['register_time'];
            $deadline_time = $post['deadline_time'];
            $secretcode = $post['secretcode'];
            $power_id = $post['power_id'];
            //logger('接收数组：'.var_export($post,TRUE)); //debug
            $admin = D('admin');
            $where = array(
                'id' => $id
            );
            $data = array(
                'user' => $user,
                'store_name' => $store_name,
                'secretcode' => $secretcode,
                'power_id' => $power_id,
                'register_time' => strtotime($register_time),
                'deadline_time' => strtotime($deadline_time)
            );
            $result = $admin->where($where)->save($data);
            if($result){
                $ajax_return = array(
                    'status' => 1,
                    'info' => '修改成功！'
                );
                logger("修改用户成功！\n");
            }else{
                $ajax_return = array(
                    'status' => 0,
                    'info' => '修改失败！'
                );
                logger("修改用户失败！\n");
            } 
            $this->ajaxReturn($ajax_return,'JSON');
        }else{
            $this->error('提交数据错误，请联系管理员！');
        }
    }
    // 导出用户数据或系统用户联系方式
     public function exportAll(){
        logger('导出数据-->');
        $post = I();
        $type = $post['type'];
        $uid = $post['uid'];
        logger('参数：'.var_export($post,TRUE));//debug
        //判断导出类型是否为空
        if($type){
            $str = '';
            $where = array(
                'uid' => $uid
            ); 
            if($type == 'user'){
                logger('导出客户信息-->');
                $admin = D('admin');
                $str = iconv('utf-8','gb2312',"ID,影楼名称,联系方式,授权码,授权项目ID,注册日期,截止日期,最新登录,登录IP,登录次数,URL\n"); 
                $result = $admin->where($where)->cache(true,60)->select();
                foreach($result as $k => $v){
                    $id = $v['id'];
                    $store_name = iconv('utf-8','gb2312',$v['store_name']); //中文转码 
                    $user = $v['user'];
                    $secretcode = $v['secretcode'];
                    $power_id = $v['power_id'];
                    if($v['register_time'] == '' || $v['register_time'] == NULL){
                        $register_time = '';
                    }else{
                        $register_time = date('Y-m-d',$v['register_time']);
                    }
                    if($v['deadline_time'] == '' || $v['deadline_time'] == NULL){
                        $deadline_time = '';
                    }else{
                        $deadline_time = date('Y-m-d',$v['deadline_time']);
                    }
                    if($v['login_time'] == '' || $v['login_time'] == NULL){
                        $login_time = '';
                    }else{
                        $login_time = date('Y-m-d',$v['login_time']);
                    }
                    $login_ip = $v['login_ip'];
                    $times = $v['times'];
                    $url = C('URL').'?secretcode='.$secretcode;
                    $str .= $id.','.$store_name.','.$user.','.$secretcode.','.$power_id.','.$register_time.','.$deadline_time.','.$login_time.','.$login_ip.','.$times.','.$url."\n";
                }
                $filename = '乐兔在线起名测名客户-'.date('Ymdhis').'.csv'; //设置文件名
            }
            if($type == 'qm'){
                logger('导出在线起名用户信息-->');
                $info = D('info');
                $str = iconv('utf-8','gb2312',"ID,家长姓名,联系方式,电子邮箱,宝宝姓氏,宝宝性别,宝宝生日,在线起名时间\n"); 
                $result = $info->where($where)->select();
                foreach($result as $k => $v){
                    $id = $k;
                    $parent = iconv('utf-8','gb2312',$v['parent']); //中文转码 
                    $phone = $v['phone'];
                    $email = $v['email'];
                    $xing = iconv('utf-8','gb2312',$v['xing']); //中文转码 
                    if($v['baby_geneder'] == 'boy'){
                        $gender = iconv('utf-8','gb2312','男'); //中文转码 
                    }
                    if($v['baby_geneder'] == 'girl'){
                        $gender = iconv('utf-8','gb2312','女'); //中文转码 
                    }
                    $birth = iconv('utf-8','gb2312',$v['birth']); //中文转码 
                    $time = date('Y-m-d H:i:s',$v['time']);
                    $str .= $id.','.$parent.','.$phone.','.$email.','.$xing.','.$gender.','.$birth.','.$time."\n";
                }
                $filename = '在线起名用户-'.date('Ymdhis').'.csv'; //设置文件名
            }
            if($type == 'dafen'){
                logger('导出姓名测试用户信息-->');
                $info = D('name_info');
                $str = iconv('utf-8','gb2312',"ID,联系方式,电子邮箱,测试姓名,姓名测试时间\n"); 
                $result = $info->where($where)->select();
                foreach($result as $k => $v){
                    $id = $k;
                    $phone = $v['phone'];
                    $email = $v['email'];
                    $xm = iconv('utf-8','gb2312',$v['xm']); //中文转码 
                    $time = date('Y-m-d H:i:s',$v['time']);
                    $str .= $id.','.$phone.','.$email.','.$xm.','.$time."\n";
                }
                $filename = '姓名测试用户-'.date('Ymdhis').'.csv'; //设置文件名
            } 
            if($str == ''){
                logger("导出类型错误，导出失败\n");
                $this->error('提交导出类型错误，请联系管理员！');
            }else{
                logger("导出成功\n");
                $export = $this->export_csv($filename,$str); //导出 
            }
        }else{
            logger("提交失败，未能成功导出！\n");
            $this->error('提交失败，未能成功导出！');
        }
    }
    //导出操作写入函数
    public function export_csv($filename,$data) { 
        header("Content-type:text/csv"); 
        header("Content-Disposition:attachment;filename=".$filename); 
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0'); 
        header('Expires:0'); 
        header('Pragma:public'); 
        echo $data;
        exit;//结束输入，否则会把HTML源码也存入了
    }
    // 显示用户名下的客户情况（分在线起名和姓名测试两个表）
    public function show_stroe_useinfo(){
        $post = I();
        $id = $post['id'];//用户ID
        $store = $post['store']; // 用户店铺名
        if($id == '' || $id == NULL){
            $where = array(
                'uid' => 1
            ); 
            $id = 1;
            $store = '系统测试店铺';
        }else{
            $where = array(
                'uid' => $id
            ); 
        }
        $info = D('info');
        $name_info = D('name_info');
        $result_info = $info->where($where)->cache(true,60)->select();
        $result_name_info = $name_info->where($where)->cache(true,60)->select();
        $this->assign('info',$result_info);
        $this->assign('dafen',$result_name_info);
        $this->assign('uid',$id);
        $this->assign('store',$store);
        $this->assign('xid',1);
        $this->display();
    }
    public function check_phone(){
        logger('检查手机号是否已经存在!');
        $post = I();
        $phone = $post['phone'];
        logger('要检查的手机号:'.$phone);
        if($phone != '' && $phone != null){
            $where = array(
                'user' => $phone
            );
            $admin = D('admin');
            $result = $admin->where($where)->field('id')->find();
            if($result){
                logger('该手机号已存在'."\n");
                $data = array(
                    'status' => 0,
                    'info' => '该手机号已存在!'
                );
            }else{
                logger('该手机号可以使用'."\n");
                $data = array(
                    'status' => 1,
                    'info' => '该手机号可以使用!'
                );
            }
        }else{
            logger('输入错误,请重新输入'."\n");
            $data = array(
                'status' => 0,
                'info' => '输入错误,请重新输入!'
            );
        }
        $this->ajaxReturn($data);
    }
    // 添加起名资源表单
    public function add_name_resource(){
        logger('显示添加起名资源表单'."\n");
        $this->assign('user',session('user'));
        $this->display();
    }
    // 添加起名资源到数据库
    public function post_name_resource(){
        logger('添加起名资源到数据库 ... ');
        $post = I();
        logger('传入参数:'.var_export($post,true)); //debug
        $name = explode($post['separator'],$post['name']);
        logger('姓名资源:'.var_export($name,true)); //debug
        if(isset($name)){
            $xing = trim($post['xing']);
            $nlength = strlen($xing);
            $resource = array();
            foreach($name as $k => $v){
                $length = strlen($v);
                if(($v != '') && ($v != null) && ($length <= 12)){
                    $resource[] = array(
                        'xing' => $xing,
                        'nums' => ($length-$nlength)/3,
                        'name' => $v,
                        'time' => time(),
                        'baby_geneder' => $post['gender']
                    );
                }
            }
            logger('整理好的姓名资源:'.var_export($resource,true)); //debug
            // echo '<pre>'; //debug
            // var_dump($resource); //debug
            // die;
            if(isset($resource)){
                $names = D('name');
                $result = $names->addAll($resource);
                if($result){
                    $this->success('添加起名资源到数据库成功!');
                }else{
                    $this->success('添加起名资源到数据库失败!');
                }
            }else{
                $this->error('整理起名资源失败,请检查资源!');
            }
        }else{
            $this->error('添加资源出错,请检查添加的资源是否有误!');
        }
        
    }

    public function count_use_info()
    {
        $admin = D('admin');
        $stores = $admin->field('id,store_name,times')->cache(true,60)->select();
        $info = D('info');
        $name_info = D('name_info');
        $result_info = $info->where($where)->cache(true,60)->select();
        $result_name_info = $name_info->where($where)->cache(true,60)->select();
        $all['store'] = count($stores);
        $all['qiming'] = count($result_info);
        $all['dafen'] = count($result_name_info);
        $this->assign('all',$all);
        $this->display();
    }
}