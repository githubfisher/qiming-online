<?php
namespace Admin\Controller;
use Think\Controller;
class ClearCacheController extends Controller {
	public function _initialize(){
		header("content-type:text/html; charset=utf-8;");
		if(!session('?uid')){
			$this->redirect('Admin/login/index');
		}
	}
   public function cache_clear() {
        $this->deldir(TEMP_PATH);
        $this->deldir(CACHE_PATH);
    }

   public function deldir($dir) {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    deldir($fullpath);
                }
            }
        }
    }
}