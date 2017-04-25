<?php
namespace Systems\Controller;
use Think\Controller;
class DesktopController extends Controller {

	//生成ADMIN节点树
	public function createNodeTree(){
		$node = M('node');
		$where = "type='admin'";
		$node_arr = $node -> where( $where ) -> select();
		$tree_arr = array();
		$node_arr_count = count( $node_arr );
		for( $i = 0 ; $i < $node_arr_count ; $i++ ){
			$tree_arr[$i]['id'] = $node_arr[$i]['id'];
			$tree_arr[$i]['text'] = $node_arr[$i]['text'];
			$tree_arr[$i]['attributes']['pid'] = $node_arr[$i]['pid'];
			$tree_arr[$i]['attributes']['controller'] = $node_arr[$i]['controller'];
			$tree_arr[$i]['attributes']['methods'] = $node_arr[$i]['methods'];
			$tree_arr[$i]['attributes']['icon'] = $node_arr[$i]['icon'];
		}
		$tree_arr = recursiveTree( $tree_arr , $rootId = 0);
		if ( file_put_contents( './public/json/node.json' , json_encode( $tree_arr ) ) )
		createMsg( '操作成功' , 0 );
		else
		createMsg( '操作失败' , 1 );		
	}

	// 获取区域数据
	public function createRegion(){
		$region = M("h_zhou");
		$arr = array();
		$sql = "select * from hsh_h_zhou where city_name='杭州市' limit 1";
		$result = $region -> query($sql);
		$i = 0;
		$arr[$i]['text'] = $result[0]['city_name'];
		$arr[$i]['pid'] = $i;
		$arr[$i]['code'] = $result[0]['city_id'];
		$arr[$i]['level'] = 1;

		$sql = "select *, count(distinct county_id) from hsh_h_zhou group by county_id";
		$result = $region -> query($sql);
		$result_count = count( $result );
		for( $k = 0 ; $k < $result_count ;$k++ ){
			$i++;
			$arr[$i]['text'] = $result[$k]['county_name'];
			$arr[$i]['pid'] = $result[$k]['city_id'];
			$arr[$i]['code'] = $code = $result[$k]['county_id'];
			$arr[$i]['level'] = 2;
		}
		
		//街道
		$sql = "select *, count(distinct town_id) from hsh_h_zhou group by town_id";
		$result = $region -> query($sql);
		$result_count = count( $result );
		for( $k = 0 ; $k < $result_count ;$k++ ){
			$i++;
			$arr[$i]['text'] = $result[$k]['town_name'];
			$arr[$i]['pid'] = $result[$k]['county_id'];
			$arr[$i]['code'] = $code = $result[$k]['town_id'];
			$arr[$i]['level'] = 3;
		}

		//村/社区
		$sql = "select *, count(distinct village_id) from hsh_h_zhou group by village_id";
		$result = $region -> query($sql);
		$result_count = count( $result );
		for( $k = 0 ; $k < $result_count ;$k++ ){
			$i++;
			$village_name = str_replace( '村村' , '村' , $result[$k]['village_name'] );
			$village_name = str_replace( '委会' , '' , $village_name );
			$arr[$i]['text'] = $village_name;
			$arr[$i]['pid'] = $result[$k]['town_id'];
			$arr[$i]['code'] = $code = $result[$k]['village_id'];
			$arr[$i]['level'] = 4;
		}
		file_put_contents( './public/jsonCache/admin_region_data.json' , json_encode($arr) );
	}	
	//导入区域数据
	public function importRegionData(){
		$region_json = file_get_contents( './public/json/admin_region_data.json' );
		$region_arr = json_decode( $region_json , true );
		$region_arr_count = count( $region_arr );
		$region = M('region');
		$region->where("1=1")->delete();//清空表数据
		for( $i = 0 ; $i < $region_arr_count ; $i++ ){
			$region ->  data( $region_arr[$i] ) -> add();
		}
	}
	
	//显示模板数据
	public function templateShow(){
		$template = M( 'template' );
		$name = I('post.name');
		$value = I('post.value');
		$page_num = I('post.rows');
		$page = I('post.page');
		$where = '1=1';
		if( !empty( $name ) and !empty( $value ) )
		$where = " $name like '%".$value."%'";
		$tmp_arr = $template -> where( $where ) -> select();
		$total = count( $tmp_arr );
		$start_num = $page_num * ( $page - 1 );
		$tmp_arr = $template -> where( $where ) -> limit( $start_num , $page_num ) -> select();
		$arr['total'] = $total;
		$arr['rows'] = $tmp_arr;
		echo json_encode( $arr );
	}
	
