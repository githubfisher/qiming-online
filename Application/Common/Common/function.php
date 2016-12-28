<?php
// 调用CURL函数，组合访问参数和URL，得到返回值
function getMingZi($url,$info){
		// $header = 'Content-type:text/xml;';
		$url .= $info;
    	$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_HEADER,0);
		// curl_setopt($ch, CURLOPT_ENCODING, "");
		$output = curl_exec($ch);
		curl_close($ch);
	    return $output;
}
function getDaFen($url,$data){
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,1);
	//curl_setopt($ch,CURLOPT_HEADER,0);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}
// 日志函数
function logger($log_content){ 
	$folder = "./Log/";
	if(!file_exists($folder)){
		mkdir($folder);
	}	
	for($i=20;$i--;$i>0){
		$max_size = 100000;   
    	$log_filename = $folder.'/'.date('Ymd',time()).'-'.$i.'.txt';
		if(file_exists($log_filename) && (abs(filesize($log_filename)) >= $max_size)){
			$next = $i + 1;
			$log_filename = $folder.'/'.date('Ymd',time()).'-'.$next.'.txt';
			//新内容写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
    		file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content." \r\n", FILE_APPEND);  
		}elseif(file_exists($log_filename) && (abs(filesize($log_filename)) < $max_size)){
			//写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
			file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content." \r\n", FILE_APPEND); 
		}else{
			if($i == 1){
				//写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
				file_put_contents($log_filename, date('Y-m-d H:i:s')." ------------------------------> ".$log_content." \r\n", FILE_APPEND); 
			}
		}
	} 
}
function randon_secret($num){
	$string = '';
	$str = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	while(strlen($string) < $num){
		$string .= substr($str,(mt_rand()%strlen($str)),1);
	}
	return $string;
} 
?>