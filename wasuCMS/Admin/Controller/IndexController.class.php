<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {
	public function __construct(){
		parent::__construct();
		$user_id = session('user_id');
		$type = session('type');
		if( empty( $user_id ) OR $type!= '系统总监' ){
			$login = A('Login');
			$login -> logOut();
			return;
		}
	}
	
    public function index(){
        $this->display();
    }
	
    public function console(){
        $this->display();
    }
	/**
		** 密码强度验证
	*/
	public function securityPw(){
		echo 1;
	}
	/**
		** 更改密码
	*/	
	public function changePw(){
		$newPassWord=I("post.newPassWord");
		$oldPassWord=I("post.oldPassWord");
		$data['user_pass']=$aginPassWord=I("post.aginPassWord");
		$data['user_id']=$user_id=I("post.user_id");
		$user=M("user");
		$re=$user->where("user_id='$user_id'")->select();
		if($re[0]['user_pass']!=md5($oldPassWord)){
			$arr['status']='系统提示';
			$arr['message']='初始密码错误';
			$arr['error']=0;
		}else{
			$data['user_pass']=md5($data['user_pass']);
			$status=$user->save($data);
			if(!empty($status)){
				$arr['status']='系统提示';
				$arr['message']='操作成功';
				$arr['error']=1;
			}else{
				$arr['status']='系统提示';
				$arr['message']='操作失败';	
				$arr['error']=0;
			}
		}
		echo json_encode($arr);		
	}
	
	/**
		** 生NODE节点树
	*/	
	public function createTree(){
		$admin_node = M( 'admin_node' );
		$admin_node_arr = $admin_node ->  order( "id ASC" ) -> select();
		$admin_node_arr_count = count( $admin_node_arr );
		$arr = array();
		for( $i = 0 ; $i < $admin_node_arr_count ; $i++ ){
			$arr[$i]['id'] = $admin_node_arr[$i]['id'];
			$arr[$i]['text'] = $admin_node_arr[$i]['text'];
			$arr[$i]['attributes']['url'] = $admin_node_arr[$i]['url'];
			$arr[$i]['attributes']['controller'] = $admin_node_arr[$i]['controller'];
			$arr[$i]['attributes']['pid'] = $admin_node_arr[$i]['pid'];
			if( $admin_node_arr[$i]['level'] == 2 ){
				$id = $admin_node_arr[$i]['id'];
				$p_arr = $admin_node -> where("pid='$id'") -> find();
				if( count( $p_arr ) > 0 ){
					$arr[$i]['state'] = 'closed';
				}else{
					$arr[$i]['state'] = 'open';
				}
			}
		}
		file_put_contents('public/jsonCache/node.json', json_encode(recursiveTree( $arr, $rootId = 0)));
	}
	
	public function addApp(){
	    $appDB = M('app');
	    
	    $data['app_name'] = $_POST['app_name'];
	    $data['app_desc'] = $_POST['app_desc'];
	    $data['user_id'] = $_SESSION['user_id'];
	    
	    $ret = $appDB->add($data);
	    if($ret){
	        standOutput($ret, SUC_OPERATE);
	    }else{
	        standOutput('', ERR_OPERATE);
	    }
	}
	
	public function getAppList(){
	    $appDB = M('app');
	    
	    $rows = $_POST['rows'];
	    $page = $_POST['page'];
	    
	    if(empty($rows) || !check_positive_int($rows)){
	        $rows = 20;
	    }
	    
	    if(empty($page) || !check_positive_int($page)){
	        $rows = 1;
	    }
	    
	    $offset = ($page - 1) * $rows;
	    
	    $ret = $appDB->field('id, app_name, app_desc, app_time')->limit($offset, $rows)->select();
	    
	    echo json_encode($ret);
	}
	
	public function updateApp(){
	    $appDB = M('app');
	    
	    $id = $_POST['id'];
	    $data['app_name'] = $_POST['app_name'];
	    $data['app_desc'] = $_POST['app_desc'];
	    
	    $ret = $appDB->where('id=%s',array($id))->save($data);
	    if($ret){
	        standOutput($ret, SUC_OPERATE);
	    }else{
	        standOutput('', ERR_OPERATE);
	    }
	}
	
	public function delApp(){
	    $appDB = M('app');
	    
	    $id = $_GET['id'];
	    
	    $ret = $appDB->where('id=%s', array($id))->delete();
	     
	    if($ret){
	        standOutput($ret, SUC_OPERATE);
	    }else{
	        standOutput('', ERR_OPERATE);
	    }
	}
	
	public function apkManager(){
	    $suffix = I( 'get._' );
	    $this->assign( 'suffix' , $suffix );
	    $this->display();
	}
	
	public function getApkList(){
	    $apkDB = M('apk');
	    
	    $appid = $_GET['appid'];
	    $rows = $_POST['rows'];
	    $page = $_POST['page'];
	     
	    if(empty($rows) || !check_positive_int($rows)){
	        $rows = 20;
	    }
	     
	    if(empty($page) || !check_positive_int($page)){
	        $rows = 1;
	    }
	     
	    $offset = ($page - 1) * $rows;
	     
	    $ret = $apkDB->field('id,app_id,apk_packname,apk_name,apk_vername,apk_version,apk_path,apk_size,apk_desc,keywords,downloads,create_time')->where('app_id=%s', array($appid))->limit($offset, $rows)->select();
	    
	    foreach($ret as $k=>$v){
	        $ret[$k]['apk_status'] = file_exists('../'.$v['apk_path']) ? '正常' : '版本文件丢失';
	        unset($ret[$k]['apk_path']);
	    }
	    
	    echo json_encode($ret);
	}
	
	public function addApk(){
	    if(file_exists('../'.$_SESSION['apkpath'])){
    	    $data['apk_path'] = $_SESSION['apkpath'];
    	    $data['apk_size'] = sprintf("%.2f", filesize('../'.$_SESSION['apkpath'])/1024/1024);
    
    	    import('ApkParser.ApkParser');
    	    
    	    $apkInfo = new \ApkParser();
    	    $apkInfo->open('../'.$_SESSION['apkpath']);
    	    
            $data['apk_packname'] = $apkInfo->getPackage();    // 应用包名
            $data['apk_name'] = $apkInfo->getAppName();     // 应用名称
            $data['apk_vername'] = $apkInfo->getVersionName();  // 版本名称
            $data['apk_version'] = $apkInfo->getVersionCode();  // 版本代码
	    }
        
	    if(!file_exists('../'.$_SESSION['apkicon'])){
	        standOutput('', ERR_OPERATE);
	    }
        $data['apk_icon'] = $_SESSION['apkicon'];
        $data['download_url'] = $_POST['downloadurl'];
        $data['app_id'] = $_POST['app_id'];
        $data['apk_desc'] = $_POST['apk_desc'];
        $data['keywords'] = $_POST['keywords'];
        
        if(!empty($_SESSION['apkshot'])){
            $data['shot_icons'] = $_SESSION['apkshot'];
        }
        
        $apkDB = M('apk');
        
        $ret = $apkDB->add($data);
        
        unset($_SESSION['apkpath']);
        unset($_SESSION['apkicon']);
        unset($_SESSION['apkshot']);
        
        if($ret){
            standOutput($ret, SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
	}
	
	public function apkUpload(){
	    $apkPath = upload_file(array('apk'), $_FILES, 'app');
	    
	    if($apkPath != 'NOUPLOAD'){	        
	        $_SESSION['apkpath'] = $apkPath;
	        standOutput($apkPath, SUC_OPERATE);
	    }else{
	        standOutput($apkPath, ERR_OPERATE);
	    }
	}
	
	public function apkIconUpload(){
	    $apkPath = upload_file(array('jpg', 'gif', 'png', 'jpeg'), $_FILES, 'appicon');
	    
	    if($apkPath != 'NOUPLOAD'){
	         
	        $_SESSION['apkicon'] = $apkPath;
	        standOutput($apkPath, SUC_OPERATE);
	    }else{
	        standOutput($apkPath, ERR_OPERATE);
	    }
	}
	
	public function apkShotUpload(){
	    $apkPath = upload_file(array('jpg', 'gif', 'png', 'jpeg'), $_FILES, 'appshot');
	     
	    if($apkPath != 'NOUPLOAD'){
	        if(empty($_SESSION['apkshot'])){
	            $_SESSION['apkshot'] = $apkPath;
	        }else{
	            if(count(explode('!!', $_SESSION['apkshot'])) >= 5){
	                standOutput('', ERR_MAX_UPLOADS);
	            }
	            
	            $_SESSION['apkshot'] = $_SESSION['apkshot'].'!!'.$apkPath;
	        }
	        logs('------- apkshot = '.$_SESSION['apkshot']);
	        standOutput($apkPath, SUC_OPERATE);
	    }else{
	        standOutput($apkPath, ERR_OPERATE);
	    }
	}
	
	private function _getApkInfo(){
	    
	}
}