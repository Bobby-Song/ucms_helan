<?php
/**
 * 日志记录函数
 * @param string $con   日志正文
 * @param string $level 日志级别
 */
function logs($con, $level = 'WARN'){
    \Think\Log::write('[ '.date('c').' ] '.$con, $level);
}

/**
 *
 * @param array $data
 * @param integer $code
 * @param string $msg
 * @return string JsonSerializable
 */
function standOutput($data, $code = SUC_OPERATE){

    $code = json_decode($code, true);

    $ret = array(
            'code'=>$code[0],
            'desc'=>$code[1],
            'data'=>$data
    );

    exit(json_encode($ret));
}

/**
 * 生成父子结构的树
 * @param array $flat
 * @param string $pidKey
 * @param string $idKey
 * @return array
 */
function buildTree($flat, $pidKey, $idKey = null)
{
    $grouped = array();
    foreach ($flat as $sub){
        $grouped[$sub[$pidKey]][] = $sub;
    }

    $fnBuilder = function($siblings) use (&$fnBuilder, $grouped, $idKey) {
        foreach ($siblings as $k => $sibling) {
            $id = $sibling[$idKey];
            if(isset($grouped[$id])) {
                $sibling['children'] = $fnBuilder($grouped[$id]);
            }
            $siblings[$k] = $sibling;
        }

        return $siblings;
    };

    $tree = $fnBuilder(current($grouped));

    return $tree;
}

/**
 * getStringLen
 * 获取字符串长度
 * @param string $str  需要计算长度的字符串
 * @return int
 */
function get_string_len($str){
    preg_match_all("/./us", $str, $match);

    return count($match[0]);
}

/**
 * @todo 获取客户端类型
 * @return string
 */
function get_client_type(){
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

    if(strpos($agent, 'windows')){
        return 'pc';
    }else if(strpos($agent, 'iphone')){
        return 'ios';
    }else if(strpos($agent, 'android')){
        return 'android';
    }
}

/**
 * @todo 文件上传
 * @param array $extlist
 * @param array $files
 * @param string $subpath
 * @param number $maxsize
 * @return string 返回上传全路径
 */
function upload_file(array $extlist, $files, $subpath = 'event', $maxsize=""){
    ini_set('max_execution_time', '0');
    ini_set("memory_limit", "1000M");
    
    if(count($files)){
        $despath = '../webRoot/upload/';

        $config = array(
                'maxSize'    =>    $maxsize,
                'rootPath'   =>    $despath,
                'savePath'   =>    $subpath.'/',
                'saveName'   =>    array('uniqid',''),
                'exts'       =>    $extlist,
                'autoSub'    =>    true,
                'subName'    =>    array('date','Ymd'),
                'hash'       =>    false,
        );
        $upload = new \Think\Upload($config);
        if($files["Filedata"]){
            $upinfo = $upload->uploadOne($files["Filedata"]);
        }else{
            $upinfo = $upload->uploadOne($files);
        }
        if(!$upinfo){
            logs('CommonFunction | uploadfile | upinfo = '.print_r($files, true).' | uperror = '.$upload->getError().' | '.$files['tmp_name'], 'WARN');
            return 'NOUPLOAD';
        }else{
            return ltrim($despath.$upinfo['savepath'].$upinfo['savename'], '.');
        }
    }else{
        return '';
    }
}

/**
 * @todo 不可逆加密字符串
 * @param string $str
 * @return string 加密结果
 */
function encrypt_string($str){
    return hash('ripemd128', $str);
}

/**
 * loginAuthenticate
 * 登陆状态鉴权
 * @return boolean
 */
function login_authenticate($flag = true){
    if(empty($_COOKIE['PHPSESSID']) || session_id() !== $_COOKIE['PHPSESSID']){

        logout();
        //header('location: /Login/login');
        exit("<script>window.top.location = '/Login/login';</script>");
    }

    if($flag){
        if(empty($_SESSION['FirstLogin'])){
            logout();
            exit("<script>window.top.location = '/Login/login';</script>");
            //header('location: /Login/login');
        }elseif($_SESSION['FirstLogin'] == 'y'){
            exit('请先修改密码，然后再使用本系统！');
        }
    }

    if($_SESSION['"'.session_id().'"'] != md5($_SERVER['HTTP_USER_AGENT'].get_client_ip(1, true))){
        //logout();
        //exit("<script>window.top.location = '/Login/login';</script>");
        // header('location: /Login/login');
    }

    if(empty($_SESSION['LastOperateTime'])){
        logout();
        exit("<script>window.top.location = '/Login/login';</script>");
        //header('location: /Login/login');
    }
}

function logout(){
    setCookie(session_name(), '');
    session_unset();
    session_destroy();
}

/**
 * getSecToken
 * @todo 获取一个安全的随机数
 * @return string
 */
function get_sec_token($flag = true){
    $secStr = '';

    try {
        if(DIRECTORY_SEPARATOR == '\\'){
            //$capi = new COM('CAPICOM.Utilities.1');
            //$secStr = $capi->GetRandom(28, 0);
            $secStr = create_token();
        }else{
            $f = fopen('/dev/urandom', 'rb');
            if(!$f){
                $secStr = fread($f, 28);
            }
            fclose($f);
        }
    }catch(Exception $e){}

    if(empty($secStr)){
        $secStr = create_token();
    }

    if($flag) $_SESSION['secStr'] = $secStr;
    return $secStr;
}

/**
 * createToken
 */
