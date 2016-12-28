<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function _initialize(){
        $power_check = A('PowerCheck');
        $power_check->index();
        header("content-type:text/html; charset=utf-8;");
    }
    public function index(){
        $uid = session('uid');
        logger('入口：系统用户: '.$uid.' 访问入口系统！');
        if(session('?power')){ //查看本功能是否在权限中
            logger('入口：读取权限列表，发送给前端');
            // $power_arr = explode(',',session('power'));
            $power_arr = explode(' ',trim(session('power'),' ')); //原来用的英文逗号，后台录入还需要切换输入法（所以后台用空格），但导出时又因为csv用逗号隔开数据。 所以都统一用空格吧
            $new_power_arr = array();
            foreach($power_arr as $k => $v){
                $new_power_arr[$v] = $v;
            }
            $code = $new_power_arr;
            // logger('权限列表：'.var_export($new_power_arr,TRUE)); //debug
            logger("入口：权限列表处理完成\n");
        }else{
            logger('入口：系统权限非法，入口系统关闭！');
            $code = array(0,0);
        }
        $this->assign('code',$code);
        $this->assign('uid',$uid);
    	$this->assign('title','起名');
        $this->show();
    }
    //开始写起名测名双系统 先备份控制器
    //起名表单显示页面
    public function name_query(){
        $uid = session('uid');
        logger('在线起名：系统用户: '.$uid.' 访问在线起名系统！');
        if(strstr(session('power'),'1')){ //查看本功能是否在权限中
            logger("在线起名：系统权限合法，在线起名系统开放！\n");
            $code = 1;
        }else{
            logger("在线起名：系统权限非法，在线起名系统关闭！\n");
            $code = 0;
        }
        $this->assign('code',$code);
        $this->assign('uid',$uid);
        $this->display();
    }
    // 查询‘三藏起名’
    public function query(){
    	$post = I();
    	$xing = $post['xing'];
    	$gender = $post['gender'];
    	$nums = $post['nums'];
        $phone = $post['phone'];
        $uid = $post['uid'];
        $parent = $post['parent'];
        $birth = $post['birth'];
        $email = $post['email'];
        $uid = $post['uid'];
        $code = $post['secretcode'];
    	if($xing && $phone && $parent && $birth){
            session('xing',$xing);
            session('phone',$phone);
            session('parent',$parent);
            session('birth',$birth);
            session('nums',$nums);
            session('gender',$gender);
            logger('存入session:姓氏->'.session('xing').'性别->'.session('gender').'名字字数'.session('nums'));
    		 // 先查询数据是否已经存储在数据库中
            $name = D('name');
            if($nums == 0){
                 $where = array(
                    'xing' => $xing,
                    'baby_geneder' => $gender,
                    'nums' => array(array('eq','1'),array('eq','2'),'or')
                );
            }else{
                $where = array(
                    'xing' => $xing,
                    'baby_geneder' => $gender,
                    'nums' => $nums
                );
            }
            $isname = $name->where($where)->select();
            if($isname){
                logger('数据库直接返回起名数据!');
                $name_arr = $isname;
            }else{
                logger('数据库无数据,执行在线起名系统');
                $url = C('QiMing');
                //$info = '?qiming='.$xing.'&setsex='.$gender.'&setming='.$nums;
                //增加循环，获取500-600个名字 2016-05-20
                $qiming = array();
                $i = 1;
                $max = 7;
                if($nums == 1){
                    $max = 5;
                }
                while($i<$max){
                    $info = '?qiming='.$xing.'&page='.$i.'&setsex='.$gender.'&setming='.$nums;
                    // logger('请求网址参数：'.$info);
                    //强制转换编码
                    $url = mb_convert_encoding($url,'gb2312','utf8');
                    $info = mb_convert_encoding($info,'gb2312','utf8');
                    $result = getMingZi($url,$info);
                    $result = mb_convert_encoding($result, 'utf8', 'gb2312');
                    // logger('result:'.$result); //debug
                    if(strpos($result,'抱歉，没找到相关数据')){
                        logger('姓氏:'.$xing.' 未找到相关数据!'."\n");
                        $this->assign('code',$code);
                        $this->assign('uid',$uid);
                        // 输出起名结果到前端页面
                        $this->display('name_query_nothing');
                    }
                    $res = strchr(strchr($result,'<div class="li5_list">'),'</div>',TRUE);
                    // logger('res:'.$res); //debug
                    $es = ltrim($res,'<div class="li5_list"><ul><li>');
                    // logger('es:'.$es); //debug
                    $arr = explode('</li><li>',$es);
                    // logger('array:'.var_export($arr,TRUE)); //debug
                    $qiming[$i] = $this->list_arr($arr);
                    // logger('结果--------------'.$i.'---:'.var_export($qiming[$i],TRUE)); //debug
                    $i++;
                }
                $name_arr = array();
                foreach($qiming as $k => $v){
                    if($v[0]['name'] != '' || $v[0]['name'] != NULL){
                         $name_arr = array_merge_recursive($name_arr,$v);
                    }
                }
                // logger('整体结果:'.var_export($name_arr,TRUE)); die; //debug
                //存储查询结果
                $name = D('name');
                foreach($name_arr as $k => $v){
                    if(strlen($v['name']) == 6){
                        $name_nums = 1;
                    }else{
                        //logger(strlen($v['name'])); //debug
                        $name_nums = 2;
                    }
                    $res_name = array(
                        'xing' => $xing,
                        'nums' => $name_nums,
                        'name' => $v['name'],
                        'baby_geneder' => $gender,
                        'time' => time()
                    );
                    $result_name_in = $name->add($res_name);
                    if($result_name_in){
                        logger('存储起名数据到数据库成功！');
                    }else{
                        logger('存储起名数据到数据库失败！');
                    }
                }
            }
            //将在线起名用户的信息录入到数据库
            $info = D('info');
            //先查询是否已有相同手机号和姓氏录入
            $where = array(
                'xing' => $xing,
                'phone' => $phone,
                'uid' => $uid
            );
            $result = $info->where($where)->find();
            if($result){
                $data = array(
                    'xing' => $xing,
                    'baby_geneder' => $gender,
                    'time' => time(),
                    'nums' => $nums,
                    'ip' => get_client_ip(),
                    'parent' => $parent,
                    'birth' => $birth,
                    'email' => $email,
                    'times' => $result['times'] + 1
                );
                $res = $info->where($where)->save($data);
            }else{
                $data = array(
                    'xing' => $xing,
                    'phone' => $phone,
                    'baby_geneder' => $gender,
                    'time' => time(),
                    'nums' => $nums,
                    'times' => 1,
                    'ip' => get_client_ip(),
                    'uid' => $uid,
                    'parent' => $parent,
                    'birth' => $birth,
                    'email' => $email
                );
                $res = $info->add($data);
                if($res){
                    logger('在线起名用户信息写入成功！');
                }else{
                    logger('在线起名用户信息写入失败！');
                }
            }
            $this->assign('code',$code);
            $this->assign('uid',$uid);
            // 输出起名结果到前端页面
            $this->assign('name',$name_arr);
            $this->display('name_query');
    	}else{
            logger('不存在session数据,同时也什么也没有输入');
            $this->error('请输入姓氏和联系方式！');
    	}

    }
    // 每个名字的详细描述
    public function details(){
        logger('测试session：'.session('xing')); //debug
    	$post = I();
    	/*$xing = $post['xing'];
    	$ming = $post['ming'];
    	if($xing && $ming){
    		$url = C('Ceshi');
    		$info = '?x='.$xing.'&m='.$ming;
    		//强制转换编码
    		$url = mb_convert_encoding($url,'gb2312','utf8');
    		$info = mb_convert_encoding($info,'gb2312','utf8');
    		$result = getMingZi($url,$info);
    		$result = mb_convert_encoding($result, 'utf8', 'gb2312');
    		logger('result:'.$result); //debug
		*/
    	$xm = $post['xm'];
    	if($xm){
    		// 判断是否姓名测试内容已经存在数据库中
            $details = D('name_details');
            $where = array(
                'xm' => $xm
            );
            $details_result = $details->where($where)->find();
            if($details_result){
                logger('数据库中已存在该姓名测试，由数据库直接返回！');
                $data = $details_result;
            }else{
                //截取姓和名
                $x = substr($xm,0,3);
                $m = substr($xm,3);
                // 访问三藏网
                $url = C('Ceshi');
                $info = '?x='.$x.'&m='.$m;
                //强制转换编码
                $url = mb_convert_encoding($url,'gb2312','utf8');
                $info = mb_convert_encoding($info,'gb2312','utf8');
                $result = getMingZi($url,$info);
                $result = mb_convert_encoding($result, 'utf8', 'gbk');
                // logger('result:'.$result); //debug
                $jx = strchr(strchr($result,'<div class="c_1">'),'<div class="c1_share">',TRUE).'</div>';
                // logger('吉凶:'.$jx); //debug

                $tghead = strchr(strchr($result,'<p class="font_wuge">'),'（<span>',TRUE);
                $tgbody = strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('天格：'.$tg); //debug

                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $rghead = strchr(strchr($result,'<p class="font_wuge">'),'（<span>',TRUE);
                $rgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('人格：'.$rg); //debug

                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $dghead = strchr(strchr($result,'<p class="font_wuge">'),'（<span>',TRUE);
                $dgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('地格：'.$dg); //debug

                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $wghead = strchr(strchr($result,'<p class="font_wuge">'),'（<span>',TRUE);
                $wgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('外格：'.$wg); //debug

                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $zghead = strchr(strchr($result,'<p class="font_wuge">'),'（<span>',TRUE);
                $zgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('总格：'.$zg); //debug
                //综合这些数据
                $data = array(
                    'xm' => $xm,
                    'jx' => $jx,
                    'tghead' => $tghead,
                    'tgbody' => $tgbody,
                    'rghead' => $rghead,
                    'rgbody' => $rgbody,
                    'dghead' => $dghead,
                    'dgbody' => $dgbody,
                    'wghead' => $wghead,
                    'wgbody' => $wgbody,
                    'zghead' => $zghead,
                    'zgbody' => $zgbody,
                    'time' => time()
                );
                logger('获得姓名测试数据，存入数据库'); //debug
                $in_details_result = $details->add($data);
                if($in_details_result){
                    logger('姓名测试内容存入数据库成功！');
                }else{
                    logger('姓名测试内容存入数据库成功！');
                }
            }
            $this->assign('data',$data);
            $this->display();
    	}else{ 
    		$this->error('服务器失联，请稍后重试！');
    	}
    }
    //处理函数列表
    public function list_arr($a){
    	$array = array();
    	foreach($a as $k => $v){
    		//$array[$k]['url'] = ltrim(strchr(strchr($v,"href='"),"' ",TRUE),"href='"); 
    		$array[$k]['name'] = ltrim(strchr(strchr($v,"_blank'>"),"</a>",TRUE),"_blank'>"); 
    	}
    	return $array;
    }
    //姓名测试打分，表单页面
    public function name_dafen(){
        $uid = session('uid'); //直接获取session中的id
        logger('姓名打分：系统用户: '.$uid.' 访问姓名测试打分系统！');
        if(strstr(session('power'),'2')){ //查看本功能是否在权限中
            logger("姓名打分：系统权限合法，姓名测试打分系统开放！\n");
            $code = 1;
        }else{
            //不存在session，查看是否存在授权码

            logger("姓名打分：系统权限非法，姓名测试打分系统关闭！\n");
            $code = 0;
        }
        $this->assign('code',$code);
        $this->assign('uid',$uid);
        $this->assign('title','姓名测试');
        $this->display();
    }
    //姓名打分测试  2016-5-24
    public function name_dafen_show(){
        $url = C('DaFen');
        $post = I();
        $xing = $post['xing'];
        $ming = $post['ming'];
        $phone = $post['phone'];
        $uid = $post['uid'];
        $email = $post['email'];
        $code = $post['secretcode'];
        if($xing && $ming && $phone && $uid){
            //先查询数据库 存在就返回，不存在就查询
            $dafen = D('name_dafen');
            $where = array(
                'xing' => $xing,
                'ming' => $ming
            );
            $result = $dafen->where($where)->find();
            if($result){
                logger('数据库中已存在该姓名打分测试，由数据库直接返回！');
                $data = $result;
            }else{
                logger('不存在该姓名的打分测试！查询->');
                $info = 'xingx='.$xing.'&mingx='.$ming;
                // logger('请求网址参数：'.$info);
                //强制转换编码
                $url = mb_convert_encoding($url,'gb2312','utf8');
                $info = mb_convert_encoding($info,'gb2312','utf8');
                $result = getDaFen($url,$info);
                $result = mb_convert_encoding($result, 'utf8', 'gbk');
                // logger('result:'.$result); //debug
                //截取开始-->
                $result = substr($result,strpos($result,'</form>'));
                // logger('result:'.$result); //debug
                //总得分
                $score = strchr(strchr($result,'<div class="c_dafen">'),'<div class="c_1">',TRUE);
                // logger('总得分:'.$score); //debug
                // 三才设置
                $three_set = strchr(strchr($result,'<div class="c_1">'),'<p><span',TRUE).'</span></p></div></div>';
                // logger('三才设置:'.$three_set); //debug
                // 五格分析
                $result = substr($result,strpos($result,'</span></p>'));
                $five_info = strchr(strchr($result,'<div class="c_1">'),'</p>',TRUE).'</p></div></div>';
                // logger('五格分析:'.$five_info); //debug
                //五格暗示
                $result = substr($result,strpos($result,'class="font_wuge"'));
                $hint = strchr(strchr($result,'<div class="c_1">'),'</table>',TRUE).'</table></div></div>';
                // logger('五格暗示：'.$hint); //debug
                //天格
                $result = substr($result,strpos($result,'</table>'));
                $tghead = strchr(strchr($result,'<p class="font_wuge">'),'<p><strong>',TRUE);
                $tgbody = strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('天格头部：'.$tghead); //debug
                // logger('天格主体：'.$tgbody); //debug
                // 人格
                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $rghead = strchr(strchr($result,'<p class="font_wuge">'),'<p><strong>',TRUE);
                $rgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('人格头部：'.$rghead); //debug
                // logger('人格主体：'.$rgbody); //debug
                // 地格
                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $dghead = strchr(strchr($result,'<p class="font_wuge">'),'<p><strong>',TRUE);
                $dgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('地格头部：'.$dghead); //debug
                // logger('地格主体：'.$dgbody); //debug
                // 外格
                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $wghead = strchr(strchr($result,'<p class="font_wuge">'),'<p><strong>',TRUE);
                $wgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('外格头部：'.$wghead); //debug
                // logger('外格主体：'.$wgbody); //debug
                // 总格
                $result = substr($result,strpos($result,'<p class="font_gray">') + 19);
                // logger('新result:'.$result);//debug
                $zghead = strchr(strchr($result,'<p class="font_wuge">'),'<p><strong>',TRUE);
                $zgbody= strchr(strchr($result,'<p><strong>'),'<p>（<a href=',TRUE);
                // logger('总格头部：'.$zghead); //debug
                // logger('总格主体：'.$zgbody); //debug
                // 综合所以数据，发送到前端
                $data = array(
                    'xing' => $xing,
                    'ming' => $ming,
                    'score' => $score,
                    'three' => $three_set,
                    'five' => $five_info,
                    'hint' => $hint,
                    'tghead' => $tghead,
                    'tgbody' => $tgbody,
                    'rghead' => $rghead,
                    'rgbody' => $rgbody,
                    'dghead' => $dghead,
                    'dgbody' => $dgbody,
                    'wghead' => $wghead,
                    'wgbody' => $wgbody,
                    'zghead' => $zghead,
                    'zgbody' => $zgbody,
                    'time' => time()
                );
                logger('获得姓名测试数据，存入数据库'); //debug
                $in_dafen_result = $dafen->add($data);
                if($in_dafen_result){
                    logger('姓名测试内容存入数据库成功！');
                }else{
                    logger('姓名测试内容存入数据库成功！');
                }
            }
            //将用户使用记录写入数据库
            $dafen_info = D('name_info');
            //先查询 电话号码是否已经存在
            $where = array(
                'uid' => $uid,
                'phone' => $phone
            );
            $dafen_search = $dafen_info->where($where)->find();
            if($dafen_search){
                logger('该用户已测试过姓名，将新信息追加到后尾');
                //已存在该电话号码
                // 则在其姓名中补齐
                $dafen_data = array(
                    'xm' => $dafen_search['xm'].','.$xing.$ming
                );
                $dafen_data_in = $dafen_info->where($where)->save($dafen_data);
                if($dafen_data_in){
                    logger('追加新信息成功！');
                }else{
                    logger('追加新信息失败！');
                }
            }else{
                logger('该用户第一次使用姓名测试系统，存入新信息！');
                $dafen_data = array(
                    'uid' => $uid,
                    'xm' => $xing.$ming,
                    'time' => time(),
                    'phone' => $phone,
                    'email' => $email
                );
                $dafen_data_in = $dafen_info->add($dafen_data);
                // logger('插入数据：'.var_export($dafen_data,TRUE)); //debug
                if($dafen_data_in){
                    logger('添加新信息成功！');
                }else{
                    logger('添加新信息失败！');
                }
            }
            $this->assign('data',$data);
            $this->display();
        }else{
            $this->error('请输入姓氏和名字！');
        }
    }
    //测试访问三藏起名网站的姓名测试打分页面
    public function test(){
        $this->display();
    }
}