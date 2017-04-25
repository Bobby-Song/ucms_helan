<?php 

namespace Systems\Controller;
use Think\Controller;

class UserController extends Controller{
	public function __construct(){
		parent::__construct();
		$user_id = session('user_id');
		$type = session('type');
		if( empty( $user_id ) || $type == '系统总监' ){
			$login = A('Admin/Login');
			$login -> logOut();
			return;
		}
	}
	
    /**
     * TV端用户管理
     */
    public function userHtml(){
		$suffix = I( "get._" );
		$this -> assign( 'suffix' , $suffix );
		$this -> assign( 'region_id' , session('region_id') );
		$this -> assign( 'user_id' , session('user_id') );
		$this -> assign( 'level' , session('level') );
        $this -> display();
    }	
    
    /**
     * app端用户管理
     */
    public function appUserManage(){
        $this->display();
    }
    
    /**
     * app端用户信息更新
     */
    public function appUserUpdate(){
        
    }
    
    /**
     * app端用户注册
     */
    public function appUserRegister(){
        //TODO 由PC端配置APP端是否开启注册功能
    }
	
	//显示TV系统用户管理数据
	public function userView(){
		$prefix = C('DB_PREFIX');
		$region_id = session('region_id');
		$user = M("user");
		$tv_node = M("tv_node");
		$name = I('post.name');
		$value = I('post.value');
		$page_num = I('post.rows');
		$page = I('post.page');
		$start_num = $page_num*($page-1);
		if( $region_id != 0 ) $where = "region_id='$region_id'";
		else $where = "1=1";
		
		if( !empty($name) and !empty($value ) ){
			$where .= " and $name LIKE '%$value%'";
		}
		$limit = "limit $start_num,$page_num";	
		$user_arr = $user -> where($where) -> select();
		$rows_arr = $user -> where($where) -> limit($limit) -> select();
        foreach ($rows_arr as $r_k => $r_v) {
            $node_code_arr = explode(',', $r_v['nodes']);
            $node_id_arr = [];
            foreach ($node_code_arr as $k => $v) {
                $node_id_arr[$k] = M('tv_node')->where(['node_code' => $v])->getField('id');
            }
            $rows_arr[$r_k]['nodes'] = implode(',', $node_id_arr);
        }
		$arr['total'] = count($user_arr);
		$arr['rows'] = $rows_arr;
		echo json_encode($arr);
	}
	
    //显示TV用户类别（获取用户类别枚举值）
    public function showTmpClass(){
        $prefix = C('DB_PREFIX'); // 获取前缀
        $content_model = M('user');
        $sql = "show columns from " . $prefix . "user where field='type'";
        $arr = $content_model->query($sql);
        $str = $arr[0]['type'];
        $str = substr($str, 5, strlen($str) - 6);
        $arr = explode(',', $str);// 计算出枚举列预置数据
        $class_arr = array();
		$i=0;
        foreach ($arr as $key => $value) {
            $v = remove_quote($value);
			if( $v != '系统总监' and $v != '' ){
				$class_arr[$i]['id'] = $v;
				$class_arr[$i]['text'] = $v;
				$i++;
			}
        }
        echo json_encode($class_arr);
    }
	
	
	//显示栏目树数据
	public function showNodeTree(){
		$tv_node = M("tv_node");
		$region_id = session('region_id');
		$tmp = '/' . $region_id;
		$n_arr = $tv_node -> where("dir='$tmp'") -> find();
		$pid = $n_arr['id'];
		$tv_node_arr = $tv_node -> where("region_id='$region_id' and id!='$pid'") -> select();
		$arr = array();
		foreach( $tv_node_arr as $key => $value ){
			$arr[$key]['id'] = $tv_node_arr[$key]['id'];
			$arr[$key]['text'] = $tv_node_arr[$key]['text'];
			$arr[$key]['attributes']['pid'] = $tv_node_arr[$key]['pid'];
		}		
		$tree_arr = recursiveTree( $arr , $pid );	
		echo json_encode($tree_arr);
	}
	
	
	//递归获取父级ID属下所有数据数组
    public function recursiveFindNode($_p_id, $_m){
        $node_arr = $_m -> where("pid='" . $_p_id . "'") -> select();
        static $arr;
        foreach ($node_arr as $key => $value) {
            $arr[] = $value;
			$_m_arr = $_m -> where("pid='" . $value['code'] . "'") -> find();
			if( !empty( $_m_arr['id'] ) ){
				$this -> recursiveFindNode( $value['code'] , $_m );
			}
        }
        return $arr;
    }

	
	//显示区域树数据
	public function showRegTree(){
		$region = M("region");
		$pid = session('region_id');
		$reg_arr = $this -> recursiveFindNode( $pid , $region );
		$arr = array();
		foreach( $reg_arr as $key => $value ){
			$arr[$key]['id'] = $reg_arr[$key]['code'];
			$arr[$key]['text'] = $reg_arr[$key]['text'];
			$arr[$key]['attributes']['pid']  = $reg_arr[$key]['pid'];
		}
		$tree_arr = $this -> recursiveRegTree( $arr , $pid );
		$tree_arr_arr = count( $tree_arr );
		for( $i = 0 ; $i < $tree_arr_arr ; $i++ ){
			$tree_arr[$i]['state'] = 'closed';
		}
		echo json_encode($tree_arr);	
	}
	