function create_token(){
    $str = 'MNa)32defg{hCGH#Iijklqr|·^stuDEvw$1%xyzABJK~`QRS!@bcL?/;OPTUVW+=XYZ098}[7mnop6&F*(540-_<,>.:\\\'"]';

    $secStr = '';
    $len = mb_strlen($str) - 1;
    for($i = 0; $i < 8; $i++){
        $pos = rand(0, $len);
        $secStr .= $str[$pos];
    }

    $secStr .= microtime(true);

    $secStr .= uniqid(mt_rand(), true);

    return substr(hash("sha256", $secStr), 0, 28);
}

/**
 * 生成8位的随机密码，只包含大小写和数字
 * @return string
 */
function urandom_pwd(){
    $str = 'MNa32defghCGHIijklqrstuDEvw1xyzABJKQRSbcLOPTUVWXYZ0987mnop6F540';

    $secStr = '';
    $len = mb_strlen($str) - 1;
    for($i = 0; $i < 8; $i++){
        $pos = rand(0, $len);
        $secStr .= $str[$pos];
    }
    
    return $secStr;
}

/**
 * 校验显示行数
 * @param string $pages
 * @return number| boolean
 */
function check_page_rows($pages){
    if(in_array($pages, array('18', '30', '45', '60', '75'), true)){
        return (int)$pages;
    }else{
        return false;
    }
}

/**
 * 判断数字范围为1-65535
 * @param int $int
 * @return number
 */
function check_small_int($int){
    return preg_match("/(^[1-9]\d{0,3}$)|(^[1-5]\d{4}$)|(^6[0-4]\d{3}$)|(^65[0-4]\d{2}$)|(^655[0-2]\d$)|(^6553[0-5]$)$/", $int);
}

/**
 * checkPositiveInt
 * 判断是否正整数
 * @param int $int
 * @return number
 */
function check_positive_int($int){
    return preg_match("/^[1-9]\d{0,12}$/", $int);
}

/**
 * 判断数字范围为1-255
 * @param int $int
 * @return number
 */
function check_tiny_int($int){
    return preg_match("/^(25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|[1-9])$/", $int);
}

function check_sec_token($token){
    if(empty($_SESSION['secStr']) || empty($token)){
        unset($_SESSION['secStr']);
        return false;
    }else{
        if(strcmp($_SESSION['secStr'], $token) === 0){
            unset($_SESSION['secStr']);
            return true;
        }else{
            unset($_SESSION['secStr']);
            return false;
        }
    }
}

/**
 * checkCardID
 * 验证身份证号 15位纯数字或者18位最后一位可以为 数字, x, X
 * @param string $cardid
 * @return boolean
 */
function check_card_id($cardid){
    return preg_match("/(^\d{15}$)|(^\d{17}[xX\d]{1}$)/", $cardid);
}

/**
 * checkMobile
 * 校验手机号
 * @param string $mobile
 * @return boolean
 */
function check_mobile($mobile){
    return preg_match("/^1[34578]{1}[0-9]{9}$/", $mobile);
}

/**
 * 校验联系方式，可以是手机或者固话
 * @param string $concat
 * @return boolean
 */
function check_concat($concat){
    return preg_match("/^[0-9\-]{7,15}$/", $concat);
}

/**
 * 校验密码
 */
function check_passwd($pwd){
    if(empty($pwd) || strlen($pwd) < 6){
        return false;
    }

    return true;
}

/**
 * checkZhName
 * 校验中文名 2到15个字的中文
 * @param string $name
 * @return boolean
 */
function check_zh_name($name, $min = 1, $max = 15){
    $len = get_string_len($name);
    if($len >= $min && $len <= $max){
        return preg_match("/^[\x{4E00}-\x{9FFF}\.]+$/u", $name);
    }

    return false;
}

/**
 * stringFilter
 * 字符串校验函数 仅支持大小写，数字和中文，并且不超过60个字节
 * @param string $str  需要校验的字符串
 * @param int $bytelen 字符串最大字节数
 * @return boolean
 */
function check_string($str){
    return preg_match("/^[a-zA-Z0-9\x{4E00}-\x{9FFF}]+$/u", $str);
}

/**
 * checkText
 * 验证文本字符-只允许大小写，数字，中文，中文逗点，中文句号，英文逗点，英文句号
 * @param string $txt
 * @return boolean
 */
function check_text($txt){
    //\xa3\xa1-\xfe

    //return preg_match("/^[a-zA-Z0-9\x{4E00}-\x{9FFF},.:，。：\-（）!！]*$/u", $txt);
    return preg_match("/^[a-zA-Z0-9０-９\x{4E00}-\x{9FFF},.:，。：\-（）!！]*$/u", $txt);
    //return true;
}

/**
 * 校验区域编码的真实性
 * @param int $gridid
 * @return boolean
 */
function check_regionid($regionid){
    if(!check_positive_int($regionid)){
        return false;
    }
    
    //TODO

    return true;
}

/**
 * @todo 校验IP的真实性
 * @param string $ip
 * @return mixed
 */
function check_ip($ip){
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
}

/**
 * 校验日期
 * 格式：yyyy-mm-dd
 */
function check_date($date){
    //TODO
    return true;
}

/**
 * 校验日期时间
 * 格式：yyyy-mm-dd hh:ii:ss
 */
function check_datetime($datetime){
    //TODO
    return true;
}

/**
 * 校验性别
 */
function check_sex($sex){
    //TODO
    return true;
}
