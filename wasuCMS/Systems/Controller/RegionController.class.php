<?php
namespace Systems\Controller;
use Think\Controller;
class RegionController extends Controller {
	private $oldpid = 0;
	public function __construct(){
		parent::__construct();
		$user_id = session( 'user_id' );
		if( empty( $user_id ) ){
			$login = A( 'Login' );
			$login -> logOut();
			return;
		}
	}
	
	
	public function regionData(){
		$suffix = I( 'get._' );
		$this -> assign( 'suffix' , $suffix );
		$this -> display();
	}
	public function regionOnOff(){
		$suffix = I( 'get._' );
		$this -> assign( 'suffix' , $suffix );
		$this -> display();
	}
	public function regionShunt(){
		$suffix = I( 'get._' );
		$this -> assign( 'suffix' , $suffix );
		$this -> display();
	}
	public function regionTemp(){
		$suffix = I( 'get._' );
		$this -> assign( 'suffix' , $suffix );
		$this -> display();
	}
	//显示城市模板关联
	public function showCityTempReg(){
		$region = M( 'region' );
		$template = M( 'template' );
		$root_arr = $region -> where("pid=0") ->  find();
		$code = $root_arr['code'];
		$where = "pid='".$root_arr['code']."'";
		$region_arr = $region -> where($where) -> select();
		foreach( $region_arr as $key => $value ){
			foreach( $value as $k => $v ){
				if( $k == 'temp_id' ){
					if ( $v != 0 ){
						$temp_arr = $template -> where("id=$v") -> find();
						$region_arr[$key]['temp_name'] = $temp_arr['text'];
					}else{
						$region_arr[$key]['temp_name'] = '未关联';
					}
				}
			}
		}
		$root_arr['children'] = $region_arr;
		echo "[".json_encode($root_arr)."]";		
	}
	
	//显示区域树表格
	public function showRerionGridTree(){
		$region = M( 'region' );
		$template = M( 'template' );
		$id = I( 'post.id' );
		if( empty( $id ) ){
			$root_arr = $region -> where("pid=0") ->  find();
			$code = $root_arr['code'];
			$where = "pid='".$root_arr['code']."'";
			$region_arr = $region -> where($where) -> select();
			$root_arr['children'] = $region_arr;
			foreach( $region_arr as $key => $value ){
				foreach( $value as $k => $v ){
					if( $k =='code'){
						$reg_arr = $region -> where("pid=$v") -> select();
						if( count( $reg_arr ) > 0 ){
							$root_arr['children'][$key]['state'] = 'closed';
							$root_arr['children'][$key]['count'] = count( $reg_arr );
						}else{
							$root_arr['children'][$key]['state'] = 'open';
						}
					}
					if( $k == 'temp_id' ){
						$temp_arr = $template -> where("id=$v") -> find();
						if( !empty( $temp_arr['text'] ) )
						$root_arr['children'][$key]['temp_name'] = $temp_arr['text'];
						else
						$root_arr['children'][$key]['temp_name'] = '未关联';
					}
				}
			}
			echo "[".json_encode($root_arr)."]";
		}else{
			$root_arr = $region -> where("id=$id") -> find();
			$region_arr = $region -> where("pid='".$root_arr['code']."'") -> select();
			$arr = $region_arr;
			foreach( $region_arr as $key => $value ){
				foreach( $value as $k => $v ){
					if( $k == 'code' ){
						$reg_arr = $region -> where("pid=$v") -> select();
						if( count( $reg_arr ) > 0 ){
							$arr[$key]['state'] = 'closed';
						}else{
							$arr[$key]['state'] = 'open';
						}
					}
					if( $k == 'temp_id' ){
						$temp_arr = $template -> where("id=$v") -> find();
						if( !empty( $temp_arr['text'] ) )
						$arr[$key]['temp_name'] = $temp_arr['text'];
						else
						$arr[$key]['temp_name'] = '未关联';
					}
				}
			}
			echo json_encode( $arr );
		}
	}
	
