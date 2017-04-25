<?php
namespace Systems\Controller;
use Think\Controller;
class IndexController extends Controller {
	
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
	
    public function index(){
		$region_id = session('region_id');
		$this -> assign( 'region_id' , $region_id );
		$this -> assign( 'user_id' , session('user_id') );
		$this -> assign( 'level' , session('level') );
		$this -> assign( 'nodes' , session('nodes') );
		$this -> assign( 'code' , session('code') );
		$this -> assign( 'code_name' , session('code_name') );
		$this -> assign( 'code_level' , session('code_level') );
        $this -> display();
    }
	
    public function console(){
		$region_id = session('region_id');
		$this -> assign( 'region_id' , $region_id );
		$this -> assign( 'user_id' , session('user_id') );
		$this -> assign( 'level' , session('level') );
		$this -> assign( 'type' , session('type') );
        $this->display();
    }
	
	public function regSelectHtml(){
		$suffix = I( "get._" );
		$this -> assign( 'suffix' , $suffix );
        $this -> assign( 'user_type' , session('type') );
		$this->display();		
	}

    public function tvNodeManageHtml(){
        $suffix = I( "get._" );
        $this -> assign( 'suffix' , $suffix );
        $this->display();
    }

    public function appNodePermissionHtml(){
        $suffix = I( "get._" );
        $this -> assign( 'suffix' , $suffix );
        $this -> assign( 'region_id' , session('region_id') );
        $this -> assign( 'user_id' , session('user_id') );
        $this -> assign( 'level' , session('level') );
        $this -> display();
    }

    public function appUserRegionHtml(){
        $suffix = I( "get._" );
        $this -> assign( 'suffix' , $suffix );
        $this -> assign( 'region_id' , session('region_id') );
        $this -> assign( 'user_id' , session('user_id') );
        $this -> assign( 'level' , session('level') );
        $this -> display();
    }

    public function emergencyBroadcastHtml(){
        $suffix = I( "get._" );
        $this -> assign( 'suffix' , $suffix );
        $this -> assign( 'region_id' , session('region_id') );
        $this -> assign( 'user_id' , session('user_id') );
        $this -> assign( 'level' , session('level') );
        $this -> display();
    }

    /**
     * 获取某区域下的所有应急广播
     */
    public function emergencyBroadcastView()
    {
        $root_region_id = I('get.region');
        $content = M('content');

        $all_region = $this->getAllRegion($root_region_id);
        $all_region_id = implode(',', $all_region);
        $all_region_id .= ',' . $root_region_id;

        $map['type'] = ['eq', '应急广播'];
        $map['region_id'] = ['in', $all_region_id];

        $content_arr = $content->where($map)->field(['id', 'title', 'contents', 'region_id', 'video_path', 'status', 'operate_time', 'release_time'])->select();

        $arr['total'] = count($content_arr);
        $page_num = I('post.rows');
        $page = I('post.page');
        $start_num = $page_num * ($page - 1);
        if (isset($_POST['name']) && isset($_POST['value'])) {
            $name = I('post.name');
            $value = I('post.value');
            $map[$name] = ['like', '%' . $value . '%'];
        }
        $content_arr = $content->where($map)->order("id desc")->limit($start_num, $page_num)->field(['id', 'title', 'contents', 'region_id', 'video_path', 'status', 'release_time'])->select();

        if (!empty($content_arr)) {
            foreach ($content_arr as $c_key => $c_val) {
                $content_arr[$c_key]['region_name'] = $c_val['region_id'] != 0 ? M('region')->where(['code' => $c_val['region_id']])->getField('text') : '';
                $content_arr[$c_key]['voice'] = $c_val['video_path'] == '' ? '' : '<a href="' . $c_val['video_path'] . '">点击下载音频</a>';
                $content_arr[$c_key]['release_time'] = $c_val['release_time'] == '1970-01-01 00:00:00' ? '' : $c_val['release_time'];
                $content_arr[$c_key]['audit_status'] = $c_val['status'] == '待审核' ? '未播出' : ($c_val['status'] == '上线' ? '已播出' : '已驳回');
            }
        }
        $arr['rows'] = $content_arr;
        echo json_encode($arr);
    }

