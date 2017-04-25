<?php

/**
 * @autor
 * @createtime 2016-03-29
 * @function   网格管理的操作
 * @desciption 网格管理
 */

namespace Admin\Controller;
use Think\Controller;

class InterfaceController extends Controller {
    
    private static $PAGES = 20; //手机端默认一页显示的数据个数
    private static $SMSEXPIRE = 3000; //手机验证码有效时间，单位：秒
    
    public function __construct(){
        parent::__construct();

        //login_authenticate();
    }
    
    public function test(){
        $len = count($_FILES['FileData']['name']);
        $file = array();
        $path = '';
        for($i=0; $i<$len; $i++){
            $file['name'] = $_FILES['FileData']['name'][$i];
            $file['type'] = $_FILES['FileData']['type'][$i];
            $file['tmp_name'] = $_FILES['FileData']['tmp_name'][$i];
            $file['error'] = $_FILES['FileData']['error'][$i];
            $file['size'] = $_FILES['FileData']['size'][$i];
        
            $tmp = upload_file(array('jpg', 'gif', 'png', 'jpeg'), $file, 'app');
        
            if($tmp != 'NOUPLOAD'){
                $path .= $tmp.'!!';
            }
        }
    }
    
    /**
     * 获取区域数据
     */
    public function getRegionData(){        
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | getRegionData | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $region_id = $_POST['regionId'];
        
        if(!check_regionid($region_id)){
            logs('Controller : Interface | getRegionData | regionId error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
        
        $rdb = M('region');
        
        $ret = $rdb->field('id,code,pid as pcode,text as name,level as levels')->where('pid = %s', array($region_id))->select();
        
        if($ret){
            standOutput($ret, SUC_OPERATE);
        }
        
        standOutput('', ERR_OPERATE);
    }
    
    /**
     * 根据手机号获取短信验证码
     */
    public function getSMSCode(){
        unset($_SESSION['PhoneNo']);
        unset($_SESSION['SMSCode']);
        unset($_SESSION['SMSCodeExpire']);
        
        $phoneno = $_POST['phoneNo'];
        if(!check_mobile($phoneno)){
            logs('Controller : Interface | getSMSCode | phoneNo error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
        
        $serialno = $_POST['serialNo'];
        if(!$this->_checkSerialNumber($serialno)){
            logs('Controller : Interface | getSMSCode | serialNo error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
        
        if($_POST['purpose'] == 'register'){
            $user = M("mobile_user");
            $num = $user->where("phone='%s'", array($phoneno))->count();
            
            if($num > 0){
                standOutput('', ERR_PHONE_REGEISTER);
            }
        }
        
        if($this->_sendSMS($phoneno, $serialno)){
            standOutput('', SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 注册用户第一步，获取手机验证码，接口getSMSCode
     * 注册用户第二步，同步手机验证码验证之后再行注册入库
     */
    public function registerUser(){
        if(empty($_SESSION['PhoneNo']) || $_COOKIE['PHPSESSID'] != session_id()){
            logs('Controller : Interface | registerUser | session error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
    
        if(!$this->_checkSMSCode($_POST['SMSCode'])){
            logs('Controller : Interface | registerUser | SMSCode error', 'WARN');
            standOutput('', ERR_CAPTCHA);
        }
    
        if(!empty($_POST['deviceToken']) && !$this->_checkDeviceToken($_POST['deviceToken'])){
            logs('Controller: Interface | registerUser | deviceTOKEN error');
            standOutput('', ERR_PRAMA);
        }
        
        $user = M("mobile_user");
        $num = $user->where("phone='%s'", array($_SESSION['PhoneNo']))->count();
        
        if($num > 0){
            logs('Controller : Interface | registerUser | cell-phone no. is exists', 'WARN');
            standOutput('', ERR_PHONE_REGEISTER);
        }
        
        $pwd = urandom_pwd();
        $userpwd = encrypt_string($pwd);
        $salt = encrypt_string(get_sec_token(false));
        $data['phone'] = $_SESSION['PhoneNo'];
        $data['serial_number'] = $_SESSION['SerialNo'];
        
        if(!empty($_POST['deviceToken'])){
            $data['device_token	'] = $_POST['deviceToken'];
        }
        
        $data['passwd'] = encrypt_string($userpwd.$salt);
        $data['salt'] = $salt;
        
        $userDB = M('mobile_user');
        $ret = $userDB->add($data);
    
        if($ret){
            if($this->_sendSMS($_SESSION['PhoneNo'], $_SESSION['SerialNo'], $pwd)){
                standOutput('', SUC_OPERATE);
            }
            
            standOutput('', ERR_REGISTER);
        }else{
            standOutput('', ERR_REGISTER);
        }
    }
    
    /**
     * 用户登录
     */
    public function signin(){
        $phone = $_POST['phoneNo'];
        $userpwd = encrypt_string($_POST['userPwd']);
        $serialno = $_POST['serialNo'];
    
        if(!check_mobile($phone)){
            logs('Controller : Interface | signin | phoneNo error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
    
        if(!$this->_checkSerialNumber($serialno)){
            logs('Controller : Interface | signin | serialNo error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
    
        //TODO 在第二阶段加上ip锁和账户锁，防止密码被暴力破解
    
        $user = M("mobile_user");
        $ret = $user->field('id,name,nickname,bind_regionid,bind_regionname,bind_status,region_id,region_name,phone,passwd,salt,photo,birthday,sex,first_login,use_type,(use_type+0) as use_typeid,err_times')->where("phone='%s'", array($phone))->find();
    
        $pwd = encrypt_string($userpwd.$ret['salt']);
    logs('-- -- --'.$ret['passwd']);
    logs('-- -- --'.$pwd);
        if(strcmp($ret['passwd'], $pwd) === 0){
            session_regenerate_id(true);
            $_SESSION['Interface_userID'] = $ret['id'];
            $_SESSION['Interface_PwdSalt'] = $ret['salt'];
            $_SESSION['Interface_SerialNoLen'] = mb_strlen($serialno);
            
            $data['ip'] = get_client_ip(1, true);
            $data['err_time'] = NULL;
            $data['err_times'] = 0;
            $data['lock_status'] = 'no';
            $data['serial_number'] = $serialno;
            $user->where('id ='.$ret['id'])->save($data);
    
            $text = $phone.$serialno.$pwd;
            $token = $this->_createAPIToken($ret['salt'], $text);
    
            $_SESSION[(string)md5($serialno)] = $text;
    
            $echoRet['id'] = $ret['id'];
            $echoRet['name'] = $ret['name'];
            $echoRet['nickname'] = $ret['nickname'];
            $echoRet['phone'] = substr_replace($ret['phone'], '****', 3, 4);
            $echoRet['token'] = $token;
            $echoRet['region_id'] = $ret['region_id'];
            $echoRet['photo'] = $ret['photo'];
            $echoRet['birthday'] = $ret['birthday'];
            $echoRet['sex'] = $ret['sex'];
            $echoRet['region_name'] = $ret['region_name'];
            $echoRet['use_type'] = $ret['use_type'];
            $echoRet['use_typeid'] = $ret['use_typeid'];
            $echoRet['bind_regionid'] = $ret['bind_regionid'];
            $echoRet['bind_regionname'] = $ret['bind_regionname'];
            $echoRet['bind_status'] = $ret['bind_status'];
            standOutput($echoRet, SUC_OPERATE);
        }else{
            $data['ip'] = get_client_ip(1, true);
            $data['err_time'] = date("Y-m-d H:i:s");
            $data['err_times'] = (int)$ret['err_times'] + 1;
            $user->where('phone ='.$phone)->save($data);
            standOutput('', ERR_LOGIN);
        }
    }
    
    /**
     * 登出
     */
    public function signout(){
        setCookie(session_name(), '');
        session_unset();
        session_destroy();
    }
    
    /**
     * 忘记密码后修改密码
     * 忘记密码第一步，获取手机验证码，接口getSMSCode
     * 忘记密码第二步，根据验证码并且输入新密码，提交修改原始密码
     */
    public function resetPasswd(){        
        if(empty($_SESSION['PhoneNo'])){
            logs('Controller : Interface | resetPasswd | phoneNo error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
        
        if(!$this->_checkSMSCode($_POST['SMSCode'])){
            logs('Controller : Interface | resetPasswd | SMSCode error', 'WARN');
            standOutput('', ERR_CAPTCHA);
        }

        $pwd = $_POST['userPwd'];
        if(strcmp($pwd, $_POST['userCfmPwd']) !== 0){
            logs('Controller : Interface | resetPasswd | pwd match error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
    
        if(!check_passwd($pwd)){
            logs('Controller: Interface | resetPasswd | userPwd error');
            standOutput('', ERR_PRAMA);
        }        
        
        $userpwd = encrypt_string($pwd);
        $salt = encrypt_string(get_sec_token(false));
        $data['passwd'] = encrypt_string($userpwd.$salt);
        $data['salt'] = $salt;
        
        $userDB = M('mobile_user');
        $ret = $userDB->where('phone = '.$_SESSION['PhoneNo'])->save($data);
    
        if($ret){
            standOutput('', SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 登录后修改密码
     */
    public function changePasswd(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | changePasswd | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
    
        if(!check_positive_int($_POST['userId'])){
            logs('Controller: Interface | changePasswd | userId error');
            standOutput('', ERR_PRAMA);
        }
    
        $phoneno = $_POST['phoneNo'];
        if(!check_mobile($phoneno)){
            logs('Controller : Interface | changePasswd | phoneNo error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
    
        $oldPasswd = $_POST['oldPwd'];
        if(!check_passwd($oldPasswd)){
            logs('Controller: Interface | changePasswd | oldPwd error');
            standOutput('', ERR_PRAMA);
        }
    
        $newPwd = $_POST['newPwd'];
        if(strcmp($newPwd, $_POST['newCfmPwd']) !== 0){
            logs('Controller : Interface | changePasswd | pwd match error', 'WARN');
            standOutput('', ERR_PRAMA);
        }
        
        if(!check_passwd($newPwd)){
            logs('Controller: Interface | changePasswd | newPwd error');
            standOutput('', ERR_PRAMA);
        }
    
        if($oldPasswd == $newPwd){
            logs('Controller: Interface | changePasswd | newPwd and oldPwd can not be same');
            standOutput('', ERR_PRAMA);
        }
    
        $userDB = M('mobile_user');
        $ret = $userDB->field('phone,passwd,salt')->where('id = '.$_POST['userId'])->find();
    
        if($ret['phone'] != $phoneno){
            standOutput('', ERR_OPERATE);
        }
    
        $pwd = encrypt_string(encrypt_string($oldPasswd).$ret['salt']);
    
        if(strcmp($ret['passwd'], $pwd) === 0){
            $salt = encrypt_string(get_sec_token(false));
            $data['passwd'] = encrypt_string(encrypt_string($newPwd).$salt);
            $data['salt'] = $salt;
            $ret1 = $userDB->where('id = '.$_POST['userId'])->save($data);
            if($ret1){
                $this->signout();
                standOutput('', SUC_OPERATE);
            }
        }
    
        standOutput('', ERR_OPERATE);
    }
    
    /**
     * 获取用户信息
     */
    public function getUserInfo(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | getUserInfo | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $id = $_SESSION['Interface_userID'];
        
        $user = M("mobile_user");
        $ret = $user->field('id,name,nickname,bind_regionid,bind_regionname,bind_status,region_id,region_name,phone,photo,birthday,sex,(use_type+0) as use_typeid')->where("id=".$id)->find();
        
        if($ret){
            $ret['phone'] = substr_replace($ret['phone'], '****', 3, 4);
            
            standOutput($ret, SUC_OPERATE);
        }
        
        standOutput('', ERR_OPERATE);
    }

    /**
     * 更新注册用户信息
     */
    public function updateUserInfo(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | updateUserInfo | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
    
        $data = array();
        
        $id = $_SESSION['Interface_userID'];
        $regionCode = $_POST['regionId'];
        $bindRegionCode = $_POST['bindRegionId'];
    
        if(!empty($_POST['name']) && check_text($_POST['name'])){
            logs('Controller: Interface | updateUserInfo | update name');
            $data['name'] = $_POST['name'];
        }
    
        if(!empty($_POST['nickname']) && check_text($_POST['nickname'])){
            logs('Controller: Interface | updateUserInfo | update nickname');
            $data['nickname'] = $_POST['nickname'];
        }
    
        if(!empty($regionCode) && check_regionid($regionCode)){
            logs('Controller: Interface | updateUserInfo | update regionId');
            $data['region_id'] = $regionCode;
            $data['region_name'] = $this->_getFullRegionName($regionCode);
        }
    
        if(!empty($_POST['address']) && check_text($_POST['address'])){
            logs('Controller: Interface | updateUserInfo | update address');
            $data['address'] = $_POST['address'];
        }
        
        if(!empty($_POST['email']) && filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)){
            logs('Controller: Interface | updateUserInfo | update email');
            $data['email'] = $_POST['email'];
        }
        
        if($_FILES){
            $tmp = upload_file(array('jpg', 'gif', 'png', 'jpeg'), $_FILES, 'app');
            logs('Controller: Interface | updateUserInfo | update photo | path = '.$tmp);
            if(file_exists('..'.$tmp)){
                logs('Controller: Interface | updateUserInfo | update photo');
                $data['photo'] = $tmp;
            }
        }
        
        if(!empty($_POST['birthday']) && check_date($_POST['birthday'])){
            logs('Controller: Interface | updateUserInfo | update birthday');
            $data['birthday'] = $_POST['birthday'];
        }
        
        if(!empty($_POST['sex']) && check_date($_POST['sex'])){
            logs('Controller: Interface | updateUserInfo | update sex');
            $data['sex'] = $_POST['sex'];
        }

        $userDB = M('mobile_user');
        
        if(!empty($bindRegionCode) && check_positive_int($bindRegionCode)){
            logs('Controller: Interface | updateUserInfo | bind region');
            $uret = $userDB->field('bind_regionid, bind_status')->where('id=%s', array($id))->find();
            if(empty($uret['bind_regionid']) || $uret['bind_status'] == 0){
                //绑定的村社区ID为空或者绑定状态为未绑定，那么可以继续绑定
                $data['bind_regionid'] = $bindRegionCode;
                $data['bind_regionname'] = $this->_getFullRegionName($bindRegionCode);
            }else{
                standOutput('', ERR_BIND_REGION);
            }
        }
        
        if(count($data) > 0){
            $ret = $userDB->where('id = '.$id)->save($data);
    
            if($ret){
                standOutput('', SUC_OPERATE);
            }
        }
    
        standOutput('', ERR_OPERATE);
    }
    
    /**
     * 根据区域ID获取栏目列表
     */
    public function getNode(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | getNode | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $userId = $_SESSION['Interface_userID'];
        $muDb = M('mobile_user');

        $muRet = $muDb->field('fav_node, rec_node, region_id')->where('id='.$userId)->find();
        
        if(empty($muRet['region_id'])){
            standOutput('', ERR_PICK_AREA);
        }

        $tnDb = M('tv_node');
        $regionId = substr_replace($muRet['region_id'], '000000', -6, 6);
        $tnRet = $tnDb->field("id")->where("pid=0 and region_id=%s", array($regionId))->find();
        
        if(empty($tnRet['id'])){
            standOutput('', ERR_UNKNOWN);
        }
        
        $where['pid'] = array("EQ", $tnRet['id']);
        $where['type'] = array("EQ", 'column');
        $where['region_id'] = array("EQ", $regionId);
        $where['_string'] = "find_in_set('app', node_type)";
        
        if(empty($muRet['fav_node']) && empty($muRet['rec_node'])){
            $ret = $tnDb->field("id, node_code, text as name, app_icon as icons")->where($where)->select();
            
            foreach($ret as $k => $v){
                $ret[$k]['is_fav'] = 'n';
            }
        }else{
            if(!empty($muRet['fav_node'])){
                $favNode = $muRet['fav_node'];
            
                if(strpos($favNode, ',') === false){
                    $where['node_code'] = array("EQ", $muRet['fav_node']);
                }else{
                    $where['node_code'] = array("IN", $muRet['fav_node']);
                }
            
                $favRet = $tnDb->field("id, node_code, text as name, app_icon as icons")->where($where)->order('field (node_code, '.$muRet['fav_node'].')')->select();
            
                foreach($favRet as $fk => $fv){
                    $favRet[$fk]['is_fav'] = 'y';
                }
            }
            
            if(!empty($muRet['rec_node'])){
                $recNode = $muRet['rec_node'];
            
                if(strpos($recNode, ',') === false){
                    $where['node_code'] = array("EQ", $muRet['rec_node']);
                }else{
                    $where['node_code'] = array("IN", $muRet['rec_node']);
                }
            
                $recRet = $tnDb->field("id, node_code, text as name, app_icon as icons")->where($where)->order('field (node_code, '.$muRet['rec_node'].')')->select();
            
                foreach($recRet as $rk => $rv){
                    $recRet[$rk]['is_fav'] = 'n';
                }
            }
            
            $ret = array_merge($favRet, $recRet);
        }        
        
        if($ret){
            standOutput($ret, SUC_OPERATE);
        }else{
            standOutput('', ERR_DATA_EMPTY);
        }
    }
    
    /**
     * 存储用户个性化栏目数据
     */
    public function updateNode(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | updateNode | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $favData = $_POST['favNodeData'];
        $recData = $_POST['RecNodeData'];
        
        if(!$this->_checkNodeList($favData)){
            logs('Controller: Interface | updateNode | favNodeData error');
            standOutput('', ERR_PRAMA);
        }
        
        if(!$this->_checkNodeList($recData)){
            logs('Controller: Interface | updateNode | RecNodeData error');
            standOutput('', ERR_PRAMA);
        }
        
        $mdb = M('mobile_user');
        
        $data['fav_node'] = $favData;
        $data['rec_node'] = $recData;
        
        $ret = $mdb->where('id = '.$_SESSION['Interface_userID'])->save($data);
        
        if($ret){
            standOutput('', SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 根据栏目ID获取对应子栏目及内容
     */
    public function getNodeContents(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | getNodeContents | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $regionId = $_POST['regionId'];
        $nodeId = $_POST['nodeCode'];
        
        $rows = $_POST['pageSize'];
        $page = $_POST['page'];
        $userId = $_SESSION['Interface_userID'];
        
        if(empty($regionId) || !check_positive_int($regionId)){
            logs('Controller: Interface | getNodeContents | regionId error'.$regionId);
            standOutput('', ERR_PRAMA);
        }
        
        if(empty($nodeId) || !check_positive_int($nodeId)){
            logs('Controller: Interface | getNodeContents | nodeId error');
            standOutput('', ERR_PRAMA);
        }
        
        if(empty($rows) || !check_positive_int($rows)){
            $rows = 20;
        }
        
        if(empty($page) || !check_positive_int($page)){
            $rows = 1;
        }        

        $offset = ($page - 1) * $rows;
        
        $retData = array();

        $cdb = M("content");
        $tvNodeDb = M('tv_node');
        $codb = M("comment");
        $udb = M("mobile_user");
        
        $cacheKey = md5($regionId.$nodeId);
        $cacheVal = $_SESSION[$cacheKey.'_cids'];
        $cacheExp = $_SESSION[$cacheKey.'_expire'];
        $url = 'http://'.C("SERVER_IP").':'.C("SERVER_PORT");
        
        if(empty($cacheVal) || ($cacheExp-time()) > 300){
            $tmpregionId = substr_replace($regionId, '000000', -6, 6);
            $tvRet = $tvNodeDb->field('id')->where('node_code=%s and region_id=%s', array($nodeId, $tmpregionId))->find();
            $idArr = $tvNodeDb->query('select getChildLst("'.$tvRet['id'].'")');
            $ids = ltrim(current($idArr[0]), '$, ');
            
            if(empty($ids)){
                standOutput('', ERR_OPERATE);
            }
            
            //获取当前栏目的儿子栏目列表
            $ret = $tvNodeDb->field("id, node_code, text as name, concat('{$url}', app_icon) as icons")->where("pid={$tvRet['id']} and find_in_set('app', node_type) and type != 'focusPic'")->select();
            
            //获取子栏目及其本身的node_code集合,用于获取对应的内容
            $cidArr = $tvNodeDb->field('node_code')->where('id in ('.$ids.') and is_leaf="yes"')->select();
            
            $cids = array();
            foreach($cidArr as $va){
                array_push($cids, $va['node_code']);
            }
            
            $cids = implode(',', $cids);
            $_SESSION[$cacheKey.'_cids'] = $cids;
            $_SESSION[$cacheKey.'_expire'] = time();
            
            if(count($ret) > 0){
                $_SESSION[$cacheKey.'_ret'] = serialize($ret);
            }
            
            $retData['nodelists'] = $ret;
        }else{
            $cids = $cacheVal;
            if(!empty($_SESSION[$cacheKey.'_ret'])){
                $retData['nodelists'] = unserialize($_SESSION[$cacheKey.'_ret']);
            }
        }
        
        $where['_string'] = 'find_in_set("app", con_type)';
        $where['is_node'] = array("EQ", "y");
        $where['type'] = array("NEQ", "应急广播");
        
        if(strpos($cids, ',') !== false) $where['node_id'] = array("IN", $cids);
        else $where['node_id'] = array("EQ", $cids);
        
        //获取所有叶子栏目的内容
        $cRet = $cdb->field('id, title, contents, operate_user_id, path as imglists, type, (type+0) as typeid, video_path as videopath')->where($where)->limit($offset, $rows)->select();
        
        $videourl = $url.'/'.VIDEO_PATH.'/';
        $walk = function($val) use($url){
            return $url.$val;
        };

        $conDB = M('consult');
        $conOptDB = M('consult_option');
        $queDB = M('question');
        $queOptDB = M('question_option');
        $quesRecDb = M('question_record');
        
        foreach($cRet as $key=>$va){
            $typeid = $va['typeid'];
            $cRet[$key]['commentsnum'] = $codb->where('content_id = '.$va['id'])->count();
            $tmp = $udb->field('name,nickname')->where('id = '.$va['operate_user_id'])->find();
            $cRet[$key]['name'] = $tmp['name'];
            $cRet[$key]['nickname'] = $tmp['nickname'];
        
            unset($cRet[$key]['operate_user_id']);
            
            $cRet[$key]['contents'] = str_replace('<img src="', '<img src="'.$url, $va["contents"]);
            
            if(!empty($va['imglists'])){
                $imgArr = explode('!!', $va['imglists']);
                $cRet[$key]['imglists'] = array_map($walk, $imgArr);
            }else{
                $cRet[$key]['imglists'] = [];
            }
            
            if($typeid == 3) { //相册
                $cRet[$key]['albumDesc'] = explode("!!", $va['contents']);
            } else if($typeid == 4) { //有图征询
                $conRet = $conDB->where('content_id='.$va['id'])->select();
                
                foreach($conRet as $tk=>$tv){
                    $conOptRet = $conOptDB->field('id, option_desc as opt, vote_number as vote')->where('consult_id='.$tv['id'].' and content_id='.$va['id'])->select();
                
                    $cRet[$key]['consultPic'][$tk]['id'] = $tv['id'];
                    $cRet[$key]['consultPic'][$tk]['title'] = $tv['c_title'];
                    $cRet[$key]['consultPic'][$tk]['content'] = $tv['c_content'];
                    $cRet[$key]['consultPic'][$tk]['picture'] = empty($tv['c_pic']) ? '':$url.$tv['c_pic'];
                    $cRet[$key]['consultPic'][$tk]['data'] = $conOptRet;
                }
            } else if($typeid == 5) { //无图征询
                $cRet[$key]['consult'] = $conOptDB->field('id, option_desc as opt, vote_number as vote')->where('content_id='.$va['id'])->select();
            } else if($typeid == 6) { //答题
                $queRet = $queDB->field('id,content_id,type,(type+0) as typeid,ques_title,ques_content,ques_pic')->where('content_id='.$va['id'])->select();

                $queswhere['user_id'] = array("EQ", $userId);
                $queswhere['content_id']  = array('EQ', $va['id']);
                
                $quesNum = $quesRecDb->where($queswhere)->count();
                
                foreach($queRet as $qk=>$qv){
                    $queOptRet = $queOptDB->field('id, option_name as opt, option_value as val')->where('question_id='.$qv['id'])->select();
                    
                    $cRet[$key]['question'][$qk]['id'] = $qv['id'];
                    $cRet[$key]['question'][$qk]['contentid'] = $qv['content_id'];
                    $cRet[$key]['question'][$qk]['type'] = $qv['type'];
                    $cRet[$key]['question'][$qk]['typeid'] = $qv['typeid'];
                    $cRet[$key]['question'][$qk]['title'] = $qv['ques_title'];
                    $cRet[$key]['question'][$qk]['content'] = $qv['ques_content'];
                    $cRet[$key]['question'][$qk]['picture'] = empty($qv['ques_pic']) ? '':$url.$qv['ques_pic'];
                    $cRet[$key]['question'][$qk]['answerStatus'] = $quesNum >= 1 ? 'y' : 'n';
                    $cRet[$key]['question'][$qk]['data'] = $queOptRet;
                }
            } else if($typeid == 7 || $typeid == 8) {
                if(!empty($va['videopath'])) $cRet[$key]['videopath'] = $videourl.$va['videopath'];
            }
        }
        
        
        $retData['items'] = $cRet;

        unset($cRet);
        unset($conRet);
        unset($conOptRet);
        unset($queRet);
        unset($queOptRet);
        
        standOutput($retData, SUC_OPERATE);
    }
    
    /**
     * 无图征询投票
     */
    public function voteConsult(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | voteConsult | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $optId = $_POST['optId'];
        $conId = $_POST['conId'];
        $userId = $_SESSION['Interface_userID'];
        $ip = get_client_ip(1);
        
        if(empty($optId) || !check_positive_int($optId)){
            logs('Controller: Interface | voteConsult | optId error');
            standOutput('', ERR_PRAMA);
        }
        
        if(empty($conId)){
            logs('Controller: Interface | voteConsult | conId is empty');
            standOutput('', ERR_PRAMA);
        }
        
        $where['user_id'] = array("EQ", $userId);
        $where['content_id']  = array('EQ', $conId);
        
        $rDb = M('consult_record');
        $num = $rDb->where($where)->count();
        
        if($num == 0){
            $cDb = M('consult_option');
            $ret = $cDb->where('id=%s', array($optId))->setInc("vote_number", 1);
        
            if($ret){
                $data['content_id'] = $conId;
                $data['option_id'] = $optId;
                $data['user_id'] = $userId;
                $data['ip'] = $ip;
                $data['join_time'] = date("Y-m-d H:i:s");
        
                $rDb->add($data);
        
                standOutput('', SUC_OPERATE);
            }else{
                standOutput('', ERR_OPERATE);
            }
        }else{
            standOutput('', ERR_VOTE_REPEAT);
        }
    }
    
    /**
     * 有图征询投票
     */
    public function voteConsultPic(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | voteConsultPic | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $optId = $_POST['optId'];
        $conId = $_POST["conId"];
        $cosId = $_POST['consultId'];
        $userId = $_SESSION['Interface_userID'];
        $ip = get_client_ip(1);
        
        if(empty($optId) || !check_positive_int($optId)){
            logs('Controller: Interface | voteConsultPic | optId error');
            standOutput('', ERR_PRAMA);
        }
        
        if(empty($conId) || !check_positive_int($conId)){
            logs('Controller: Interface | voteConsultPic | conId is empty');
            standOutput('', ERR_PRAMA);
        }
        
        if(empty($cosId) || !check_positive_int($cosId)){
            logs('Controller: Interface | voteConsultPic | consultId is empty');
            standOutput('', ERR_PRAMA);
        }
        
        $where['user_id'] = array("EQ", $userId);
        $where['content_id']  = array('EQ', $conId);
        $where['consult_id']  = array('EQ', $cosId);
        
        $rDb = M('consult_record');
        $num = $rDb->where($where)->count();
        
        if($num == 0){
            $cDb = M('consult_option');
            $ret = $cDb->where('id=%s', array($optId))->setInc("vote_number", 1);
        
            if($ret){
                $data['content_id'] = $conId;
                $data['consult_id'] = $cosId;
                $data['option_id'] = $optId;
                $data['user_id'] = $userId;
                $data['ip'] = $ip;
                $data['join_time'] = date("Y-m-d H:i:s");
        
                $rDb->add($data);
        
                standOutput('', SUC_OPERATE);
            }else{
                standOutput('', ERR_OPERATE);
            }
        }else{
            standOutput('', ERR_VOTE_REPEAT);
        }
    }
    
    /**
     * 回答问题
     */
    public function answerQuestion(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | answerQuestion | TOKEN error');
            standOutput('', ERR_TOKEN);
        }

        $conId = $_POST["conId"];
        $userId = $_SESSION['Interface_userID'];
        
        if(!$this->_checkContentId($conId)){
            logs('Controller: Interface | answerQuestion | conId is error');
            standOutput('', ERR_PRAMA);
        }

        $where['user_id'] = array("EQ", $userId);
        $where['content_id']  = array('EQ', $conId);
        
        $quesRecDb = M('question_record');
        $num = $quesRecDb->where($where)->count();
        
        if($num != 0){
            standOutput('', ERR_VOTE_REPEAT);
        }

        $data = array();
        $ip = get_client_ip(1);
        $tmpData = json_decode($_POST['data'], true);
        foreach($tmpData as $k=>$va){
            $quesId = $va['quesId'];
            $optId = $va['quesOptIds'];
            
            if(empty($quesId) || !check_positive_int($quesId)){
                logs('Controller: Interface | answerQuestion | quesId is error');
                standOutput('', ERR_PRAMA);
            }
            
            if(empty($optId) || !$this->_checkNodeList($optId)){
                logs('Controller: Interface | answerQuestion | optIds is error');
                standOutput('', ERR_PRAMA);
            }
            
            $data[$k]['content_id'] = $conId;
            $data[$k]['ques_id'] = $quesId;
            $data[$k]['quesopt_ids'] = $optId;
            $data[$k]['user_id'] = $userId;
            $data[$k]['ip'] = $ip;
            $data[$k]['join_time'] = date("Y-m-d H:i:s");
        }
            
        $ret = $quesRecDb->addAll($data);
        if($ret){
            standOutput('', SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 获取征询的投票结果
     */
    public function getVote(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | getVote | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $conId = $_POST['conId'];
        $cosId = $_POST['consultId'];
        
        if(empty($conId) || !check_positive_int($conId)){
            logs('Controller: Interface | getVote | conId error');
            standOutput('', ERR_PRAMA);
        }
        
        $where['content_id'] = array('EQ', $conId);
        if(!empty($cosId) && check_positive_int($cosId)){
            $where['consult_id'] = array('EQ', $cosId);
        }
        
        $cDb = M('consult_option');
        
        $ret = $cDb->field('id, option_desc as opt, vote_number as vote')->where($where)->select();
        
        if($ret){
            standOutput($ret, SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 根据内容ID获取对应评论
     */
    public function getComment(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | getComment | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $rows = $_POST['pageSize'];
        $page = $_POST['page'];
        $conId = $_POST['conId'];
        
        if(empty($rows) || !check_positive_int($rows)){
            $rows = 20;
        }
        
        if(empty($page) || !check_positive_int($page)){
            $page = 1;
        }

        $offset = ($page - 1) * $rows;
        $url = 'http://'.C("SERVER_IP").':'.C("SERVER_PORT");
        
        $comDb = M('comment');
        
        $ret = $comDb->alias('c')->field('c.id,c.content_id as conid,c.comment,c.comm_time as time,u.name,u.nickname,u.photo')->join('__MOBILE_USER__ as u ON u.id=c.author')->where('c.content_id=%s', array($conId))->limit($offset, $rows)->select();
    
        if($ret){
            foreach($ret as $k=>$v){
                $ret[$k]['comment'] = stripslashes($v['comment']);
                $ret[$k]['photo'] = empty($v['photo']) ? '' : $url.$v['photo'];
            }
            
            standOutput($ret, SUC_OPERATE);
        }else{
            standOutput('', ERR_DATA_EMPTY);
        }
    }
    
    /**
     * 根据内容ID增加评论
     */
    public function addComment(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addComment | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $conDb = M('content');
        $comDb = M('comment');
        
        $conId = $_POST['conId'];
        
        $num = $conDb->where('id=%s', array($conId))->count();
        
        if($num == 1){
            $data['content_id'] = $conId;
            $data['author'] = $_SESSION['Interface_userID'];
            $data['comment'] = addslashes($_POST['comment']);
            $data['comm_time'] = date('Y-m-d H:i:s');
            
            $ret = $comDb->add($data);
            
            if($ret){
                standOutput($ret, SUC_OPERATE);
            }
        }
        
        standOutput('', ERR_OPERATE);
    }
    
    /**
     * 获取可添加内容的栏目
     */
    public function getAddConNode(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addComment | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $pDB = M('node_permission');
        $user = M("mobile_user");
        $tvNodeDb = M("tv_node");
        
        $userId = $_SESSION['Interface_userID'];
        $uret = $user->field('bind_regionid')->where("id='%s' and bind_status=1", array($userId))->find();
        
        if(empty($uret['bind_regionid'])){
            standOutput('', ERR_BIND_REGION);
        }
        
        $regionId = substr_replace($uret['bind_regionid'], '000000', -6, 6);
        $url = 'http://'.C("SERVER_IP").':'.C("SERVER_PORT");
        
        $nodesRet = $pDB->field('node_code')->where('region_id='.$regionId)->find();
        $ret = $tvNodeDb->field("id, node_code, text as name, concat('{$url}', app_icon) as icons")->where("node_code IN ({$nodesRet['node_code']}) and region_id={$regionId} and find_in_set('app', node_type) and type != 'focusPic'")->select();
        
        if($ret){
            standOutput($ret, SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 可添加内容的栏目添加内容
     */
    public function addContent(){        
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addContent | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $userId = $_SESSION['Interface_userID'];
        $nodeId = $_POST['nodeCode'];
        $regionId = $this->_checkNodeCode($nodeId);
        
        if($regionId){
            $title = $_POST['title'];
            $content = $_POST['content'];
            $videoPath = $_POST['videoPath'];
            
            $tvDB = M('tv_node');
            $tvRet = $tvDB->field('level')->where('node_code=%s',array($nodeId))->find();
            
            $file = array();
            $path = '';
            $len = count($_FILES['FileData']['name']);
            for($i=0; $i<$len; $i++){
                $file['name'] = $_FILES['FileData']['name'][$i];
                $file['type'] = $_FILES['FileData']['type'][$i];
                $file['tmp_name'] = $_FILES['FileData']['tmp_name'][$i];
                $file['error'] = $_FILES['FileData']['error'][$i];
                $file['size'] = $_FILES['FileData']['size'][$i];
                
                $tmp = upload_file(array('jpg', 'gif', 'png', 'jpeg'), $file, 'app');
                
                if($tmp != 'NOUPLOAD'){
                    $path .= $tmp.'!!';
                }
            }
            
            $data['type'] = '图文';
            
            if(!empty($_FILES['videoPath'])){
                $videoPath = upload_file(array('mp4'), $_FILES['videoPath'], VIDEO_PATH);
                
                if($tmp != 'NOUPLOAD'){
                    $data['video_path'] = $videoPath;
                    $data['type'] = '窗口视频';
                }
            }
            
            $data['path'] = rtrim($path, '!!');
            $data['title'] = $title;
            $data['contents'] = $content;
            
            $data['node_id'] = $nodeId;
            if($tvRet['level'] == 2){
                $data['region_id'] = substr_replace($regionId, '000000', -6, 6);
            }else if($tvRet['level'] == 3){
                $data['region_id'] = substr_replace($regionId, '000', -3, 3);
            }else{
                $data['region_id'] = $regionId;
            }
            
            $data['operate_user_id'] = $userId;
            $data['user_src'] = 'mobile';
            $data['operate_time'] = date("Y-h-d H:i:s");
            
            $cDB = M('content');
            $ret = $cDB->add($data);
            
            if($ret){
                standOutput($ret, SUC_OPERATE);
            }else{
                standOutput('', ERR_OPERATE);
            }
        }
        
        standOutput('', ERR_AUTH);
    }
    
    /**
     * 删除内容
     */
    public function delContent(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addContent | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $userId = $_SESSION['Interface_userID'];
        $nodeId = $_POST['nodeCode'];
        $regionId = $this->_checkNodeCode($nodeId);
        
        if($regionId){
            $id = $_POST['conId'];
            
            $cDB = M('content');
            $ret = $cDB->where("id=%s and operate_user_id=%s and node_id=%s and region_id=%s and user_src='mobile' and status='待审核'", array($id, $userId, $nodeId, $regionId))->delete();
            
            if($ret){
                standOutput($ret, SUC_OPERATE);
            }else{
                standOutput('', ERR_OPERATE);
            }
        }
        
        standOutput('', ERR_AUTH);
    }
    
    /**
     * 获取当前用户指定的栏目内容
     */
    public function getUserContent(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addContent | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $userId = $_SESSION['Interface_userID'];
        $nodeId = $_POST['nodeCode'];
        $regionId = $this->_checkNodeCode($nodeId);
        
        if($regionId){
            $rows = $_POST['pageSize'];
            $page = $_POST['page'];
            
            if(empty($rows) || !check_positive_int($rows)){
                $rows = 20;
            }
            
            if(empty($page) || !check_positive_int($page)){
                $rows = 1;
            }
            
            $offset = ($page - 1) * $rows;
            
            $codb = M("comment");
            $condb = M('content');
            $udb = M('mobile_user');
            $url = 'http://'.C("SERVER_IP").':'.C("SERVER_PORT");
            $videourl = $url.'/webRoot/upload/'.VIDEO_PATH.'/';
            $walk = function($val) use($url){
                return $url.$val;
            };
            
            $cRet = $condb->field('id, title, contents, operate_user_id, path as imglists, type, (type+0) as typeid, video_path as videopath, status, (status+0) as statusid')->where("node_id=%s and operate_user_id=%s and user_src='mobile'", array($nodeId, $userId))->limit($offset, $rows)->select();
            
            foreach($cRet as $key=>$va){
                $typeid = $va['typeid'];
                $cRet[$key]['commentsnum'] = $codb->where('content_id = '.$va['id'])->count();
                $tmp = $udb->field('name,nickname')->where('id = '.$va['operate_user_id'])->find();
                $cRet[$key]['name'] = $tmp['name'];
                $cRet[$key]['nickname'] = $tmp['nickname'];
            
                unset($cRet[$key]['operate_user_id']);
            
                if(!empty($va['imglists'])){
                    $imgArr = explode('!!', $va['imglists']);
                    $cRet[$key]['imglists'] = array_map($walk, $imgArr);
                }else{
                    $cRet[$key]['imglists'] = [];
                }
                
                if($typeid == 7 || $typeid == 8) {
                    if(!empty($va['videopath'])) $cRet[$key]['videopath'] = $videourl.$va['videopath'];
                }
            }
            
            if($cRet){
                standOutput($cRet, SUC_OPERATE);
            }else{
                standOutput('', ERR_OPERATE);
            }
        }
        
        standOutput('', ERR_AUTH);
    }
    
    /**
     * 更新条目的内容
     */
    public function updateContent(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addContent | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $userId = $_SESSION['Interface_userID'];
        $nodeId = $_POST['nodeCode'];
        $regionId = $this->_checkNodeCode($nodeId);
        
        if($regionId){
            //$cDB = M('content');
            //$ret = $cDB->save($data);
        }
        
        standOutput('', ERR_AUTH);
    }

    /**
     * 添加应急广播
     */
    public function addEmergBC(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addContent | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
    
        $userId = $_SESSION['Interface_userID'];
        $user = M("mobile_user");
        $ret = $user->field('region_id,use_type')->where("id='%s'", array($userId))->find();
    
        if(strpos($ret['use_type'], '管理员') !== false){
            $title = $_POST['title'];
            $content = $_POST['content'];
    
            if(!empty($_FILES['audio'])){
                $audioPath = upload_file(array('amr'), $_FILES['audio'], VIDEO_PATH);
    
                if($audioPath != 'NOUPLOAD'){
                    $data['video_path'] = $audioPath;
                }
            }
    
            $data['title'] = $title;
            $data['contents'] = $content;
            $data['type'] = '应急广播';
            $data['region_id'] = $ret['region_id'];
            $data['operate_user_id'] = $userId;
            $data['user_src'] = 'mobile';
            $data['operate_time'] = date("Y-h-d H:i:s");
    
            $cDB = M('content');
            $ret = $cDB->add($data);
    
            if($ret){
                standOutput($ret, SUC_OPERATE);
            }else{
                standOutput('', ERR_OPERATE);
            }
        }
    
        standOutput('', ERR_AUTH);
    }
    
    /**
     * 获取应急广播列表
     */
    public function getEmergBC(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addContent | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
    
        $userId = $_SESSION['Interface_userID'];
        $user = M("mobile_user");
        $ret = $user->field('region_id')->where("id='%s'", array($userId))->find();
    
        $title = $_POST['title'];
        $content = $_POST['content'];
    
        $rows = $_POST['pageSize'];
        $page = $_POST['page'];
        if(empty($rows) || !check_positive_int($rows)){
            $rows = 20;
        }
    
        if(empty($page) || !check_positive_int($page)){
            $rows = 1;
        }
    
        $offset = ($page - 1) * $rows;
    
        $where['operate_user_id'] = array('EQ',$userId);
        $where['region_id'] = array('EQ',$ret['region_id']);
        $where['user_src'] = array('EQ','mobile');
        $where['type'] = array('EQ','应急广播');
    
        $cDB = M('content');
        $ret = $cDB->field('id,title,contents,video_path as audio,operate_time,status,review_msg')->where($where)->limit($offset, $rows)->select();
    
        if($ret){
            standOutput($ret, SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 获取app列表
     */
    public function getAppList(){
        if(!$this->_checkAPIToken($_SESSION['Interface_PwdSalt'], $_POST['TOKEN'], false)){
            logs('Controller: Interface | addContent | TOKEN error');
            standOutput('', ERR_TOKEN);
        }
        
        $apkDB = M('apk');
        $appDB = M('app');
        $appRet = $appDB->field('id, app_name')->select();
        
        foreach($appRet as $k=>$v){
            $ret = $apkDB->field('id,apk_packname,apk_vername,apk_version,apk_size,apk_icon,shot_icons,keywords,downloads,create_time')->where('app_id=%s',array($v['id']))->order('apk_version desc')->limit(1)->select();
            if($ret){
                $ret[0]['shot_icons'] = explode('!!', $ret[0]['shot_icons']);
                $appRet[$k]['apkList'] = $ret;
            }else{
                $appRet[$k]['apkList'] = [];
            }
        }
        
        if($appRet){
            standOutput($appRet, SUC_OPERATE);
        }else{
            standOutput('', ERR_OPERATE);
        }
    }
    
    /**
     * 将china_region表转换为层级结构region
     */
    public function syncCommunityDataToLocal(){
        /* ini_set('max_execution_time', 0);
        $comdb = M('china_region');
        $prodb = M('region');
    
        $ret = $comdb->field('province_id, province_name')->distinct(true)->select();
    
        $data = array();
    
        foreach($ret as $key=>$va){
            $data[$key]['code'] = $va['province_id'];
            $data[$key]['pcode'] = 0;
            $data[$key]['name'] = $va['province_name'];
            $data[$key]['levels'] = 0;
        }
    
        $prodb->addAll($data);
        unset($ret);
        unset($data);
        unset($va);
     
        $ret1 = $comdb->field('province_id, city_id, city_name')->distinct(true)->select();
         
        $data1 = array();
    
        foreach($ret1 as $key1=>$va1){
            $data1[$key1]['code'] = $va1['city_id'];
            $data1[$key1]['pcode'] = $va1['province_id'];
            $data1[$key1]['name'] = $va1['city_name'];
            $data1[$key1]['levels'] = 1;
        }
    
        $prodb->addAll($data1);
        unset($ret1);
        unset($data1);
        unset($va1);
    
        $ret2 = $comdb->field('city_id, county_id, county_name')->distinct(true)->select();
    
        $data2 = array();
    
        foreach($ret2 as $key2=>$va2){
            $data2[$key2]['code'] = $va2['county_id'];
            $data2[$key2]['pcode'] = $va2['city_id'];
            $data2[$key2]['name'] = $va2['county_name'];
            $data2[$key2]['levels'] = 2;
        }
    
        $prodb->addAll($data2);
        unset($ret2);
        unset($data2);
        unset($va2);
        
        $ret3 = $comdb->field('county_id, town_id, town_name')->distinct(true)->select();
    
        $data3 = array();
        
        $i = 0;
        foreach($ret3 as $key3=>$va3){
            $data3[$key3]['code'] = $va3['town_id'];
            $data3[$key3]['pcode'] = $va3['county_id'];
            $data3[$key3]['name'] = $va3['town_name'];
            $data3[$key3]['levels'] = 3;
        }
            
        $prodb->addAll($data3);
        unset($ret3);
        unset($data3);
        unset($va3);
    
        $ret4 = $comdb->field('town_id, village_id, village_name')->distinct(true)->select();
    
        $data4 = array();
    
        $i = 0;
        foreach($ret4 as $key4=>$va4){
            $data4[$i]['code'] = $va4['village_id'];
            $data4[$i]['pcode'] = $va4['town_id'];
            $data4[$i]['name'] = $va4['village_name'];
            $data4[$i]['levels'] = 4;
            
            if($i == 100){
                $prodb = M('region');
                $ret = $prodb->addAll($data4);
                echo $ret,$i,'<br/>';
                $i = -1;
                unset($data4);
            }
            
            $i++;
        }
    
        
        unset($ret4);
        unset($data4);
        unset($va4); */
    }
    
    /**
     * 解密字符串
     * @param string $key
     * @param string $ciphertext
     * @return string
     */
    private function _decryptString(&$key, &$ciphertext){
        $ciphertext_dec = base64_decode($ciphertext);
    
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CFB);
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);
    
        # 获取除初始向量外的密文
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
    
        # 可能需要从明文末尾移除 0
        $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CFB, $iv_dec);
    
        return $plaintext_dec;
    }
    
    /**
     * 校验手机序列号
     * @param string $number
     * @return boolean
     */
    private function _checkSerialNumber(&$number){
        return preg_match("/^[\dA-F]{14,16}$/", $number);
    }
    
    /**
     * 校验手机的设备TOKEN
     * @param numeric $token
     * @return boolean
     */
    private function _checkDeviceToken(&$token){
        return preg_match("/(^[a-zA-Z\d_\-]{44}$)|(^[a-zA-Z\d_]{64}$)/", $token);
    }
    
    /**
     * 发送短信验证码
     */
    private function _sendSMS($mobile, $serialnumber, $pwd = false){
        $secStr = '';
        $t1 = microtime(true) * 10000;
        $t2 = mt_rand(100, 999);
        
        $str = str_shuffle($serialnumber.$t1.$t2.$mobile);
        $len = mb_strlen($str) - 1;
        
        for($i = 0; $i < 6; $i++){
            $pos = rand(0, $len);
            $secStr .= $str[$pos];
        }
        
        vendor('dayu.TopSdk');
        
        $c = new \TopClient;
        $c ->appkey = '23656991';
        $c ->secretKey = '8fc4617d80cdce64b490d9888b8f64ba';
        
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req ->setExtend( "" );
        $req ->setSmsType( "normal" );
        $req ->setRecNum( $mobile );
        
        if(!$pwd){
            $req ->setSmsFreeSignName( "智慧社区融媒体" );
            $req ->setSmsParam( '{"code":"'.$secStr.'", "appname":"融媒体"}' );
            $req ->setSmsTemplateCode( "SMS_50330072" );
        }else{
            $req ->setSmsFreeSignName( "智慧社区融媒体" );
            $req ->setSmsParam( '{"passwd":"'.$pwd.'", "appname":"融媒体"}' );
            $req ->setSmsTemplateCode( "SMS_52195098" );
        }
        
        $resp = $c ->execute( $req );
        
        if($resp->code){
            logs('Controller: Interface | _sendSMS | ret code = '.$resp->code.' | msg = '.$resp->msg);
            return false;
        }else{
            logs('Controller: Interface | _sendSMS | ret err_code = '.$resp->result->err_code);
            
            if($resp->result->err_code == 0){
                
                $_SESSION['PhoneNo'] = $mobile;
                $_SESSION['SerialNo'] = $serialnumber;
                $_SESSION['SMSCode'] = $secStr;
                $_SESSION['SMSCodeExpire'] = time();
            
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 验证短信验证码
     */
    private function _checkSMSCode($code){
        $return = true;
        if(empty($_SESSION['SMSCode']) || empty($_SESSION['SMSCodeExpire'])){
            $return = false;
        }
    
        if((time() - $_SESSION['SMSCodeExpire']) > self::$SMSEXPIRE){
            $return = false;
        }
    
        if($_SESSION['SMSCode'] !== $code){
            $return = false;
        }
        
        unset($_SESSION['SMSCode']);
        unset($_SESSION['SMSCodeExpire']);
        
        return $return;
    }

    /**
     * 生成接口会话
     * @param string $key
     * @param string $text
     * @return string
     */
    private function _createAPIToken($key, $text){
    
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CFB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_DEV_URANDOM);
    
        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_CFB, $iv);
    
        # 将初始向量附加在密文之后，以供解密时使用
        $ciphertext = $iv . $ciphertext;
    
        # 对密文进行 base64 编码
        $ciphertext_base64 = base64_encode($ciphertext);
    
        return $ciphertext_base64;
    }
    
    /**
     * 校验接口会话
     * @param string $key
     * @param string $ciphertext
     * @return boolean
     */
    private function _checkAPIToken(&$key, &$ciphertext){
        $len = $_SESSION['Interface_SerialNoLen'];
        if(empty($ciphertext) || empty($key) || empty($len)){
            logs('Controller: Interface | _checkAPIToken | salt or token or Interface_SerialNoLen is empty!');
            return false;
        }
        
        $plaintext_dec = $this->_decryptString($key, $ciphertext);
        $mobile = substr($plaintext_dec, 0, 11);
        $serialnumber = substr($plaintext_dec, 11, $len);
        $pwd = substr($plaintext_dec, 11+$len);
        
        if(strcmp($_SESSION[(string)md5($serialnumber)], $plaintext_dec) === 0){
            $user = M("mobile_user");
            $ret = $user->field('passwd')->where("phone='%s'", array($mobile))->find();
            if(strcmp($ret['passwd'], $pwd) === 0){
                return true;
            }else{
                logs('Controller: Interface | _checkAPIToken | passwd error!');
                return false;
            }
        }else{
            logs('Controller: Interface | _checkAPIToken | token error!');
            return false;
        }
    }
    
    /**
     * 检测nodelist ID正确性
     * @param string $nlist
     * @return boolean
     */
    private function _checkNodeList(&$nlist){
        if(strpos($nlist, ',') === false){
            return check_positive_int($nlist);
        }else{
            $nArr = explode(',', $nlist);
            $ret = true;
            
            foreach($nArr as $va){
                if(!check_positive_int($va)){
                    $ret = false;
                    break;
                }
            }
            
            return $ret;
        }
    }
    
    /**
     * 
     */
    private function _checkContentId($id){
        if(empty($id) || !check_positive_int($id)){
            return false;
        }
        
        $conDB = M('content');
        $num = $conDB->where('id=%s', array($id))->count();
        
        if($num == 0){
            return false;
        }
        
        return true;
    }
    
    private function _checkNodeCode($nodeCode){
        $userId = $_SESSION['Interface_userID'];
        $user = M("mobile_user");
        $ret = $user->field('region_id')->where("id='%s'", array($userId))->find();
        $regionId = substr_replace($ret['region_id'], '000000', -6, 6);
        
        $pDB = M('node_permission');
        $nodesRet = $pDB->field('node_code')->where('region_id='.$regionId)->find();
        
        if(strpos($nodesRet['node_code'], $nodeCode) !== false){
            return $ret['region_id'];
        }
        
        return false;
    }
    
    private function _getFullRegionName($region_code){
        static $regionName = '';
        $regionDB = M('region');
        $r1 = $regionDB->field('text,pid')->where('code='.$region_code)->find();
        $r2 = $regionDB->field('text,pid')->where('code='.$r1['pid'])->find();
        $r3 = $regionDB->field('text,pid')->where('code='.$r2['pid'])->find();
        $r4 = $regionDB->field('text,pid')->where('code='.$r3['pid'])->find();
        $r5 = $regionDB->field('text,pid')->where('code='.$r4['pid'])->find();
        
        $vname = $r1['text'];
        $tname = $r2['text'];
        $aname = $r3['text'];
        $cname = $r4['text'];
        $sname = $r5['text'];
        
        return $sname.$cname.$aname.'@'.$tname.'@'.$vname;
    }
}