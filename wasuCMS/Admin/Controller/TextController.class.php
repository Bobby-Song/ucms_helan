<?php
  /**
     * [图文类模块]
     * @Author   wangtao
     * @DateTime 2016-10-25T08:30:01+0800
     * @return   [type]                   [description]
     */
namespace Admin\Controller;
use Think\Controller;
class TextController extends Controller {
	public function textHtml(){
		$suffix =I("get._");
		$permissions = session('permissions');
		$this->assign('suffix',$suffix);
		$this->assign('permissions',$permissions);
		$this -> display();
	}
	public function editHtml(){
		$suffix =I("get._");
		$user_id = session('user_id');
		$this -> assign('suffix',$suffix);
		$this -> assign('user_id',$user_id);
		$this -> display();
	}
	public function auditHtml(){
		$suffix =I("get._");
		$user_id = session('user_id');
		$this -> assign('suffix',$suffix);
		$this -> assign('user_id',$user_id);
		$this -> display();		
	}
	
	// 显示数据
	public function showData(){
		$text = M('text');
		$node_id = I('get.node_id');
		$name = I('post.name');
		$value = I('post.value');
		$page_num = I('post.rows');
		$page = I('post.page');
		$region_id = session('region_id');
		$permissions = session('permissions');
		if( $permissions == 1 )
		$where = "node_id='$node_id' AND region_id='$region_id'";
		else if( $permissions == 2 )
		$where = "node_id='$node_id' AND state='1'";
		else if( $permissions == 3 )
		$where = "node_id='$node_id' AND (state='2' OR state='4' OR state='6')";
		if(!empty($name) and !empty($value)){
			$where .= " AND $name LIKE '%$value%'";
		}
		$text_arr = $text -> where($where) -> select();
		$total = count( $text_arr );

		$start_num = $page_num*($page-1);
		$text_arr = $text -> where($where) -> order("sort DESC") -> limit($start_num,$page_num) -> select();
		$arr['total'] = $total;
		$arr['rows'] = $text_arr;
		echo json_encode( $arr );
	}
	
	//新增修改数据
	public function editData(){
		$text = M('text');
		$flag = I('get.flag');
		$data = I('post.');
		$content = $data['content'];
		$data['content'] = htmlspecialchars_decode( $content );//HTML转义反函数
		$data['admin_id'] = session('user_id');
		$data['region_id'] = session('region_id');
		if( $flag == '1' ){
			unset($data['id']);			
			$status = $id = $text -> data($data) -> add();
			$save_arr = array();
			if( !empty( $status ) ){
				$save_arr['id'] = $status;
				$save_arr['sort'] = $status;
				$save_arr['state'] = 1;
				$status = $text -> save( $save_arr );				
			}
		}else if( $flag == '3' ){
			$data['state'] = 1;
			$status = $text -> save($data);
			$id = $data['id'];
		}
		if( !empty( $status ) ){
			$this -> createConJson( $id );
			$arr['status']='系统提示';
			$arr['message']='操作成功';			
		}else{
			$arr['status']='系统提示';
			$arr['message']='操作失败';				
		}
		echo json_encode($arr);	
	}
	
	// 上传视频
	public function moviesUpLoad(){
		$filename = $_POST['fileName'];
		$array = array();
		if ($filename) {
			file_put_contents(MOVIES_PATH.$filename,file_get_contents($_FILES["file"]["tmp_name"]),FILE_APPEND);
			$array['success'] = true;
			echo json_encode($array);
		}		
	}
	// 上传图片
	public function picUpload(){
		$filename = $_POST['fileName'];
		$ext = substr($filename,strripos($filename,'.')); 
		$file_name = date("Ymd").time().rand().$ext;
		$array = array();
		if ($filename) {
			file_put_contents(IMAGE_PATH.$file_name,file_get_contents($_FILES["file"]["tmp_name"]),FILE_APPEND);
			$array['success'] = true;
			$array['file_name'] = $file_name;
			echo json_encode($array);
		}
	}