	//区域树表格编辑
	public function editRerionGridTree(){
		$region = M( 'region' );
		$data = I( 'post.' );
		if( $data['flag'] == 1 ){
			$root_arr = $region -> where("id='".$data['id']."'") -> find();
			$data['pid'] = $root_arr['code'];
			$where = "pid='" . $data['pid'] . "'";
			$code_arr = $region -> where( $where ) -> field( 'code' ) -> order( 'code DESC' ) -> limit(0,1) -> select();
			if( count( $code_arr ) > 0 ){
				$code = $code_arr[0]['code'];
			}else{
				$code = $root_arr['code'];
			}
			if( $root_arr['level'] == 1 )
			$data['code'] = $code + 1000000;
			else if( $root_arr['level'] == 2 )
			$data['code'] = $code + 1000;
			else if( $root_arr['level'] == 3 )
			$data['code'] = $code + 1;
			$data['level'] = $root_arr['level'] +1 ;
			unset( $data['flag'] );
			unset( $data['id'] );
			$status =  $region -> data($data) -> add();
		}else if( $data['flag'] == 3 ){
			unset( $data['flag'] );
			$status =  $region -> save($data);
		}else if( $data['flag'] == 2 ){
			$this -> recursiveDelRegion( $data['id'] , $region );
			$status = true;
		}
		if( !empty( $status ) ){
			createMsg( '操作成功' , 0 );
			
		} 
		else createMsg( '操作失败' , 1 );		
	}
	
	//递归删除区域表信息
	public function recursiveDelRegion( $_id , $_M ){
		$reg_arr = $_M -> where("id=$_id") -> find();
		$pid = $reg_arr['code'];
		$status = $_M -> where("id=$_id") -> delete();
		$arr = $_M -> where("pid=$pid") -> select();
		$arr_count = count( $arr ); 
		if( $arr_count > 0 ){
			for( $i = 0 ; $i < $arr_count ; $i++ ){
				$id = $arr[$i]['id'];
				$this -> recursiveDelRegion( $id , $_M );
			}
		}
	}
	
	//递归删除TV栏目表信息
	public function recursiveDelTvNode( $_id , $_M ){
		$_M -> where("id=$_id") -> delete();
		$arr = $_M -> where("pid=$_id") -> select();
		$arr_count = count( $arr ); 
		if( $arr_count > 0 ){
			for( $i = 0 ; $i < $arr_count ; $i++ ){
				$id = $arr[$i]['id'];
				$this -> recursiveDelTvNode( $id , $_M );
			}
		}
	}
	
	// 递归遍历数组并格式化
	public function recursiveNodeArr( $_json_path ){
		$node_arr = array();
		$root_json = @file_get_contents( $_json_path );
		if( empty( $root_json ) ){
			return false;
		}else{
			$root_json = str_replace( 'var mainArray =' , '' , $root_json );
			$root_json = str_replace( ';' , '' , $root_json );	
			$root_json = trimall( $root_json );
			$root_json = json_decode( $root_json , true );
			$home_arr = array();
			$node_arr = array();
			foreach( $root_json['node'] as $key => $value ){
				foreach( $value as $k => $v ){
					if( $k == 'type' ){
						if( $v == 'pageLink' || $v == 'videoLink' || $v == 'content' || $v == 'focusPic' || $v == 'videoFrame' ) $home_arr[] =  $value;
						if( $v == 'column' ) $node_arr[] = $value;
					}
				}
			}
			$arr = array();
			if( count( $home_arr ) > 0 ) $arr['home'] = $home_arr;
			if( count( $node_arr ) > 0 ) $arr['node'] = $node_arr;
			return $arr;			
		}
	}
	

	
	