    /**
     * 审核应急广播
     */
    public function emergencyBroadcastAudit()
    {
        $flag = I('post.flag');
        $id_str = I('post.str');
        $id_arr = explode(',', $id_str);
        $content = M('content');
        foreach ($id_arr as $id_k => $id_v) {
            $data['status'] = $flag == 'y' ? '上线' : '驳回';
            $data['release_time'] = date('Y-m-d H:i:s');
            $ret = $content->where(['id' => $id_v])->save($data);
        }
        if (!empty($ret)) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }

    /**
     * 获取某区域内手机端用户
     */
    public function mobileUserView()
    {
        $root_region_id = I('get.region');
        $mobile_user = M('mobile_user');

        $all_region = $this->getAllRegion($root_region_id);
        $all_region_id = implode(',', $all_region);
        $all_region_id .= ',' . $root_region_id;

        $map['region_id'] = ['in', $all_region_id];
        $map['bind_regionid']  = ['in', $all_region_id];
        $map['_logic'] = 'or';
        $user_arr = $mobile_user->where($map)->field(['id', 'name', 'sex', 'phone', 'bind_regionid', 'bind_status'])->select();

        $arr['total'] = count($user_arr);
        $page_num = I('post.rows');
        $page = I('post.page');
        $start_num = $page_num * ($page - 1);
        if (isset($_POST['name']) && isset($_POST['value'])) {
            $name = I('post.name');
            $value = I('post.value');
            $where['_complex'] = $map;
            $where[$name] = ['like', '%' . $value . '%'];
            $user_arr = $mobile_user->where($where)->order("id")->limit($start_num, $page_num)->field(['id', 'name', 'sex', 'phone', 'bind_regionid', 'bind_status'])->select();
        } else {
            $user_arr = $mobile_user->where($map)->order("id")->limit($start_num, $page_num)->field(['id', 'name', 'sex', 'phone', 'bind_regionid', 'bind_status'])->select();
        }

        if (!empty($user_arr)) {
            foreach ($user_arr as $user_key => $user_val) {
                $user_arr[$user_key]['bind_region_name'] = $user_val['bind_regionid'] != 0 ? M('region')->where(['code' => $user_val['bind_regionid']])->getField('text') : '暂无绑定区域';
                $user_arr[$user_key]['status'] = $user_val['bind_status'] == 0 ? '无权限' : '有权限';
            }
        }
        $arr['rows'] = $user_arr;
        echo json_encode($arr);
    }

    /**
     * 审核手机端用户的绑定区域
     */
    public function mobileUserRegionAudit()
    {
        $id_str = I('post.str');
        $id_arr = explode(',', $id_str);
        $mobile_user = M('mobile_user');
        foreach ($id_arr as $id_k => $id_v) {
            $c_status = $mobile_user->where(['id' => $id_v])->getField('bind_status');
            $status = $c_status == 0 ? 1 : 0;
            $ret = $mobile_user->where(['id' => $id_v])->setField('bind_status', $status);
        }
        if (!empty($ret)) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }

    /**
     * 获取某区域已经设定的具有app上发布信息权限的栏目
     */
    public function appNodePermissionView()
    {
        $region_id = I('get.region_id');
        $permission = M('node_permission');
        $perm_arr = $permission->where(['region_id' => $region_id])->select();
        $arr['total'] = count($perm_arr);
        if (!empty($perm_arr)) {
            foreach ($perm_arr as $p_key => $p_val) {
                $perm_arr[$p_key]['region_name'] = M('region')->where(['code' => $region_id])->getField('text');
                $perm_arr[$p_key]['nodes'] = $p_val['node_code'];
                $perm_arr[$p_key]['region_id'] = $region_id;
            }
        }
        $arr['rows'] = $perm_arr;
        echo json_encode($arr);
    }

    /**
     * APP端可发布内容栏目权限编辑
     */
    public function appNodePermissionEdit()
    {
        $flag = I('post.flag');
        $data['region_id'] = I('post.region_id');
        $data['node_code'] = I('post.nodes');
        $permission = M('node_permission');

        if ($flag == 1) {
            $ret = $permission->add($data);
        } else {
            $data['id'] = I('post.id');
            $ret = $permission->save($data);
        }
        if ($ret) {
            createMsg( '操作成功' , 0 );
        } else {
            createMsg( '操作失败' , 1 );
        }
    }