	//递归序列化区域树
	public function recursiveRegTree($tree, $rootId = 0) {  
		$return = array();  
		foreach($tree as $leaf) {  
			if( $leaf['attributes']['pid'] == $rootId ) { 		
				foreach( $tree as $subleaf ) {  
					if( $subleaf['attributes']['pid'] == $leaf['id'] ) {  
						$leaf['children'] = $this -> recursiveRegTree( $tree, $leaf['id'] );  
						break;  
					}  
				}  
				$return[] = $leaf;  
			}  
		}  
		return $return;  
	} 
	
	//TV用户编辑
	public function userEdit(){
		$user_model = M('user');
		$data = I('post.');
		$flag = I('post.flag');
		if ( empty( $data['nodes'] ) ) {
		    $data['nodes'] = 0;
        } else {
            $node_id_arr = explode(',', $data['nodes']);
            $node_code_arr = [];
            foreach ($node_id_arr as $k => $v) {
                $node_code_arr[$k] = M('tv_node')->where(['id' => $v])->getField('node_code');
            }
            $data['nodes'] = implode(',', $node_code_arr);
        }
		//else $data['nodes'] = $this -> screeningNode( $data['nodes'] );
		if( empty( $data['code'] ) ) $data['code'] = 0;
		$user_arr = $user_model -> where("account='" . $data['account'] . "'") -> find();
		if( $flag == '1' ){
			if( !empty( $user_arr['id'] ) ){
				createMsg('用户名重复', 1);
				return;
			}
			unset( $data['flag'] );
			unset( $data['id'] );
			$data['password'] = md5("123456");
			$data['region_id'] = session('region_id');
			$status = $user_model -> data( $data ) -> add();
            if ($data['type'] == '操作员') {
                $this::createUserJson($status, $data['code']);
            }
		}else if( $flag == '3' ){
			if( !empty( $user_arr['id'] ) ){
				if( $data['id'] != $user_arr['id'] ){
					createMsg('用户名重复', 1);
					return;					
				}
			}
			unset( $data['flag'] );
			$status = $user_model -> save( $data );
            if ($data['type'] == '操作员') {
                $this::createUserJson($data['id'], $data['code']);
            }
		}else{
			$datas['id'] = $data['id'];
			$datas['status'] = $data['status'];
			if( $flag == '4' ){
				$datas['password'] = md5('123456');
			}
			$status = $user_model -> save( $datas );
		}
		if (!empty($status)) createMsg('操作成功', 0);
		else createMsg('操作失败', 1);
		
	}

    /**
     * @FIXME 生成操作员用户的json文件
     * @param $_id int 用户ID
     * @param $_code string 用户所属区域码
     */
	static function createUserJson($_id, $_code)
    {
        $region = M('region');

        $arr = $region->where(['code' => $_code])->field(['id', 'code', 'text', 'level', 'pid'])->find();
        $arr_parent = $region->where(['code' => $arr['pid']])->field(['id', 'code', 'text', 'level', 'pid'])->find();
        $arr['parent'] = $arr_parent;

        $file_path = './public/user/' . $_id . '.json';
        file_put_contents($file_path , json_encode($arr));
    }
	
	//验证当前登录用户密码是否是初始的笨蛋密码
	public function passwordVerify(){
		$user_model = M('user');
		$id = session('user_id');
		$user_arr = $user_model -> where("id='".$id."'") -> find();
		$p = md5('123456');
		if( $user_arr['password'] == $p ) echo 0;
		else echo 1;
	}
	
	//更改密码
	public function changePassWord(){
		$user_model = M('user');
		$id = session('user_id');
		$new_pw = I('post.newPassWord');
		$old_pw = I('post.oldPassWord');
		$old_pw = md5( $old_pw );
		$user_arr = $user_model -> where("id='".$id."'") -> find();
		if( $user_arr['password'] != $old_pw ){
			$arr['status']='系统提示';
			$arr['message']='初始密码错误';	
			$arr['error']=0;
			return;
		}else{
			$data['password'] = md5( $new_pw );
			$data['id'] = $id;
			$status = $user_model -> save( $data );
			if(!empty($status)){
				$arr['status']='系统提示';
				$arr['message']='操作成功';
				$arr['error']=1;
			}else{
				$arr['status']='系统提示';
				$arr['message']='操作失败';	
				$arr['error']=0;
			}
			echo json_encode($arr);	
		}
	}
	
	//TV用户所属栏目版块筛选（非叶子节点删除）
	/*
	public function screeningNode( $_node ){
		$tv_node_model = M('tv_node');
		$node_arr = explode( ',' , $_node );
		$node_arr_count = count( $node_arr );
		for( $i = 0 ; $i < $node_arr_count ; $i++ ){
			$leaf_arr = $tv_node_model -> where("id='" . $node_arr[$i] . "'") -> field("is_leaf") -> find();
			if( $leaf_arr['is_leaf'] == 'no' ) unset( $node_arr[$i] );
		}
		$node_arr = array_values( $node_arr );
		$str = '';
		foreach( $node_arr as $value ){
			$str.= $value . ',';
		}
		return rtrim($str, ",");
	}
	*/
}