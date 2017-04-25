<?php
namespace Admin\Controller;
use Think\Controller;
class LoginController extends Controller {
	/*
	 ** 登录页面显示
	 */
    public function index(){
        $this->display();
    }
	
	/*
	 ** 生成验证码
	 */
	public function creatCodeChar(){
		$str = $this->creatRandom(5);
		$width  = 85;
		$height = 25; 
		@ header("Content-Type:image/png");
		$im = imagecreate($width, $height);
		$back = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
		$pix  = imagecolorallocate($im, 187, 230, 247);
		$font = imagecolorallocate($im, 41, 163, 238);
		mt_srand();
		for ($i = 0; $i < 1000; $i++) {
			imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $pix);
		}
		imagestring($im, 8, 20, 5, $str, $font);
		imagerectangle($im, 0, 0, $width -1, $height -1, $pix);
		imagepng($im);
		imagedestroy($im);
		session('verification',$str);
	}
	/*
		** 生成验证字符
	*/
	public function creatRandom($_len){
		$srcstr = "1a2s3d4f5g6hj8k9qwertyupzxcvbnm";
		mt_srand();
		$strs = "";
		for ($i = 0; $i < $_len; $i++) {
			$strs .= $srcstr[mt_rand(0, 30)];
		}
		return $strs;
	}
	/*
		** 用户登录验证
	*/	
	public function userLogin(){
		$codeChar = I('post.code_char');
		$userName = I('post.user_name');
		$userPass = I('post.user_pass');
		$arr = array();
		$arr['title'] = '系统提示';
		if(session('verification') != $codeChar){
			$arr['msg'] = '验证码错误';
			$arr['error'] = 1;
			echo json_encode( $arr );
			return;
		}else{
			$userPass = md5( $userPass );
			$admin_user = M("user");
			$region_model = M('region');
			$reg = M("region");
			$where = "(email='$userName' OR phone='$userName' OR account='$userName') AND password='$userPass'";
			$where.= " AND status='1'";
			$admin_user_arr = $admin_user -> where($where) -> find();
			if( !empty( $admin_user_arr['id'] ) ){
				$file_path = './public/user/'.$admin_user_arr['id'].'.json';
				if( !file_exists( $file_path ) ){
					$a = array();
					file_put_contents( $file_path , json_encode( $a ) );					
				}
				$arr['error'] = 0;
				$_SESSION['user_id'] = $admin_user_arr['id'];
				$_SESSION['user_name'] = $userName;
				$_SESSION['type'] = $admin_user_arr['type'];
				$_SESSION['region_id'] = $admin_user_arr['region_id'];
				$reg_arr = $reg -> where("code='".$admin_user_arr['region_id']."'") -> find();
				$_SESSION['level'] = $reg_arr['level'];
				$_SESSION['nodes'] = $admin_user_arr['nodes'];
				$_SESSION['code'] = $admin_user_arr['code'];
				if( $admin_user_arr['code'] != 0 ){
					$r = $region_model -> where("code='".$admin_user_arr['code']."'") -> find();
					$c = $region_model -> where("code='".$r['pid']."'") -> find();
					$_SESSION['code_name'] = $c['text'] . $r['text'];
					$_SESSION['code_level'] = $r['level'];
				}else{
					$_SESSION['code_name'] = '0';
					$_SESSION['code_level'] = '0';
				}
				if( $admin_user_arr['type'] == '系统总监' ) $arr['url'] = '/wasuCMS/Index';
				else $arr['url'] = '/wasuCMS/Systems/Index';
				echo json_encode( $arr );
			}else{
				$arr['msg'] = '用户名/密码错误或用户已停用';
				$arr['error'] = 1;
				echo json_encode( $arr );
			}
		}
	}
	
	/*
	 ** 用户登录验证
	 */
	public function logOut(){
		setCookie('session_name', '');
        session_unset();
        session_destroy();
		$script = "<script>top.location.href = '/wasuCMS/Login/'</script>";
		echo $script;
		//header('location: /pdcms/Login/');
	}
}