	//区域模板关联
	public function tempCorr(){
		$region = M( 'region' );
		$data = I( 'post.' );
		$this -> recursiveUpdate( $region , $data );
		$template = M( 'template' );
		$tv_node = M( 'tv_node' );
		
		$template_arr = $template -> where("id='".$data['temp_id']."'") -> find();
		$region_arr = $region -> where("id='".$data['id']."'") -> find();
		//读出模板路径根数据
		$temp_path = $template_arr['path'].'public/data/';
		$arr = $this -> recursiveNodeArr( $temp_path.'index_data.js' );
		if( !empty( $arr ) ){
			$tv_node_arr = $tv_node -> where("region_id='".$region_arr['id']."'") -> find();
			if( !empty( $tv_node_arr['id'] ) ){
				$id = $tv_node_arr['id'];
				$this -> recursiveDelTvNode( $id , $tv_node );
			}
			$datas['text'] = $region_arr['text'];
			$datas['region_id'] = $region_id = $region_arr['id'];
			$datas['dir'] = '/'.$region_arr['code'];
			$datas['pid'] = 0;
			$pids = $tv_node -> data( $datas ) -> add();		
			$this -> recursiveInsertNode( $arr , $tv_node , $pids , $temp_path , true , $region_id ); // 将模板JSON转数组插入表
			import('FileUtil.FileUtil');//引入文件夹操作类库
			$file_edit = new \FileUtil;
			
			$file_path = '../webRoot/'.$region_arr['code'];
			if( !is_dir( $file_path ) ){
				mkdir( $file_path );
			}else{
				$file_edit -> unlinkDir( $file_path );
				mkdir( $file_path );
			}
			$file_edit -> copyDir( $template_arr['path'] , $file_path );
			chmod( $file_path , 0777 );
			recurDir( $file_path , 0777 );//递归更改文件目录权限
			
			$tv_node_arr = array();
			$arr = array();
			$a =array();
			$tv_node_arr = $tv_node -> where("region_id=$region_id") -> select();
			$tv_node_arr_count = count( $tv_node_arr );
			for( $i = 0 ; $i < $tv_node_arr_count ; $i++ ){
				$a[$i]['id'] = $tv_node_arr[$i]['id'];
				$a[$i]['text'] = $tv_node_arr[$i]['text'];
				$a[$i]['attributes']['pid'] = $tv_node_arr[$i]['pid'];
				$a[$i]['attributes']['region_id'] = $tv_node_arr[$i]['region_id'];
				$a[$i]['attributes']['type'] = $tv_node_arr[$i]['type'];
				$a[$i]['attributes']['is_leaf'] = $tv_node_arr[$i]['is_leaf'];
			}
			$arr = recursiveTree( $a, $rootId = 0 ); //递归生成区域CMS栏目树数组
			file_put_contents( './public/json/'.$region_arr['code'].'_node.json' , json_encode( $arr ) );
			
			//生成区域CMS用户（操作/审核/发布）
			
			$operate['account'] = 'operate_'.$region_id;
			$operate['password'] = md5('123456');
			$operate['type'] = '操作员';
			$operate['region_id'] = $region_id;

			$audit['account'] = 'audit_'.$region_id;
			$audit['password'] = md5('123456');
			$audit['type'] = '内容审核';
			$audit['region_id'] = $region_id;

			$release['account'] = 'release_'.$region_id;
			$release['password'] = md5('123456');
			$release['type'] = '内容发布';
			$release['region_id'] = $region_id;			

			$user = M('user');
			$user -> where("region_id=$region_id") -> delete();
			$user -> data( $operate ) -> add();
			$user -> data( $audit ) -> add();
			$user -> data( $release ) -> add();
			createMsg( '操作成功' , 0 );			
		}else{
			createMsg( '操作失败' , 0 );
		}			
	}
	
