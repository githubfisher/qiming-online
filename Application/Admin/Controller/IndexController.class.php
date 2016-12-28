<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {
	public function _initialize(){
        Vendor("Page.page");
		header("content-type:text/html; charset=utf-8;");
		if(!session('?uid')){
			$this->redirect('Admin/login/index');
		}
	}
    public function index(){
    	$this->display('login/index');
    }
    //显示在线起名用户信息
    public function show(){
        logger('SESSION:'.session('uid'));
    	$info = D('info');
        $where = array(
            'uid' => session('uid')
        );
        $result = $info->where($where)->select();
        //分页
        $limit = 15;
        $count = $info->where($where)->count();
        $page = new \Page($count,$limit);
        $list = $info->where($where)->order('time desc')->limit($page->firstRow.','.$page->listRows)->select();
        $show = $page->show();
        $this->assign('user',session('user'));
    	$this->assign('list',$list);
        $this->assign('page',$show);
        // logger('chaxun:'.var_export($result,TRUE));//debug
        $this->assign('xid',1);
    	$this->assign('info',$result);
    	$this->display();
    }
    //显示姓名测试用户信息
    public function name_dafen(){
        logger('SESSION:'.session('uid'));
        $info = D('name_info');
        $where = array(
            'uid' => session('uid')
        );
        $result = $info->where($where)->select();
        //分页
        $limit = 15;
        $count = $info->where($where)->count();
        $page = new \Page($count,$limit);
        $list = $info->where($where)->order('time desc')->limit($page->firstRow.','.$page->listRows)->select();
        $show = $page->show();
        $this->assign('user',session('user'));
        $this->assign('list',$list);
        $this->assign('page',$show);
        // logger('chaxun:'.var_export($result,TRUE));//debug
        $this->assign('xid',1);
        $this->assign('info',$result);
        $this->display();
    }
    //导出全部用户数据（单个表）
    public function exportAll(){
        $post = I();
        $type = $post['type'];
        //判断导出类型是否为空
        if($type){
            $str = '';
            $where = array(
                'uid' => session('uid')
            ); 
            if($type == 'qm'){
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
                $this->error('提交导出类型错误，请联系管理员！');
            }else{
                $export = $this->export_csv($filename,$str); //导出 
            }
        }else{
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
}