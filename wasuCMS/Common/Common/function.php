<?php
	/*
	 * 递归生成树
	 */
	function recursiveTree($tree, $rootId = 0) {  
		$return = array();  
		foreach($tree as $leaf) {  
			if( $leaf['attributes']['pid'] == $rootId ) { 		
				foreach( $tree as $subleaf ) {  
					if( $subleaf['attributes']['pid'] == $leaf['id'] ) {  
						$leaf['children'] = recursiveTree( $tree, $leaf['id'] );  
						break;  
					}  
				}  
				$return[] = $leaf;  
			}  
		}  
		return $return;  
	}  

	/*
		批量修改文件目录权限
	*/

	function recurDir( $dir , $chmod='' ) {
		if( is_dir( $dir ) ) {
			if( $handle = opendir( $dir ) ) {
				while( false !== ( $file = readdir( $handle ) ) ) {
					if(is_dir($dir.'/'.$file)) {
						if($file != '.' && $file != '..') {
							$path = $dir.'/'.$file;
							$chmod ? chmod($path,$chmod) : FALSE;
							recurDir($path);
						}
					}else{
						$path = $dir.'/'.$file;
						$chmod ? chmod($path,$chmod) : FALSE;
					}
				}
			}
			closedir($handle);
		}
	}

	function funUploadJson( $_url , $_path , $_file){
		$url = $_url;
		$php_path = dirname(__FILE__) . '/'; //获取当前文件物理绝对路径
		$save_path = $php_path . $_path;
		$save_url = $url;
		$save_path = realpath($save_path) . '/';
		
		$file_name = $_file['name'];
		$tmp_name = $_file['tmp_name'];
		$file_size = $_file['imgFile']['size'];
		
		$temp_arr = explode(".", $file_name);//获取文件名后缀
		$file_ext = array_pop($temp_arr);
		$file_ext = trim($file_ext);
		$file_ext = strtolower($file_ext);
		
		$ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
		if ( in_array( $file_ext , $ext_arr ) === false ){
			alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$dir_name]) . "格式。");
		}
		
		$ymd = date("Ymd");
		$save_path .= $ymd . "/";
		$save_url .= $ymd . "/";
		if (!file_exists($save_path)) {
			mkdir($save_path);
			@chmod( $save_path , 0777 );
		}
		$new_file_name = md5( date("YmdHis") . '_' . rand(10000, 99999) ) . '.' . $file_ext;
		$file_path = $save_path . $new_file_name;

		if (move_uploaded_file($tmp_name, $file_path) === false) {
			alert("上传文件失败");
		}
		@chmod($file_path, 0777);
		$file_url = $save_url . $new_file_name;
		echo json_encode(array('error' => 0, 'url' => $file_url));
	}

	function alert($msg) {
		header('Content-type: text/html; charset=UTF-8');
		echo json_encode(array('error' => 1, 'message' => $msg));
		exit;
	}

	function createMsg( $msg , $error , $title = '系统提示' ) {
		header('Content-type: text/html; charset=UTF-8');
		echo json_encode(array('error' => $error, 'message' => $msg , 'title' => $title ) );	
	}

	//移除字符串单引号双引号
	function remove_quote( &$str ) {
		if(preg_match( "/^\'/" , $str ) ){
			$str = substr( $str , 1 , strlen( $str )-1);
		}
		if(preg_match( "/\'$/" , $str ) ){
			$str = substr( $str , 0 , strlen( $str )-1);
		}
		return $str;
	}

	//ZIP解压缩
	function onlineUnzip( $_file_name , $_path ){
		if( !file_exists( $_file_name ) ){
			return false;
		}
		$resource = zip_open( $_file_name );
		$i = 0;
		while ( $dir_resource = zip_read( $resource ) ) {
			if ( zip_entry_open( $resource , $dir_resource ) ){
				if( $i == 0 ){
					$root_file = str_replace( '/' , '' , zip_entry_name( $dir_resource ) );
					$file_arr = scandir( $_path );
					foreach( $file_arr as $key => $value ){
						if( $value == $root_file ){
							zip_close( $resource );
							unlink( $_file_name );
							return false;
						}
					}
				}
				$file_name = $_path . zip_entry_name( $dir_resource );
				$file_path = substr( $file_name , 0 , strrpos( $file_name ,  "/" ) );
				if( !is_dir( $file_path ) ){
					mkdir( $file_path , 0777 , true );
				}
				if( !is_dir( $file_name ) ){
					$file_size = zip_entry_filesize( $dir_resource );
					if($file_size < ( 1024*1024*20 ) ){
						$file_content = zip_entry_read( $dir_resource , $file_size );
						file_put_contents( $file_name , $file_content );
					}
				}
				zip_entry_close( $dir_resource );
				$i++;
			}
		}
		zip_close( $resource ); 
		unlink( $_file_name );
		return $root_file;
	}

	//清除全角回车制表符
	function trimall($str){
		$qian=array("　","\t","\n","\r","&nbsp;","&emsp;"," ");
		$hou=array("","","","","","","");
		return str_replace($qian,$hou,$str); 
	}
	
	//清除全角回车制表符
	function trimalls($str){
		$qian=array("　","\t","\n","\r","&nbsp;","&emsp;");
		$hou=array("","","","","","");
		return str_replace($qian,$hou,$str); 
	}
	
	//按宽度等比例缩放图片
	function changeImg( $_img_path , $_width ){
		$a = getimagesize($_img_path);
		$type = $a['mime'];
		switch($type){
			case 'image/jpeg' :
			$src_img=imagecreatefromjpeg($_img_path);
			break;
			case 'image/png' :
			$src_img=imagecreatefrompng($_img_path);
			break;
			case 'image/gif' :
			$src_img=imagecreatefromgif($_img_path);
			break;
		}
		$ws=imagesx($src_img);
		$hs=imagesy($src_img);
		$w=(int)$ws;
		$h=(int)$hs;
		$_width=(int)$_width;
		if($w>$_width){
			$h=$h*($_width/$w);
			$h=(int)$h;
			$image=imagecreatetruecolor($_width, $h);
			imagecopyresampled($image, $src_img, 0, 0, 0, 0, $_width, $h, $ws, $hs);
			switch($type) {
				case 'image/jpeg' :
				imagejpeg($image,$_img_path,100); // 存储图像
				break;
				case 'image/png' :
				imagepng($image,$_img_path,9);
				break;
				case 'image/gif' :
				imagegif($image,$_img_path,100);
				break;
				default:
				break;
			}		
		}
	}
	
	function cutphoto_1( $o_photo , $width , $height ){ 
		$a = getimagesize($o_photo);
		$type = $a['mime'];
		switch($type){
			case 'image/jpeg' :
			$temp_img = imagecreatefromjpeg($o_photo);
			$ext = '.jpg';
			break;
			case 'image/png' :
			$temp_img = imagecreatefrompng($o_photo);
			$ext = '.png';
			break;
			case 'image/gif' :
			$temp_img = imagecreatefromgif($o_photo);
			$ext = '.gif';
			break;
		}
		$o_width  = imagesx($temp_img);                                 
		$o_height = imagesy($temp_img); 
		$pic_name = md5( date("YmdHis") . rand(10000, 99999) ) . $ext;
		$d_photo = '..' . UPLOAD_PATH . 'image/' . date("Ymd") . '/' . $pic_name;
		$return_pic = UPLOAD_PATH . 'image/' . date("Ymd") . '/' . $pic_name;
		//判断处理方法 
		if($width > $o_width || $height > $o_height ){//原图宽或高比规定的尺寸小,进行压缩 
			$newwidth = $o_width; 
			$newheight = $o_height;
			
			if($o_width>$width){ 
				$newwidth=$width; 
				$newheight=$o_height*$width/$o_width; 
			} 
			if($newheight>$height){ 
				$newwidth=$newwidth*$height/$newheight; 
				$newheight=$height; 
			} 
			//缩略图片 
			$new_img = imagecreatetruecolor($newwidth, $newheight);  
			imagecopyresampled($new_img, $temp_img, 0, 0, 0, 0, $newwidth, $newheight, $o_width, $o_height);
			if($ext=='.jpg')			
			$bool = imagejpeg($new_img , $d_photo); 
			if($ext=='.png')			
			$bool = imagepng($new_img , $d_photo); 
			if($ext=='.gif')			
			$bool = imagegif($new_img , $d_photo); 		
			imagedestroy($new_img); 
			
	   }else{//原图宽与高都比规定尺寸大,进行压缩后裁剪                                                                             
			if($o_height*$width/$o_width>$height){//先确定width与规定相同,如果height比规定大,则ok 
				$newwidth=$width; 
				$newheight=$o_height*$width/$o_width; 
				$x=0; 
				$y=($newheight-$height)/2; 
			}else{//否则确定height与规定相同,width自适应 
				$newwidth=$o_width*$height/$o_height; 
				$newheight=$height; 
				$x=($newwidth-$width)/2; 
				$y=0; 
			} 
			//缩略图片 
			$new_img = imagecreatetruecolor($newwidth, $newheight);  
			imagecopyresampled($new_img, $temp_img, 0, 0, 0, 0, $newwidth, $newheight, $o_width, $o_height);  
			if($ext=='.jpg'){
				imagejpeg($new_img , $d_photo);
				$temp_img = imagecreatefromjpeg($d_photo);				
			}			
			 
			if($ext=='.png'){
				imagepng($new_img , $d_photo); 
				$temp_img = imagecreatefrompng($d_photo);	
			}			
			
			if($ext=='.gif'){
				imagegif($new_img , $d_photo); 
				$temp_img = imagecreatefromgif($d_photo);
			}						                
			imagedestroy($new_img); 
			$o_width  = imagesx($temp_img);                                //取得缩略图宽 
			$o_height = imagesy($temp_img);                                //取得缩略图高 

			//裁剪图片 
			$new_imgx = imagecreatetruecolor($width,$height); 
			imagecopyresampled($new_imgx,$temp_img,0,0,$x,$y,$width,$height,$width,$height); 		
			if($ext=='.jpg')			
			$bool = imagejpeg($new_imgx , $d_photo); 
			if($ext=='.png')			
			$bool = imagepng($new_imgx , $d_photo); 
			if($ext=='.gif')			
			$bool = imagegif($new_imgx , $d_photo); 		 			
			imagedestroy($new_imgx); 
	   }
	   
		if( $bool ){
			return $return_pic;
		}else{
			return false;
		}
	} 
	
	// 获取开始指定字符串到结束字符串
	function getStr($str,$start_str,$end_str,$_flag){ 
		if(strstr($str,$start_str) && strstr($str,$end_str)){
			if($_flag==1){
				$start_pos=strpos($str,$start_str)+strlen($start_str);
				$end_pos = strpos($str,$end_str);
				$c_str_l = $end_pos - $start_pos;		
			}else if($_flag==2){
				$start_pos=strpos($str,$start_str);
				$end_pos = strpos($str,$end_str)+strlen($end_str);
				$c_str_l = $end_pos - $start_pos;		
			}

			$contents = substr($str,$start_pos,$c_str_l); 
			return $contents;		
		}else{
			return false;
		}
	}
	
	//过滤时默认保留html中的指定标签
	function cleanhtml($str,$tags='<img><a>'){//过滤时默认保留html中的<a><img>标签  
		$search = array(  
			'@<script[^>]*?>.*?</script>@si',  // Strip out javascript  
			'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly   
			'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA   
		);   
		$str = preg_replace($search, '', $str);  
		$str = strip_tags($str,$tags);  
		return $str;  
	}
	
	function clearTNR($str){
		$qian=array("\t","\n","\r","&nbsp;","&emsp;");
		$hou=array("","","","","");
		return str_replace($qian,$hou,$str); 	
	}

	//清除TABLE标签内的<BR>
	function clearTableBr($_str,$tag,$tag1){
		$s = $_str;
		$newstr = getStr( $s , $tag , $tag1 , 1 );
		if($newstr){
			$newstr = str_replace('<br />','', $newstr);
			$newstr = "##".$newstr."@@";
			$oldstr = getStr($s,$tag,$tag1,2);
			$s = str_replace($oldstr,$newstr,$s);
			return clearTableBr($s,$tag,$tag1);
		}else{
			return $s;
		}
	}
	
	//下载图片
	function loadImgAndChange( $_src , $_size ){
		$a = getimagesize( $_src );
		$type = $a['mime'];
		switch($type){
			case 'image/jpeg' :
			$ext = '.jpg';
			break;
			case 'image/png' :
			$ext = '.png';
			break;
			case 'image/gif' :
			$ext = '.gif';
			break;
		}
		$pic_file = file_get_contents( $_src );
		$path = '..' . UPLOAD_PATH .'image/';
		if( !is_dir( $path ) ){
			mkdir( $path );
			@chmod( $path , 0777 );
		}
		$path.= date("Ymd") . '/';
		if( !is_dir( $path ) ){
			mkdir( $path );
			@chmod( $path , 0777 );
		}
		$file_name = md5( date("Ymd").time().rand( 10000 , 99999 ) ) . $ext;
		$pic_file_path = $path . $file_name;
		$returnPath = UPLOAD_PATH . 'image/' . date("Ymd") . '/' . $file_name;
		if(file_put_contents($pic_file_path,$pic_file)){
			changeImg( $pic_file_path , $_size );//按宽度处理图片
			return $returnPath;
		}else{
			return false;
		}
	}
	
	//富文本编辑器中内容格式化	
	function contentEditor( $_con_content_re , $_size ){
		$icon = 0;
		$con_content = htmlspecialchars_decode( $_con_content_re );//HTML转义反函数
		$tags="<img><table><tr><td><p><P><br>"; // 需要保留的HTML标签
		$con_content = cleanhtml( $con_content , $tags ); // 清除不保留的HTML标签
		$con_content = clearTNR( $con_content );// 清除制表符回车符空格
		$con_content = preg_replace( '@<table[^>]*?>@si' , '<table>' , $con_content );//如果TABLE标签内含其它属性，则替换成<table>标签
		$reg = "/((style|valign|align|width|height|bgcolor|class|id)=[^\"\'\s>]*)[^>]*/"; //HTML属性标签
		$con_content = preg_replace($reg, '', $con_content); //清除所有HTML属性标签
		$con_content = trimalls( $con_content ); //清除全角回车制表符
		
	
		if( strstr( $con_content , "<table>" ) ){ //如果内容里有TABLE标签，则要清除标签内的BR及其它标签，并格式化表格
			$con_content = clearTableBr( $con_content , "<table>" , "</table>" );
			$con_content = str_replace("##","<table width='100%' border='1' cellspacing='0' cellpadding='0'>",$con_content);
			$con_content = str_replace("@@","</table>",$con_content);
		}
		$pic_arr = array();
		//如果内容包含图片，如果是网络图片则需要下载本地进行相关处理，并把第一幅图生成条目缩略图
		if( strstr( $con_content , "<img" ) ){
			preg_match_all( '/<img.*\/>/iUs' , $con_content , $out ); 
			//全局正则表达式匹配出现IMG标签内的字符，并把结果赋给数组$out
			$out_arr = $out[0];
			foreach( $out_arr as $key => $value ){
				$src = getStr( $value , '<img src="' , '" alt="" />' , 1 );//获取IMG标签内SRC指向的图片路径
				$src = trim( $src );
				if( strstr( $src , 'http://' ) ){ //如果图片是网络地址图片，则需要下载至本地
					if( $pic_file_path = loadImgAndChange( $src , $_size ) ){
						if( $key == 0 ){
							$p = cutphoto_1( '..'.$pic_file_path , 330  ,160 );
							if( !empty( $p ) ){
								$icon = $p;
							}
						}
						$reg = '<p><center><img src="' . $pic_file_path . '" alt="" /></center></p>';
						$pic_arr[] = $pic_file_path;
					}
				}else{
					$reg = '<p><center><img src="' . $src . '" alt="" /></center></p>';
					$pic_arr[] = $src;
					$srcs = '..' . $src;
					changeImg( $srcs , $_size );
					if( $key == 0 ){
						$p = cutphoto_1( $srcs , 330  ,160 );
						if( !empty( $p ) ){
							$icon = $p;
						}
					}
				}
				$con_content = str_replace( $value , $reg , $con_content );
			}
		}
		$con_content=str_replace("</p>","&",$con_content);
		$con_content=str_replace("</P>","&",$con_content);
		$con_content=str_replace("<p>","&",$con_content);
		$con_content=str_replace("<br />","&",$con_content);
		$con_content=str_replace("</p>","&",$con_content);
		$con_content=str_replace("<p >","&",$con_content);
		$con_content=str_replace("<br>","&",$con_content);
		$con_arr=explode("&",$con_content);
		$con_arr_count = count( $con_arr );
		for( $i=0 ; $i < $con_arr_count ; $i++ ){
			if( $con_arr[$i] == "" || $con_arr[$i] == "　"){
				unset( $con_arr[$i] ); 
			}
		}
		$con_arr = array_values( $con_arr );
		$con_arr_count = count( $con_arr );
		$len = '';
		for( $i = 0 ; $i < $con_arr_count ; $i++ ){
			if( !strstr($con_arr[$i],"<img") ){
				$con_arr[$i]="　　".$con_arr[$i];
			}
			$len .= $con_arr[$i]."<br>";
		}
		$return_arr['content'] = $len;
		$return_arr['icon'] = $icon;
		$return_arr['pic'] = $pic_arr;
		return $return_arr;
	}
    
    if( ! function_exists('array_column'))
{
  function array_column($input, $columnKey, $indexKey = NULL)
  {
    $columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
    $indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
    $indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
    $result = array();
 
    foreach ((array)$input AS $key => $row)
    { 
      if ($columnKeyIsNumber)
      {
        $tmp = array_slice($row, $columnKey, 1);
        $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : NULL;
      }
      else
      {
        $tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
      }
      if ( ! $indexKeyIsNull)
      {
        if ($indexKeyIsNumber)
        {
          $key = array_slice($row, $indexKey, 1);
          $key = (is_array($key) && ! empty($key)) ? current($key) : NULL;
          $key = is_null($key) ? 0 : $key;
        }
        else
        {
          $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
        }
      }
 
      $result[$key] = $tmp;
    }
 
    return $result;
  }
}