	//递归插入栏目数据
	public function recursiveInsertNode( $_ARR , $_M , $_PID , $_path , $bool , $_rid ){
		if( $bool === true ){
			$node_arr = $_ARR['node'];
			$home_arr = $_ARR['home'];
			$arrs = $node_arr;
		}else{
			$arrs = $_ARR;
		}
		if( !empty( $home_arr ) ){
			foreach( $home_arr as $v ){
				$f = $v;
				$f['pid'] = $_PID;
				$f['is_leaf'] = "yes";
				$f['region_id'] = $_rid;
				$_M -> data( $f ) -> add();					
			}
			
		}
		foreach( $arrs as $key => $val ){
			if ( is_array( $val ) ){
				if( !empty( $val['list'] ) ){
					$f = $val;
					$f['pid'] = $_PID;
					$f['is_leaf'] = "yes";
					$f['region_id'] = $_rid;
					$_M -> data( $f ) -> add();					
				}else{
					$d = $val;
					$d['pid'] = $_PID;
					$d['region_id'] = $_rid;
					$pid = $_M -> data( $d ) -> add();			
					$this->oldpid = $pid;
				}
			}

			if( !empty( $val['dir'] ) ){
				$json_path = $_path.$val['dir'].'_data.js';				
				$a = array();
				$a = $this -> recursiveNodeArr( $json_path );
				if( !empty( $a ) ) 
				$this -> recursiveInsertNode( $a , $_M , $this -> oldpid , $_path , true , $_rid );
				
			}

			if( !empty( $val['node'] ) )
			$this -> recursiveInsertNode( $val['node'] , $_M , $this->oldpid , $_path , false , $_rid );
			$this -> oldpid++;
		} 

	}
	
	
	public function test(){
		$db = M('tv_node');
		$arr = $db->field('id,pid,text')->select();
		$this -> data2arr($arr, $rootId = 0, $level = 0);
	}
	
	
	public function data2arr($tree, $rootId = 0, $level = 0) {  
		foreach($tree as $leaf) {  
			if($leaf['pid'] == $rootId) {  
				echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level) . $leaf['id'] . ' ' . $leaf['text'] . '<br/>';  
				foreach($tree as $l) {  
					if($l['pid'] == $leaf['id']) {  
						$this -> data2arr($tree, $leaf['id'], $level + 1);  
						break;  
					}  
				}  
			}  
		}  
	}  
	//区域IP设定
	public function regIpShunt(){
		$region = M( 'region' );
		$data = I( 'post.' );
		$where = "(begin_ip='".$data['begin_ip']."' OR begin_ip='".$data['end_ip']."' OR end_ip='".$data['end_ip']."'";
		$where .= " OR end_ip='".$data['begin_ip']."') AND id!='".$data['id']."'";
		$reg_arr = $region -> where( $where ) -> find();
		if( !empty( $reg_arr['id'] ) ){
			createMsg( 'IP数据重复，请进行修改' , 1 );
			return;
		}
		$status =  $region -> save( $data );
		if ( !empty( $status ) ){
			createMsg( '操作成功' , 0 );
			
		} else createMsg( '操作失败' , 1 );		
	}
	
	//区域上下线设定
	public function regOnOff(){
		$region = M( 'region' );
		$data = I( 'post.' );
		$reg_arr = $region -> where("id='".$data['id']."'") -> field('temp_id') -> find();
		if( $reg_arr['temp_id'] == 0 and $data['status'] == '上线' ){
			createMsg( '该区域未关联模板，不能上线' , 1 );
			return;
		} 
		$this -> recursiveUpdate( $region , $data );
		createMsg( '操作成功' , 0 );			
	}
	
	//递归修改
	public function recursiveUpdate( $_M , $_data ){
		$reg_arr = $_M -> where("id='".$_data['id']."'") -> find();
		$status = $_M -> save( $_data );
		//if ( !empty( $status ) ) return false;
		$arr = $_M -> where("pid='".$reg_arr['code']."'") -> select();
		$arr_count = count( $arr ); 
		if( $arr_count > 0 ){
			for( $i = 0 ; $i < $arr_count ; $i++ ){
				$_data['id'] = $arr[$i]['id'];
				$this -> recursiveUpdate( $_M  , $_data );
			}
		}
	}
}