    public function showNodeTree()
    {
        $region_id = I('get.region_id');
        $tv_node = M('tv_node');
        $dir = '/' . $region_id;
        $node_id = $tv_node->where(['dir' => $dir])->getField('id');
        $leaf_node_arr = $this->getLeafNode($node_id);
        $rows = [];
        foreach ($leaf_node_arr as $key => $val) {
            $row_arr = $tv_node->where(['id' => $val])->field(['text', 'node_code'])->find();
            $rows[$key]['id'] = $row_arr['node_code'];
            $rows[$key]['text'] = $row_arr['text'];
        }
        echo json_encode($rows);
    }

    /**
     * 递归获取所有的叶子节点ID
     * @param $_id
     * @return array
     */
    public function getLeafNode($_id)
    {
        static $arr = [];
        $node_arr = M('tv_node')->where(['pid' => $_id])->field(['id', 'is_leaf'])->select();
        if (!empty($node_arr)) {
            foreach ($node_arr as $node_key => $node_val) {
                if ($node_val['is_leaf'] != 1) {
                    $this->getLeafNode($node_val['id']);
                }
            }
        } else {
            $type = M('tv_node')->where(['id' => $_id])->getField('type');
            if (in_array($type, ['column', 'activeNode'])) {
                $arr[] = $_id;
            }
        }
        return $arr;
    }
	
	public function parameterHtml(){
		$suffix = I( "get._" );
		$this -> assign( 'suffix' , $suffix );	
		$this->display();		
	}
	/**
		** 退出系统
	*/
	public function exitSystem(){
		$login = A('Admin/Login');
		$login -> logOut();		
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
	
	/**
		** 生成区域树表格
	*/
	public function showRegionData(){
		$region = M( 'region' );
		$template = M( 'template' );
		$id = I( 'post.id' );
		$region_id = session('region_id');
		if( empty( $id ) ){
			$root_arr = $region -> where("code='$region_id'") ->  find();
            $root_arr['img'] = $root_arr['pic'] != '' ? '<img src=' . rtrim($root_arr['pic'], '!!') . ' style="width:50px;height:40px">' : '';
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
                $root_arr['children'][$key]['img'] = $value['pic'] != '' ? '<img src=' . rtrim($value['pic'], '!!') . ' style="width:50px;height:40px">' : '';
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
                $arr[$key]['img'] = $value['pic'] != '' ? '<img src=' . rtrim($value['pic'], '!!') . ' style="width:50px;height:40px">' : '';
			}
			echo json_encode( $arr );
		}		
	}

	/**
		** 选择区域
	*/	
	public function selectReg(){
		$data = I( 'post.' );
		$user_id = session( 'user_id' );
		$region = M( 'region' );
		$reg_arr = $region -> where( "code='" . $data['pid'] . "'" ) -> field( "id,text,code,level,pid" ) -> find();
		$data['parent'] = $reg_arr;
		$path = './public/user/' . $user_id . '.json';
		$boole = file_put_contents( $path , json_encode( $data ) );
		if( !empty( $boole ) ) createMsg( $data['text'].'选择成功' , 0 );
		else createMsg( '操作失败' , 1 );
	}

	public function editRegionData()
    {
        $code = I('post.code');
        $data['region_desc'] = I('post.region_desc');
        $data['pic'] = I('post.pic');
        $status = M('region')->where(['code' => $code])->save($data);

        // 生成区域简介json
        $code_data = M('region')->where(['code' => $code])->field(['pid', 'level'])->find();
        if ($code_data['level'] == 3) {         // 镇级区域
            $root_region_code = $code_data['pid'];

            $path = '..' . WEB_PATH . $root_region_code . '/public/data/' . $code;
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            } else {
                chmod($path, 0777);
            }
            $path .= '/' . $code . '_desc.js';
            $data['code'] = $code;
            $data['pic'] = $data['pic'] != '' ? rtrim($data['pic'], '!!') : '/webRoot/img/default_area_pic.jpg';
            file_put_contents($path, json_encode($data));

            // 生成街镇索引json
            $town_arr = M('region')->where(['pid' => $root_region_code])->field(['code', 'text', 'pic'])->order('id asc')->select();

        } elseif ($code_data['level'] == 4) {   // 村级区域
            $root_region_code = M('region')->where(['code' => $code_data['pid']])->getField('pid');

            // 生成村社索引json
            $town_arr = M('region')->where(['pid' => $code_data['pid']])->field(['code', 'text', 'pic'])->order('id asc')->select();
        } else {
            if ($status) {
                createMsg('操作成功', 0);
            } else {
                createMsg('操作失败', 1);
            }
            return;
        }

