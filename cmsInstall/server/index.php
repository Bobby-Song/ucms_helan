<?php
	header("content-type:text/html;charset=utf-8");	
	$data = $_POST;
	$arr = array();
	$arr['status']='系统提示';
	if ( $data['DB_PORT'] == '' )
	$conn = mysqli_connect( $data['DB_HOST'] , $data['DB_USER'] , $data['DB_PWD'] );
	else 
	$conn = mysqli_connect( $data['DB_HOST'] . ':' . $data['DB_PORT'] , $data['DB_USER'] , $data['DB_PWD'] );
	mysqli_query($conn , "set names UTF8");
	if( empty( $conn ) ){			
		$arr['message'] = '数据库连接错误!';
		echo json_encode( $arr );
		return;
	}else{
		$db_name = $data['DB_NAME'];
		$state = mysqli_select_db($conn , $db_name );
		
		if( $state ) mysqli_query($conn , "DROP DATABASE $db_name");//如果有同名数据库，删除之
		$result = mysqli_query($conn , "CREATE DATABASE $db_name"); 
		if( empty( $result ) ){
			$arr['message'] = "创建数据库".$db_name."错误!";
			echo json_encode( $arr );
			return;
		}else{
			mysqli_select_db( $conn , $db_name );
			$sql = file_get_contents( '../sql/cms.sql' );
			$sql = str_replace( 'prefix_' , $data['DB_PREFIX'] , $sql );
			$data_arr = explode( ';' , $sql );
			array_pop( $data_arr ); 
			foreach( $data_arr as $b ){ 
				$sql = $b. ";"; 
				$status = mysqli_query( $conn , $sql );
				if( empty( $status ) ){
					$arr['message'] = "创建数据库" . $data['DB_NAME'] . "错误!".$conn->error;
					echo json_encode( $arr );
					return;
				}
			}
            /* $sql1 = file_get_contents( '../sql/function.sql' );
			$sql1 = str_replace( 'prefix_' , $data['DB_PREFIX'] , $sql1 );
            $status1 = mysqli_query( $conn , $sql1 );
            if( empty( $status1 ) ){
                $arr['message'] = "创建数据库" . $data['DB_NAME'] . "错误!".$conn->error;
                echo json_encode( $arr );
                return;
            } */
            
			//创建配置文件		
			$config_content = "<?php\n" ;
			$config_content.= "return array(\n" ;
			$config_content.= "\t'MODULE_ALLOW_LIST' => array('Admin','Systems'),\n";
			$config_content.= "\t'DEFAULT_MODULE' => 'Admin',\n";
			$config_content.= "\t'LANG_AUTO_DETECT' => FALSE,\n";
			$config_content.= "\t'LANG_SWITCH_ON' => TRUE,\n";
			$config_content.= "\t'DEFAULT_LANG' => 'zh-cn',\n";
			$config_content.= "\t'DB_TYPE' => 'mysql',\n";
			$config_content.= "\t'DB_HOST' => '".$data['DB_HOST']."',\n";
			$config_content.= "\t'DB_NAME' => '".$data['DB_NAME']."',\n";
			$config_content.= "\t'DB_USER' => '".$data['DB_USER']."',\n";
			$config_content.= "\t'DB_PWD' => '".$data['DB_PWD']."',\n";
			$config_content.= "\t'DB_PORT' => '".$data['DB_PORT']."',\n";
			$config_content.= "\t'DB_PREFIX' => '".$data['DB_PREFIX']."',\n";				
			$config_content.= "\t'DB_FIELDS_CACHE' => FALSE,\n";
            $config_content.= "'LOAD_EXT_FILE' => 'functionXyg',\n";
            $config_content.= "'LOAD_EXT_CONFIG' => 'errCode',\n";
            $config_content.= "\n";
            $config_content.= "/**---------------- 以下配置为CMS内部自用  -----------------*/\n";
            $config_content.= "'REGION_NAME' => '银川市',\n";
            $config_content.= "'SERVER_IP' => '".$_POST['PORTAL_HOST_IP']."',\n";
            $config_content.= "'SERVER_PORT' => '".$_POST['PORTAL_HOST_PORT']."',\n";
			$config_content.= ");\n";
            
			$config_file_path = '../../wasuCMS/Common/Conf/config.php';
			if( file_put_contents( $config_file_path , $config_content ) ){
				$web_root = '../../webRoot';
				if( !is_dir( $web_root ) ){
					mkdir( $web_root );
					@chmod( $web_root , 0777 );
				}
				$video = '../../video';
				if( !is_dir( $video ) ){
					mkdir( $video );
					@chmod( $video , 0777 );
				}
				$upload = $web_root.'/upload';
				if( !is_dir( $upload ) ){
					mkdir( $upload );
					@chmod( $upload , 0777 );
				}
				$template = $web_root.'/template';
				if( !is_dir( $template ) ){
					mkdir( $template );
					@chmod( $template , 0777 );
				}
				$config_content = "<?php\n";
				$config_content.= "header('content-type:text/html;charset=utf-8');\n";
				$config_content.= "if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');\n";
                $config_content.= "define('APP_DEBUG' , true);\n";
				$config_content.= "define('SITE_PATH' , '/wasuCMS/');\n";
				$config_content.= "define('WEB_PATH' , '/webRoot/');\n";
				$config_content.= "define('CSS_PATH' , '/wasuCMS/public/css/');\n";
				$config_content.= "define('JS_PATH' , '/wasuCMS/public/javascript/');\n";
				$config_content.= "define('IMAGES_PATH' , '/wasuCMS/public/images/');\n";
				$config_content.= "define('CF_PATH' , '/wasuCMS/public/clientFramework/');\n";
				$config_content.= "define('HIGHCHARTS_PATH' , '/wasuCMS/public/highcharts/');\n";
				$config_content.= "define('EDITOR_URL' , '/wasuCMS/public/editor/');\n";
				
				$config_content.= "define('JSON_CACHE_PATH' , '/wasuCMS/public/json/');\n";
				$config_content.= "define('VIDEO_PATH' , '/video/');\n";
				$config_content.= "define('UPLOAD_PATH' , WEB_PATH . 'upload/');\n";
				$config_content.= "define('TEMPLATE_PATH' , WEB_PATH . 'template/');\n";
				$config_content.= "require '../ThinkPHP/ThinkPHP.php';";
				$config_file_path = '../../wasuCMS/index.php';
				if( file_put_contents( $config_file_path , $config_content ) ){
					$user_table_name = $data['DB_PREFIX'].'user';
					$user_name = $data['SYSTEM_DIRECTOR'];
					$user_pass = $data['SYSTEM_DIRECTOR_PWD'];
					$user_pass = md5( $user_pass );
					$user_type = '系统总监';
					$sql = "insert into $user_table_name (account,password,type) values('$user_name','$user_pass','$user_type')";
					$status = mysqli_query( $conn , $sql );
					if( empty( $status ) ){
						$arr['message'] = "创建系统总监账户失败!";
						echo json_encode( $arr );
						return;						
					}else{
						//插入区域数据
						$region_json = file_get_contents( '../json/admin_region_data.json' );
						$region_arr = json_decode( $region_json , true );
						$region_arr_count = count( $region_arr );
						$table = $data['DB_PREFIX'].'region';
						for( $i = 0 ; $i < $region_arr_count ; $i++ ){
							$text = $region_arr[$i]['text'];
							$pid = $region_arr[$i]['pid'];
							$code = $region_arr[$i]['code'];
							$level = $region_arr[$i]['level'];
							$sql = "insert into $table (text,pid,code,level) values('$text','$pid','$code','$level')";
							mysqli_query( $conn , $sql );
						}
						
						$arr['message'] = "系统安装成功!请牢记系统总监密码，3秒后进入管理界面";
						$arr['prompt'] = 1;
						echo json_encode( $arr );						
					}
				}else{
					$arr['message'] = "创建配置文件错误!原因：无文件操作权限，请更改文件权限";
					echo json_encode( $arr );
					return;					
				}
			}else{
				$arr['message'] = "创建配置文件错误!原因：无文件操作权限，请更改文件权限";
				echo json_encode( $arr );
				return;
			}
		}
	}