	//KingEdit 富文本编辑器 本地上传图片
	public function uploadJson(){
		$url = I('get.url');
		$path = '../../../hshWebRoot/image/';
		$file = $_FILES['imgFile'];
		funUploadJson( $url , $path , $file);
	}
	
	// 数据排序
	public function lineMove(){
		$from = I('post.froms');
		$fromId = I('post.fromId');
		$to = I('post.to');
		$toId = I('post.toId');
		$text = M('text');
		
		$data = array();
		$data['id'] = $toId;
		$data['sort'] = $from;
		
		$datas = array();
		$datas['id'] = $fromId;
		$datas['sort'] = $to;
		
		$status = $text -> save( $data );
		$arr['status']='系统提示';
		if( !empty( $status ) ){
			$status = $text -> save( $datas );
			if( !empty($status) ) $arr['message'] = '移动成功';
			else $arr['message'] = '移动失败';
		}
		else $arr['message'] = '移动失败';						
		echo json_encode($arr);	
	}
	
	// 数据置顶
	public function topMove(){
		$text = M('text');
		$id = I('post.id');
		$text_arr = $text -> order("sort DESC") -> find();
		$to_id = $text_arr['id'];
		$to_sort = $text_arr['sort'];
		
		$from = array();
		$from['id'] = $id;
		$from['sort'] = $to_sort;
		$to = array();
		$to['id'] = $to_id;
		$to['sort'] = $id;
		
		$status = $text -> save($from);
		if( !empty( $status ) ){
			$status = $text -> save($to);
			if( !empty( $status ) ){
				$arr['status']='系统提示';
				$arr['message']='置顶成功';				
			}else{
				$arr['status']='系统提示';
				$arr['message']='置顶失败';					
			}
		}else{
			$arr['status']='系统提示';
			$arr['message']='置顶失败';			
		}
		echo json_encode($arr);	
	}	
	
	// 审核发布
	public function audits(){
		$data = array();
		$data['id'] = I('post.id');
		$state = I('post.state');
		$data['back_reason'] = I('post.back_reason');
		$user_id = session('user_id');
		$audit_type = I("post.flag");
		$text = M('text');
		if( $audit_type == 'con_auditor' ){
			if( $state == 1 ){
				$data['state'] = 2;
			}else if( $state == 2 ){
				$data['state'] = 3;
			}
			$data['audit_id'] = $user_id;
		}else if( $audit_type == 'con_release' ){
			if( $state == 1 ){
				$data['state'] = 4;
			}else if( $state == 2 ){
				$data['state'] = 5;
			}
			$data['release_id'] = $release_id;
			$data['release_time'] = date("Y-m-d")." ".date("H:i:s");
		}
		$status = $text -> save($data);
		if(!empty($status)){
			$arr['status']='系统提示';
			$arr['message']='操作成功';	
		}else{
			$arr['status']='系统提示';
			$arr['message']='操作失败';			
		}
		echo json_encode($arr);
	}
	
	//上/下线
	public function onOrDownLine(){
		$data = I('post.');
		$text = M('text');
		$status = $text -> save($data);
		$arr = array();
		if(!empty($status)){
			$arr['status']='系统提示';
			$arr['message']='操作成功';	
		}else{
			$arr['status']='系统提示';
			$arr['message']='操作失败';		
		}
		echo json_encode($arr);
	}
	
	//数据删除
	public function deletes(){
		$text = M("text");
		$str = I('post.str');
		$status = $text -> delete($str);
	}
	
	//生成内容页JSON
	public function createConJson( $_id ){
		$text = M('text');
		$text_arr = $text -> where("id='$_id'") -> find();
		$file_path = WEB_ROOT . $text_arr['node_id'] . '/';
		if( !file_exists( $file_path ) ) mkdir( $file_path , 0777 );
		$file_path .= 'json/';
		if( !file_exists( $file_path ) ) mkdir( $file_path , 0777 );
		$file = $file_path . $_id . '.json';
		file_put_contents( $file , json_encode( $text_arr ) );
	}
}