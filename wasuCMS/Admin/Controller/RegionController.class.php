<?php
namespace Admin\Controller;
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
    public function recursiveNodeArr( $_json_path, $flag = false ){
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
            
            $sort = 1;
            
            if( !empty( $root_json['marquee'] ) ){
                if($flag){
                    $home_arr[0] = array( "text"=>"滚动字幕", "type"=>"marquee", "level" => "2", "sort" => $sort  );
                }else{
                    $home_arr[0] = array( "text"=>"滚动字幕", "type"=>"marquee", "level" => "2");
                }                
            }

            foreach( $root_json['node'] as $key => $value ){
                if($flag){
                    $value["sort"] = ++$sort;
                }
                
                foreach( $value as $k => $v ){
                    if ( $k == 'type' ) {
                        if ( $v == 'pageLink' || $v == 'noDisplay' || $v == 'listContent' || $v == 'focusPic' || $v == 'videoFrame') {
                            $home_arr[] =  $value;
                        } elseif ( $v == 'column' || $v == 'activeNode') {
                            $node_arr[] = $value;
                        }
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
        $arr = $this -> recursiveNodeArr( $temp_path.'index_data.js', true );
        if( !empty( $arr ) ){
            $region_id = $region_arr['id'];
            $region_code = $region_arr['code'];

            $num = $tv_node -> where("region_id='".$region_code."'") -> count();
            if( $num > 0 ){
                $tv_node -> where("region_id='".$region_code."'") -> delete();
            }
            $datas['text'] = $region_arr['text'];

            $datas['region_id'] = $region_code;
            $datas['dir'] = '/'.$region_code;
            $datas['pid'] = 0;
            $pids = $tv_node -> data( $datas ) -> add();

            $this -> recursiveInsertNode( $arr , $tv_node , $pids , $temp_path , true , $region_code); // 将模板JSON转数组插入表
            import('FileUtil.FileUtil');//引入文件夹操作类库
            $file_edit = new \FileUtil();

            $file_path = '../webRoot/'.$region_code;
            if( !is_dir( $file_path ) ){
                mkdir( $file_path );
            }else{
                $file_edit -> unlinkDir( $file_path );
                mkdir( $file_path );
            }
            $file_edit -> copyDir( $template_arr['path'] , $file_path );
            chmod( $file_path , 0777 );
            recurDir( $file_path , 0777 );//递归更改文件目录权限

            $num = array();
            $arr = array();
            $a =array();

            //Fixme 增加查询条件:type != 'noDisplay'，将类型为noDisplay的栏目隐藏显示
            $num = $tv_node -> where("region_id={$region_code} and type != 'noDisplay'") -> select();
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
            file_put_contents( './public/json/'.$region_code.'_node.json' , json_encode( $arr ) );

            //生成区域CMS用户（操作/审核/发布）

            $operate['account'] = 'operate_'.$region_id;
            $operate['password'] = md5('123456');
            $operate['type'] = '操作员';
            $operate['region_id'] = $region_code;

            $audit['account'] = 'audit_'.$region_id;
            $audit['password'] = md5('123456');
            $audit['type'] = '内容审核';
            $audit['region_id'] = $region_code;

            $release['account'] = 'release_'.$region_id;
            $release['password'] = md5('123456');
            $release['type'] = '内容发布';
            $release['region_id'] = $region_code;

            $user = M('user');
            $user -> where("region_id=$region_code") -> delete();
            $user -> data( $operate ) -> add();
            $user -> data( $audit ) -> add();
            $user -> data( $release ) -> add();

            $idArr = $region->query("select getRegionChildLst('".$region_code."')");
            $ids = ltrim(current($idArr[0]), '$, ');

            if($ids == $data['id']){
                $rRet = $region->field('text,code,level')->where('code='.$ids)->select();
            }else{
                $rRet = $region->field('text,code,level')->where('code in ('.$ids.')')->select();
            }

            $muser = array();
            $pwd = '123456';
            $userpwd = encrypt_string($pwd);
            $salt = encrypt_string(get_sec_token(false));
            $mobileDB = M('mobile_user');
            $deleteArr = array();
            foreach($rRet as $rk=>$rv){
                $muser[$rk]['name'] = $rv['text'];
                $muser[$rk]['region_id'] = $rv['code'];
                $muser[$rk]['passwd'] = encrypt_string($userpwd.$salt);
                $muser[$rk]['salt'] = $salt;
                $muser[$rk]['user_type'] = 'h5';
                $muser[$rk]['use_type'] = '管理员'.$rv['level'];

                array_push($deleteArr, $rv['code']);

                if($rv['level'] == '4'){
                    $this->createVillageImg($rv['text'], $rv['code']);  //FIXME 生成首页logo图片
                }
            }

            $ret = $mobileDB->where('region_id in ('.implode(',', $deleteArr).')')->delete();
            if($ret){
                $mobileDB->addAll($muser);
            }else{
                $mobileDB->addAll($muser);
            }
            createMsg( '操作成功' , 0 );
        }else{
            createMsg( '操作失败' , 0 );
        }
    }

    /**
     * 自动生成图片
     * @param $_text
     * @param $_code
     */
    public function createVillageImg($_text, $_code)
    {
        $image = new \Think\Image();
        $root_code = substr($_code, 0, strlen($_code) - 6) . '000000';      // 获取根region code

        // 存放生成的logo图片的目录，不存在则创建
        $img_path = '../webRoot/' . $root_code . '/public/logo/';
        if (!file_exists($img_path)) {
            mkdir($img_path, 0777, true);
        }

        $logo_img = '../webRoot/img/linan_logo.png';               // 生成logo的底图
        $text = '美丽和谐' . $_text . '欢迎您！';                 // 生成logo的文字内容
        $root = $_SERVER['DOCUMENT_ROOT'];
        $ttf = $root.'/webRoot/img/linan_logo.ttf';                    // 字体
        $img_path_whole = $img_path . $_code . '.png';          // 生成logo图片的完整路径名称
        // 根据村名字数不同采用不同字体大小
        if ($this::absLength($_text) > 6) {
            $font_size = 23;
        } elseif ($this::absLength($_text) > 4) {
            $font_size = 25;
        } else {
            $font_size = 28;
        }
        $image->open($logo_img)->text($text, $ttf, $font_size, '#DA251C',\Think\Image::IMAGE_WATER_CENTER)->save($img_path_whole);
    }

    /**
     * 判断中文字符串长度
     * @param $str
     * @return int
     */
    static public function absLength($str)
    {
        if(empty($str)){
            return 0;
        }
        if(function_exists('mb_strlen')){
            return mb_strlen($str,'utf-8');
        }
        else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }

    //递归插入栏目数据
    public function recursiveInsertNode( $_ARR , $_M , $_PID , $_path , $bool , $_rid){
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
                $pids = $_M -> data( $f ) -> add();
            }

        }
        foreach( $arrs as $key => $val ){
            if ( is_array( $val ) ){
                if( !empty( $val['list'] ) ){
                    $f = $val;
                    $f['pid'] = $_PID;
                    $f['is_leaf'] = "yes";
                    $f['region_id'] = $_rid;
                    $pids = $_M -> data( $f ) -> add();
                }else{
                    $d = $val;
                    if (!empty($val['dir'])) {
                        $d['has_child'] = 'yes';
                    } else {
                        $d['has_child'] = 'no';
                    }
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
        $data['begin_ip'] = I( 'post.begin_ip' );
        $data['end_ip'] = I( 'post.end_ip' );
        $code = I( 'post.code' );

        $where = "(begin_ip='".$data['begin_ip']."' OR begin_ip='".$data['end_ip']."' OR end_ip='".$data['end_ip']."'";
        $where .= " OR end_ip='".$data['begin_ip']."') AND code!='".$code."'";
        $reg_arr = $region -> where( $where ) -> find();
        if( !empty( $reg_arr['id'] ) ){
            createMsg( 'IP数据重复，请进行修改' , 1 );
            return;
        }
        $status =  $region ->where(['code' => $code])-> save( $data );

        if ( !empty( $status ) ){
            if (substr($code, 6) != '000000') {    // 如果当前session中的region_id后6位不全是0，则强制替换成0
                $root_region_id = substr_replace($code, '000000', 6);
            } else {
                $root_region_id = $code;
            }
            $path = '..' . WEB_PATH . $root_region_id . '/public/data';
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            } else {
                chmod($path, 0777);
            }
            $path .= '/region.js';

            $village_code_arr = $this->getAllVillageRegion($root_region_id);

            $map['code'] = ['in', implode(',', $village_code_arr)];
            $region_arr = M('region')->where($map)->order('id')->field('id as region_id, text, code as region_code, begin_ip, end_ip')->select();

            if (empty($region_arr)) {
                $region_arr = [];
            }
            $js_contents = 'var regionArray =' . json_encode($region_arr) . ';';
            $ret = file_put_contents($path, $js_contents);
            if ($ret) {
                createMsg( '操作成功' , 0 );
            } else {
                createMsg( '操作失败' , 1 );
            }
        } else {
            createMsg( '操作失败' , 1 );
        }
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

    /**
     * 递归获取某区域下的所有村级区域
     * @param $_code string 当前区域码
     * @return array 所有村级区域码的集合
     */
    public function getAllVillageRegion($_code)
    {
        $region = M('region');
        static $arr = [];
        $region_id_arr = $region->where(['pid' => $_code])->field('code')->select();

        if (!empty($region_id_arr)) {
            foreach ($region_id_arr as $r_i_k => $r_i_v) {
                $this->getAllVillageRegion($r_i_v['code']);
            }
        } else {
            $arr[] = $_code;
        }
        return $arr;
    }
}