	//显示模板类别
	public function showTmpClass(){
		$template = M( 'template' );
		$sql = "show columns from ".C('DB_PREFIX')."template where field='class'";
		$arr = $template -> query( $sql );
		$str = $arr[0]['type'];
		$str = substr( $str , 5 , strlen( $str ) - 6 );
		$arr = explode( ',' , $str );// 计算出枚举列预置数据
		$class_arr = array();
		foreach ( $arr as $key => $value ){
			$class_arr[$key]['id'] = $key;
			$class_arr[$key]['text'] = remove_quote( $value );
		}
		echo json_encode( $class_arr );
	}
	
	//显示模板分类树
	public function createTmpTree(){
		$template = M( 'template' );
		$sql = "show columns from ".C('DB_PREFIX')."template where field='class'";
		$arr = $template -> query( $sql );
		$str = $arr[0]['type'];
		$str = substr( $str , 5 , strlen( $str ) - 6 );
		$arr = explode( ',' , $str );// 计算出枚举列预置数据
		$tree_arr = array();
		foreach ( $arr as $key => $value ){
			$tree_arr[$key]['id'] = $value;
			$value = remove_quote( $value );
			$tree_arr[$key]['text'] = $value;
			$tree_arr[$key]['attributes']['type'] = 'test';
			$tmp_arr = $template -> where("class='".$value."'") -> select();
			if( count( $tmp_arr ) > 0 ){
				foreach ( $tmp_arr as $k => $v ){
					foreach ( $v as $k1 => $v1 ){
						if ( $k1 == 'id' )
						$tree_arr[$key]['children'][$k]['id'] = $v1;
						else if ( $k1 == 'text' )
						$tree_arr[$key]['children'][$k]['text'] = $v1;
						else if ( $k1 == 'class' )
						$tree_arr[$key]['children'][$k]['attributes']['class'] = $v1;
						else if ( $k1 == 'path' )
						$tree_arr[$key]['children'][$k]['attributes']['path'] = $v1;
					}
					$tree_arr[$key]['children'][$k]['attributes']['type'] = 'con';
				}
			}else{
				$tree_arr[$key]['children'][0]['id'] = $key + 1;
				$tree_arr[$key]['children'][0]['text'] = '该类别无模板上传';
				$tree_arr[$key]['children'][0]['attributes']['type'] = '';
			}
			if( $key == 0 )
			$tree_arr[$key]['state'] = 'open';
			else
			$tree_arr[$key]['state'] = 'closed';
		}
		echo json_encode( $tree_arr );
	}

	//上传ZIP模板包
	public function zipUpload(){
		$save_path = realpath( $save_path ) . '/';
		$filename = $_POST['fileName'];
		
		$file_name = date("Ymd") . time() . rand() . '.zip';
		$file_path = './public/zip/'.$file_name;
		$zip_path = './public/zip/';
		$array = array();
		if ( $filename ) {	
			file_put_contents( $file_path , file_get_contents( $_FILES["file"]["tmp_name"] ) , FILE_APPEND );
			
			$root_file = onlineUnzip( $file_path , $zip_path );
			if( !empty( $root_file ) ){
				$array['success'] = true;
				$array['status'] = '模板上传成功';
				$array['file_name'] = $zip_path . $root_file . '/';
				$array['error'] = 0;
			}else{
				$array['success'] = false;
				$array['status'] = '模板名重复，请修改后重新上传';
				$array['error'] = 1;				
			}
			echo json_encode( $array );
		}
	}
	
	// 模版新增/修改/删除
	public function tmpEdits(){
		$template = M( 'template' );
		$data = I('post.');
		if( $data['flag'] == 1 ){
			$tep_arr = $template -> where("text='".$data['text']."'") -> find();
			if( !empty( $tep_arr['text'] ) ){
				createMsg( '模板名称重复' , 1 );
				return;
			}
			unset( $data['flag'] );
			unset( $data['id'] );
			$status =  $template -> data($data) -> add();
		}else if( $data['flag'] == 3 ){
			$tep_arr = $template -> where("text='".$data['text']."' and id!='".$data['id']."'") -> find();
			if( !empty( $tep_arr['text'] ) ){
				createMsg( '模板名称重复' , 1 );
				return;
			}
			unset( $data['flag'] );
			$status =  $template -> save($data);			
		}else if( $data['flag'] == 2 ){
			$arr = explode( ',' , $data['id'] );
			import('FileUtil.FileUtil');
			$file_edit = new \FileUtil;
			foreach ( $arr as $key => $value ){
				$tmp_arr = $template -> where("id=$value") -> find();
				$file_edit -> unlinkDir( $tmp_arr['path'] );
			}
			$template -> delete( $data['id'] );
			$status = 1; 
		}
		if( !empty( $status ) ) createMsg( '操作成功' , 0 );
		else createMsg( '操作失败' , 1 );
	}
	
	/**
	 * 移动端图标编辑
	 */
	public function iconEdit(){
	    
	}
}