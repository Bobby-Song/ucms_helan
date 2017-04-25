<?php
    /**
     * 该文件用于定义全局使用的一些变量，如错误码
     *
     */
    
    
    /**
     * 键值表示将要定义的常量，数组第一位表示操作码，第二位表示操作码的描述
     * 
     * 约定如下：
     * 
     * 键值以SUC或者ERR开头，以表达其意的大写结束，中间以下划线隔开，将用来定义常量，
     * SUC开头的表示操作成功，code以1开头；ERR开头的表示操作失败，code以2开头。
     * 
     * 除0表示操作成功外，其他的操作码共5位；
     */
    $codeArr = array(
            'SUC_OPERATE'          =>  array(0, '操作成功！'),
            'SUC_LOGIN'             =>  array(10001, '登录成功！'),
            
            'ERR_LOGIN'             =>  array(20000, '登录失败！'),
            'ERR_OPERATE'           =>  array(20001, '操作失败！'),
            'ERR_PHONE_REGEISTER'   =>  array(20002, '手机号已被注册！'),
            'ERR_LOGIN_NAME'        =>  array(20003, '用户名或者密码错误！'),
            'ERR_CAPTCHA'           =>  array(20004, '验证码错误！'),
            'ERR_TOKEN'             =>  array(20005, 'TOKEN错误或者已失效！'),
            'ERR_PRAMA'             =>  array(20006, '参数错误！'),
            'ERR_AUTH'              =>  array(20007, '没有操作权限！'),
            'ERR_REGISTER'          =>  array(20008, '注册失败！'),
            'ERR_UNKNOWN'           =>  array(20009, '未知错误！'),
            'ERR_VOTE_REPEAT'       =>  array(20010, '您已投过票，请勿重复投票！'),
            'ERR_MAX_UPLOADS'       =>  array(20011, '上传数量超过限制！'),
            'ERR_BIND_REGION'       =>  array(20012, '当前用户没有绑定区域或者绑定未审核通过！'),
            'ERR_DATA_EMPTY'        =>  array(20013, '未查询到相关数据！'),
            'ERR_DATA_DUPLICATE'    =>  array(20014, '数据重复！'),
            'ERR_DATA_NOT_UPDATE'   =>  array(20015, '数据未发生任何改变！'),
            'ERR_INVALIDATA'        =>  array(20016, '操作校验未通过！'),
            'ERR_LOGIN_EMPTY'       =>  array(20017, '用户名或者密码不能为空！'),
            'ERR_SESSION_EXPIRE'    =>  array(20018, '会话已过期或者无效！'),
            'ERR_BIND_REGION'       =>  array(20019, '该用户已绑定区域，如果要重新绑定，请管理员解除绑定！'),
            'ERR_PICK_AREA'         =>  array(20027, '请先选择区域，再操作！')
    );
    
    foreach($codeArr as $key => $va){        
        define($key, json_encode($va));
    }