        if (!empty($town_arr)) {
            foreach ($town_arr as $t_key => $t_val) {
                $town_arr[$t_key]['pic'] = $t_val['pic'] != '' ? rtrim($t_val['pic'], '!!') : '/webRoot/img/default_area_pic.jpg';
            }
            $town_path = '..' . WEB_PATH . $root_region_code . '/zhjz';
            if (!file_exists($town_path)) {
                mkdir($town_path, 0777, true);
            } else {
                chmod($town_path, 0777);
            }
            if ($code_data['level'] == 3) {
                $town_path .= '/' . $root_region_code . '.js';
            } elseif ($code_data['level'] == 4) {
                $town_path .= '/' . $code_data['pid'] . '.js';
            }

            file_put_contents($town_path, json_encode($town_arr));
        }

        if ($status) {
            createMsg('操作成功', 0);
        } else {
            createMsg('操作失败', 1);
        }
    }

    public function unlinkRegionPic()
    {
        $arr['status'] = '系统提示';
        $flag = I('post.flag');
        $img_path = I('post.img_path');
        $icon = I('post.icon');
        $id = I('post.id');

        if (unlink($img_path)) {
            if ($flag == 'icon') {
                $data = array();
                $data['id'] = $id;
                if (strstr($icon, '!!'))
                    $data['pic'] = $icon;
                else
                    $data['pic'] = '';
                M('region')->save($data);
            }
            $arr['message'] = '操作成功';
            $arr['error'] = 1;
        } else {
            $arr['message'] = '操作失败';
            $arr['error'] = 0;
        }
        echo json_encode($arr);
    }

	public function showTvNodeData()
    {
        $tv_node = M('tv_node');
        $region_id = I('get.region_id');

        if (!isset($_POST['id'])) {
            if (substr($region_id, 6) != '000000') {    // 如果当前session中的region_id后6位不全是0，则强制替换成0
                $region_id = substr_replace($region_id, '000000', 6);
            }

            $arr = $tv_node->where(['pid' => 0, 'region_id' => $region_id])->find();    // 获取区县根栏目
            $arr['services'] = $arr['is_leaf'] == 'yes' ? '是' : '否';
            $arr['has_son'] = $arr['has_child'] == 'yes' ? '是' : '否';
            switch ($arr['level']) {
                case '0':
                    $arr['region_level'] = '区/县级栏目';
                    break;
                case '1':
                    $arr['region_level'] = '市级栏目';
                    break;
                case '2':
                    $arr['region_level'] = '区/县级栏目';
                    break;
                case '3':
                    $arr['region_level'] = '镇/街级栏目';
                    break;
                case '4':
                    $arr['region_level'] = '村/社区级栏目';
                    break;
            }
            $node_rows = $tv_node->where(['pid' => $arr['id']])->select();
            if (!empty($node_rows)) {
                foreach ($node_rows as $n_r_k => $n_r_v) {
                    $arr['children'][$n_r_k] = $n_r_v;
                    $arr['children'][$n_r_k]['services'] = $n_r_v['is_leaf'] == 'yes' ? '是' : '否';
                    $arr['children'][$n_r_k]['has_son'] = $n_r_v['has_child'] == 'yes' ? '是' : '否';
                    switch ($n_r_v['level']) {
                        case '0':
                            $arr['children'][$n_r_k]['region_level'] = '区/县级栏目';
                            break;
                        case '1':
                            $arr['children'][$n_r_k]['region_level'] = '市级栏目';
                            break;
                        case '2':
                            $arr['children'][$n_r_k]['region_level'] = '区/县级栏目';
                            break;
                        case '3':
                            $arr['children'][$n_r_k]['region_level'] = '镇/街级栏目';
                            break;
                        case '4':
                            $arr['children'][$n_r_k]['region_level'] = '村/社区级栏目';
                            break;
                    }
                    $node_row = $tv_node->where(['pid' => $n_r_v['id']])->select();
                    if(!empty($node_row)){
                        $arr['children'][$n_r_k]['state']='closed';
                        $arr['children'][$n_r_k]['count']=count($node_row);
                    }else{
                        $arr['children'][$n_r_k]['state']='open';
                    }
                }
            }
            echo "[".json_encode($arr)."]";
        } else {
            $id = I('post.id');
            $arr = $tv_node->where(['pid' => $id])->select();
            if (!empty($arr)) {
                foreach ($arr as $arr_key => $arr_val) {
                    $arr[$arr_key]['services'] = $arr_val['is_leaf'] == 'yes' ? '是' : '否';
                    $arr[$arr_key]['has_son'] = $arr_val['has_child'] == 'yes' ? '是' : '否';
                    switch ($arr_val['level']) {
                        case '0':
                            $arr[$arr_key]['region_level'] = '区/县级栏目';
                            break;
                        case '1':
                            $arr[$arr_key]['region_level'] = '市级栏目';
                            break;
                        case '2':
                            $arr[$arr_key]['region_level'] = '区/县级栏目';
                            break;
                        case '3':
                            $arr[$arr_key]['region_level'] = '镇/街级栏目';
                            break;
                        case '4':
                            $arr[$arr_key]['region_level'] = '村/社区级栏目';
                            break;
                    }
                    $node_row = $tv_node->where(['pid' => $arr_val['id']])->select();
                    if(!empty($node_row)){
                        $arr[$arr_key]['state']='closed';
                        $arr[$arr_key]['count']=count($node_row);
                    }else{
                        $arr[$arr_key]['state']='open';
                    }
                }
            }
            echo json_encode($arr);
        }
    }


    /**
     * tv栏目管理
     */
    public function editNodeGridTree()
    {
        $tv_node = M('tv_node');
        $data = I('post.');
        $BuildIndex = A('BuildIndex');

        if( $data['flag'] == 1 ){          // 新增
            $data['pid'] = $data['id'];
            $data['type'] = 'column';
            $data['is_leaf'] = 'yes';
            $data['level'] = '4';
            $data['icon_src'] = 'public/img/focus1.png';
            unset($data['id']);
            $result = $tv_node->add($data);
            $r_arr = $BuildIndex->recursiveFindDir($result, $tv_node);
            $BuildIndex->buildDirData($r_arr, $tv_node, $data['region_id'], $data['region_id']);
        }else if( $data['flag'] == 3 ){    // 修改
            $result = $tv_node->save($data);
            $r_arr = $BuildIndex->recursiveFindDirPlus($data['id'], $tv_node);
            $BuildIndex->buildDirData($r_arr, $tv_node, $data['region_id'], $data['region_id']);
        }else if( $data['flag'] == 2 ){    // 删除
            $r_arr = $BuildIndex->recursiveFindDir($data['id'], $tv_node);
            $result = $tv_node->delete($data['id']);
            $BuildIndex->buildDirData($r_arr, $tv_node, $data['region_id'], $data['region_id']);
        }

        //FIXME 生成栏目json数据
        $a =array();
        $num = $tv_node -> where(['region_id' => $data['region_id']]) -> select();
        $tv_node_arr_count = count( $num );
        for( $i = 0 ; $i < $tv_node_arr_count ; $i++ ){
            $a[$i]['id'] = $num[$i]['id'];
            $a[$i]['text'] = $num[$i]['text'];
            $a[$i]['attributes']['pid'] = $num[$i]['pid'];
            $a[$i]['attributes']['region_id'] = $num[$i]['region_id'];
            $a[$i]['attributes']['node_code'] = $num[$i]['node_code'];
            $a[$i]['attributes']['type'] = $num[$i]['type'];
            $a[$i]['attributes']['level'] = $num[$i]['level'];
            $a[$i]['attributes']['is_leaf'] = $num[$i]['is_leaf'];
            $a[$i]['attributes']['has_child'] = $num[$i]['has_child'];
        }
        $arr = recursiveTree( $a, 0 ); //递归生成区域CMS栏目树数组
        file_put_contents( './public/json/' . $data['region_id'] . '_node.json' , json_encode( $arr ) );

        if(!empty($result)){
            createMsg('操作成功' , 0);
        } else {
            createMsg('操作失败' , 1);
        }
    }


	/**
		** 参数设置 - 数据展示
	*/
	public function parameterShow(){
		$type_ext = M( 'type_ext' );
		$type_param = M( 'type_param' );
		$type_ext_arr = $type_ext -> select();
		$arr = array();
		$type_ext_count = count( $type_ext_arr );

		$f=0;
		if( !empty( $type_ext_count ) ){
			for( $i = 0 ; $i < $type_ext_count ; $i++ ){
				$type_param_arr = $type_param -> where("type_id='".$type_ext_arr[$i]['id']."'") -> select();
				$type_param_count = count( $type_param_arr );
				if( !empty( $type_param_count ) ){
					for( $k = 0 ; $k < $type_param_count ; $k++ ){
						$arr[$f]['name'] = $type_param_arr[$k]['text'];
						$arr[$f]['value'] = $type_param_arr[$k]['value'];
						$arr[$f]['group'] = $type_ext_arr[$i]['text'];
						$arr[$f]['editor'] = 'text';
						$f++;
					}
				}else{
					$arr[$f]['name'] = '';
					$arr[$f]['value'] = '';
					$arr[$f]['group'] = $type_ext_arr[$i]['text'];
					$f++;
				}
			}
		}
		$a = array();
		$a['total'] = count( $arr );
		$a['rows'] = $arr;
		echo json_encode( $a );
	}
	
	/**
		** 参数设置 - 数据编辑
	*/	
	public function parameterEdit(){
		$data = I( 'post.' );
		if( $data['flag'] == 'type_ext' ){
			$type_ext = M( 'type_ext' );
			unset( $data['flag'] );
			unset( $data['value'] );
			unset( $data['type_id'] );
			$arr = $type_ext -> where( "text='" . $data['text'] . "'" ) -> find();
			if( empty( $arr['id'] ) ){
				$status = $type_ext -> data( $data ) -> add();
			}else{
				createMsg( '类别名称重复' , 1 );
				return;
			}
		}else if( $data['flag'] == 'type_param' ){
			$type_param = M( 'type_param' );
			unset( $data['flag'] );
			$arr = $type_param -> where( "text='" . $data['text'] . "'" ) -> find();
			if( empty( $arr['id'] ) ){
				$status = $type_param -> data( $data ) -> add();
			}else{
				createMsg( '参数名称重复' , 1 );
				return;
			}
		}
		if( !empty( $status ) ) createMsg( '操作成功' , 0 );
		else createMsg( '操作失败' , 1 );		
	}
	
	/**
		** 参数设置 - 参数类别生成下拉菜单
	*/		
	public function typeExtData(){
		$type_ext = M( 'type_ext' );
		$type_ext_arr = $type_ext -> field( ' id , text ' ) -> select();
		echo json_encode( $type_ext_arr );
	}

	/**
	 * 移动端栏目图标上传
	 */
	public function iconManage(){
	    $suffix = I("get._");
	    $this->assign('suffix', $suffix);
	    $this->display('appNodeIcon');
	}
	
	public function getRegionTree(){
	    $tDb = M('tv_node');
	    $region_id = $_SESSION['region_id'];
	    
	    $root_arr = $tDb -> where("code='$region_id'") ->  find();
	    $code = $root_arr['code'];
	    $where = "pid='".$root_arr['code']."'";
	    $region_arr = $tDb -> where($where) -> select();
	    $root_arr['children'] = $region_arr;
	    foreach( $region_arr as $key => $value ){
	        foreach( $value as $k => $v ){
	            if( $k =='code'){
	                $reg_arr = $tDb -> where("pid=$v") -> select();
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
	}
	
	public function appNodeIcon(){
	    $suffix = I( "get._" );
	    $this -> assign( 'suffix' , $suffix );
	    
	    $this->display();
	}
	
	/**
	 * 获取APP栏目结构树
	 */
	public function getAppNodeTree(){
	    $nodeDB = M('tv_node');
	     
	    $where['region_id'] = $_SESSION['region_id'];
	    $where['type'] = array("EQ", 'column');
	    $where['_string'] = "find_in_set('app', node_type)";
	     
	    $ret = $nodeDB->field('id,node_code,pid,text,app_icon,tv_icon')->where($where)->select();
	    $url = 'http://'.C("SERVER_IP").':'.C("SERVER_PORT");
	    
	    foreach($ret as $k=>$v){
	        $ret[$k]['attributes']['app_icon'] = empty($v['app_icon']) ? '' : $url.$v['app_icon'];
	        $ret[$k]['attributes']['tv_icon'] = empty($v['tv_icon']) ? '' : $url.$v['tv_icon'];
            $ret[$k]['attributes']['node_code'] = $v['node_code'];

	        unset($ret[$k]['app_icon']);
	        unset($ret[$k]['tv_icon']);
            unset($ret[$k]['node_code']);
	    }
	    
	    $ret = buildTree($ret, 'pid', 'id');
	     
	    echo json_encode($ret);
	}
	
	/**
	 * app栏目图标上传
	 */
	public function AppNodeIconUpload(){
	    $from = I('get.from');
	    $path = upload_file(array('jpg', 'gif', 'png', 'jpeg'), $_FILES, 'app');
	    
	    $ret = array();
	    if($path != 'NOUPLOAD'){
	        $ret['success'] = true;
	        $ret['fileName'] = 'http://'.C("SERVER_IP").':'.C("SERVER_PORT").$path;
            if ($from == 'app') {
                $_SESSION['app_fileName'] = $path;
            } else {
                $_SESSION['tv_fileName'] = $path;
            }
	    }else{
	        $ret['success'] = false;
	        $ret['fileName'] = '';
	    }

	    echo json_encode($ret);
	}
	
	public function saveAppNodeIcon(){
	    $app_filename = $_SESSION['app_fileName'];
	    $tv_filename = $_SESSION['tv_fileName'];

        $region_id = session('region_id');

	    if(empty($app_filename) && empty($tv_filename)){
	        logs('Controller: Index | saveAppNodeIcon | upload file is empty!');
	        standOutput('', ERR_PRAMA);
	    }

	    $nodeId = $_GET['id'];
	    if(empty($nodeId) || !check_positive_int($nodeId)){
	        logs('Controller: Index | saveAppNodeIcon | nodeid is wrong!');
	        standOutput('', ERR_PRAMA);
	    }

        $tv_savefile = '';
        $app_savefile = '';

	    $url = 'http://'.C("SERVER_IP").':'.C("SERVER_PORT");

	    /* 生成缩略图  */
	    $img = new \Think\Image();
        if (!empty($app_filename)) {
            $app_file = pathinfo($app_filename);
            $app_savefile = $app_file['dirname'].'/'.$app_file['filename'].'_t.'.$app_file['extension'];
            $img->open('..'.$app_filename);
            $img->thumb(120, 120, \Think\Image::IMAGE_THUMB_FIXED)->save('..'.$app_savefile);
            $data['app_icon'] = $app_savefile;
            /* 删除原图  */
            unlink('..'.$app_filename);
        }

        if (!empty($tv_filename)) {
            $tv_file = pathinfo($tv_filename);
            $tv_savefile = $tv_file['dirname'].'/'.$tv_file['filename'].'_t.'.$tv_file['extension'];
            $img->open('..'.$tv_filename);
            $img->thumb(300, 192, \Think\Image::IMAGE_THUMB_FIXED)->save('..'.$tv_savefile);
            $data['tv_icon'] = $tv_savefile;
            unlink('..'.$tv_filename);
        }


	    /* 更新APP栏目图标路径  */
	    $nodeDB = M('tv_node');

	    $ret = $nodeDB->where(array('node_code' => $nodeId, 'region_id' => $region_id))->save($data);
	    
	    unset($_SESSION['app_fileName']);
	    unset($_SESSION['tv_fileName']);

	    if($ret){
	        $app_url = $app_savefile != '' ? $url.$app_savefile : '';
	        $tv_url = $tv_savefile != '' ? $url.$tv_savefile : '';
	        standOutput($app_url . '___' . $tv_url, SUC_OPERATE);

	    }
	    
	    standOutput('', ERR_OPERATE);
	}


	/**
	 * 用户载入提示当前数据变化（操作、审核、发布数据变化提示）
	 */	
	public function prompt(){
		$prefix = C('DB_PREFIX');
		$content_model = M('content');
		$type = session('type');
		$user_id  = session('user_id');

        $root_region_id = M('user')->where(['id' => $user_id])->getField('region_id');

        $all_region = $this->getAllRegion($root_region_id);
        $all_region_id = implode(',', $all_region);
        $all_region_id .= ',' . $root_region_id;

        $field = " c.*, r.text as region_name";
        $where['c.type'] = array('NEQ', '应急广播');
        $where['c.region_id'] = array('IN', $all_region_id);
		if( $type == '操作员' ){
            $where['c.operate_user_id'] = array('EQ', $user_id);
            $where['c.user_src'] = array('EQ', 'user');
            $where['c.status'] = array('IN', ['下线', '驳回']);
		}else if( $type == '内容审核' ){
            $where['c.status'] = array('EQ', '待审核');
		}else if( $type == '内容发布' ){
            $where['c.status'] = array('EQ', '已审核');
		}
        $con_arr = $content_model->alias('c')->join('__REGION__ as r ON r.code=c.region_id') -> where( $where ) -> field( $field ) -> select();
        if (!empty($con_arr)) {
            foreach ($con_arr as $con_key => $con_value) {
                $con_arr[$con_key]['md5_id'] = md5($con_value['id']);
                if (in_array($con_value['type'], ['无图征询', '有图征询'])) {
                    if ($con_value['type'] == '无图征询') {
                        $cons_arr = M('consult_option')->where(['content_id' => $con_value['id']])->order('id asc')->field('option_desc')->select();
                        $options_arr = array_column($cons_arr, 'option_desc');
                        $con_arr[$con_key]['options'] = implode('!!', $options_arr);
                    } elseif ($con_value['type'] == '有图征询') {
                        $consult_arr = M('consult')->where(['content_id' => $con_value['id']])->order('id asc')->select();
                        $pic_arr = array_column($consult_arr, 'c_pic');
                        $pic_str = implode('!!', $pic_arr);
                        $title_arr = array_column($consult_arr, 'c_title');
                        $title_str = implode('!!', $title_arr);
                        $content_arr = array_column($consult_arr, 'c_content');
                        $content_str = implode('!!', $content_arr);
                        $id_arr = array_column($consult_arr, 'id');
                        $id_str = implode('!!', $id_arr);

                        $con_arr[$con_key]['contents'] = $pic_str . '@@' . $title_str . '@@' . $content_str . '@@' . $id_str;
                        $cons_arr = M('consult_option')->where(['consult_id' => $id_arr[0]])->order('id asc')->field('option_desc')->select();
                        $options_arr = array_column($cons_arr, 'option_desc');
                        $con_arr[$con_key]['options'] = implode('!!', $options_arr);
                    }

                } elseif ($con_value['type'] == '监控') {
                    $con_arr[$con_key]['contents'] = M('camera_ext')->where(['content_id' => $con_value['id']])->getField('params');
                } elseif (in_array($con_value['type'], ['全屏视频', '窗口视频'])) {
                    if ($con_value['video_path'] == '') {
                        $con_arr[$con_key]['video_path'] = $con_value['tv_play_url'];
                    }
                } elseif ($con_value['type'] == '答题') {
                    $ques_arr = M('question')->where(['content_id' => $con_value['id']])->order('id asc')->field(['id', 'type', 'ques_title', 'standard_answer'])->select();
                    //echo json_encode($ques_arr);exit;
                    static $ques_contents;
                    foreach ($ques_arr as $ques_key => $ques_val) {
                        $ques_opts = M('question_option')->where(['question_id' => $ques_val['id']])->order('id asc')->field('option_value')->select();
                        $ques_opt = implode('##', array_column($ques_opts, 'option_value'));
                        $ques_contents .= $ques_val['ques_title'] . '!!' . $ques_val['type'] . '!!' . $ques_val['standard_answer'] . '!!' . $ques_opt . '!!' . $ques_val['id'] . '@@';
                    }
                    $con_arr[$con_key]['contents'] = rtrim($ques_contents, '@@');
                }
            }
        }

		$data['total'] = count( $con_arr );
		$data['rows'] = $con_arr;
		echo json_encode( $data );				
	}


    /**
     * 递归获取区域的所有子区域码
     * @param $_id  string 区域码
     * @return array 返回一个存放所有的子区域码的数组
     */
	public function getAllRegion($_id)
    {
        $region = M('region');
        static $arr = [];
        $region_id_arr = $region->where(['pid' => $_id])->field(['code', 'level'])->select();

        if (!empty($region_id_arr)) {
            foreach ($region_id_arr as $r_i_k => $r_i_v) {
                $arr[] = $r_i_v['code'];
                if ($r_i_v['level'] < 4) {
                    $this->getAllRegion($r_i_v['code']);
                }
            }
        } else {
            $arr[] = $_id;
        }
        return $arr;
    }
}