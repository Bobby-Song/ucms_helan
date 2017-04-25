<?php 

namespace Admin\Controller;
use Think\Controller;

class UserController extends Controller{
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * app端用户管理
     */
    public function userApp(){
        $_SESSION['userType'] = 'app';
        $this->assign('suffix', I('get._'));
        $this->display();
    }
    
    /**
     * app端用户管理
     */
    public function userWechat(){
        $_SESSION['userType'] = 'h5';
        $this->assign('suffix', I('get._'));
        $this->display('userApp');
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
    
    public function getAppUser(){
        $mDb = M('mobile_user');
        
        $ret = $mDb->field('id,name,nickname,email,address,phone,status')->where('user_type="'.$_SESSION['userType'].'"')->select();
        
        foreach($ret as $k=>$v){
            $ret[$k]['phone'] = substr_replace($v['phone'], '****', 3, 4);
        }
        
        echo json_encode($ret);
    }
    
    public function modifyUserStatus(){
        if(empty($_SESSION['type']) || $_SESSION['type'] != '系统总监'){
            logs('Controller: User | modifyUserStatus | current user have not autority!');
            standOutput('', ERR_AUTH);
        }
        
        $id = $_GET['id'];
        
        if(empty($id) || !check_positive_int($id)){
            logs('Controller: User | modifyUserStatus | id autority!');
            standOutput('', ERR_PRAMA);
        }
        
        $mDb = M("mobile_user");
        
        $data['status'] = array("exp", "abs(status-1)");
        
        $ret = $mDb->where('id=%s', array($id))->save($data);
        
        if($ret){
            standOutput('', SUC_OPERATE);
        }
        
        standOutput('', ERR_OPERATE);
    }
}