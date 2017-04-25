DROP DATABASE IF EXISTS DBNAME;
CREATE DATABASE DBNAME;
USE DBNAME;

SET NAMES utf8;


DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `getChildLst`(`rootId` VARCHAR(50)) RETURNS varchar(10000) CHARSET utf8
BEGIN
DECLARE sTemp varchar(10000);
DECLARE sTempChd varchar(10000);

SET sTemp = '$';
SET sTempChd = rootId;

WHILE sTempChd is not null DO
SET sTemp = concat(sTemp,',',sTempChd);
SELECT group_concat(id) INTO sTempChd FROM prefix_tv_node where pid<>id and FIND_IN_SET(pid,sTempChd)>0;
END WHILE;

RETURN sTemp;
END ;;
DELIMITER ;


DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `getRegionChildLst`(`rootId` VARCHAR(50)) RETURNS varchar(10000) CHARSET utf8
BEGIN
DECLARE sTemp varchar(10000);
DECLARE sTempChd varchar(10000);

SET sTemp = '$';
SET sTempChd = rootId;

WHILE sTempChd is not null DO
SET sTemp = concat(sTemp,',',sTempChd);
SELECT group_concat(code) INTO sTempChd FROM prefix_region where pid<>code and FIND_IN_SET(pid,sTempChd)>0;
END WHILE;

RETURN sTemp;
END ;;
DELIMITER ;

DROP TABLE IF EXISTS `prefix_china_region`;
CREATE TABLE `prefix_china_region` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `province_id` bigint(20) unsigned NOT NULL COMMENT '省编码',
  `province_name` char(64) NOT NULL COMMENT '省名称',
  `city_id` bigint(20) unsigned NOT NULL COMMENT '市编码',
  `city_name` char(64) NOT NULL COMMENT '市名称',
  `county_id` bigint(20) unsigned NOT NULL COMMENT '县/区编码',
  `county_name` char(64) NOT NULL COMMENT '县/区名称',
  `town_id` bigint(20) unsigned NOT NULL COMMENT '镇/街编码',
  `town_name` char(64) NOT NULL COMMENT '镇/街名称',
  `village_id` bigint(20) unsigned NOT NULL COMMENT '村/社区编码',
  `village_name` char(64) NOT NULL COMMENT '村/社区名称',
  PRIMARY KEY (`id`),
  UNIQUE KEY `village_id` (`village_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='省市县镇村数据';


DROP TABLE IF EXISTS `prefix_content`;
CREATE TABLE `prefix_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `title` varchar(101) NOT NULL DEFAULT '' COMMENT '标题',
  `contents` text NOT NULL COMMENT '内容',
  `is_node` enum('y','n') NOT NULL DEFAULT 'y' COMMENT '是否是栏目内容，y是 n首页',
  `home_type` enum('marquee','pageLink','videoFrame','listContent','focusPic','') NOT NULL DEFAULT '' COMMENT '指向文件类型：字幕、外部链接、视频框、内容、焦点图、空值',
  `video_path` varchar(302) NOT NULL DEFAULT '' COMMENT '视频，仅支持一个视频',
  `tv_play_url` varchar(1002) NOT NULL DEFAULT '' COMMENT '电视端视频或者监控播放地址',
  `icon_path` varchar(302) NOT NULL DEFAULT '' COMMENT '图片存储路径集合，多个以逗号隔开',
  `type` enum('图文','监控','相册','有图征询','无图征询','答题','全屏视频','窗口视频','应急广播') NOT NULL DEFAULT '图文' COMMENT '内容样式',
  `node_id` smallint(5) unsigned NOT NULL default 0 COMMENT '所属栏目ID，对应prefix_node表ID',
  `region_id` varchar(13) NOT NULL DEFAULT '0' COMMENT '所属区域ID，对应region表的code',
  `status` enum('待审核','已审核','驳回','上线','下线') NOT NULL DEFAULT '待审核' COMMENT '文章状态',
  `path` varchar(1002) NOT NULL DEFAULT '' COMMENT '内容资源（图片）路径',
  `review_msg` varchar(1002) DEFAULT NULL COMMENT '驳回意见',
  `operate_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '录入人ID，对应user表id',
  `audit_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID，对应user表id',
  `release_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布人ID，对应user表id',
  `operate_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '录入时间',
  `audit_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '审核时间',
  `release_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '发布时间',
  `click_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击量',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文章排序',
  `start_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '活动开始时间',
  `end_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '活动结束时间',
  `join_times` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '用户可以参与次数',
  `con_type` set('tv','app','wechat') NOT NULL DEFAULT 'tv,app,wechat' COMMENT '内容展示终端',
  `user_src` enum('mobile','user') NOT NULL DEFAULT 'user' COMMENT 'operate_id的来源表',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='内容表';


DROP TABLE IF EXISTS `prefix_comment`;
CREATE TABLE `prefix_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `content_id` int(10) unsigned NOT NULL COMMENT '文章ID，对应prefix_content表ID',
  `author` varchar(101) NOT NULL COMMENT '评论人ID/评论终端机器号',
  `comment` text NOT NULL COMMENT '评论内容',
  `comm_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '评论时间',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '评论状态 0-待审核（默认） 1-审核通过',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='评论表';


DROP TABLE IF EXISTS `prefix_type_ext`;
CREATE TABLE `prefix_type_ext` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `text` varchar(51) NOT NULL DEFAULT '' COMMENT '类型名称',
  `desc` varchar(101) NOT NULL DEFAULT '' COMMENT '类型描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类型扩展表';


DROP TABLE IF EXISTS `prefix_type_param`;
CREATE TABLE `prefix_type_param` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `type_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
  `text` varchar(101) NOT NULL DEFAULT '' COMMENT '参数名称',
  `value` varchar(201) NOT NULL DEFAULT '' COMMENT '参数内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='类型参数表';


DROP TABLE IF EXISTS `prefix_camera_ext`;
CREATE TABLE `prefix_camera_ext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `content_id` int(10) unsigned NOT NULL COMMENT '文章ID，对应prefix_content表ID',
  `params` varchar(201) NOT NULL DEFAULT '' COMMENT '监控参数',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='监控扩展表';


DROP TABLE IF EXISTS `prefix_question`;
CREATE TABLE `prefix_question` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `content_id` int(10) unsigned NOT NULL COMMENT '文章ID，对应prefix_content表ID',
  `type` enum('单选','多选') NOT NULL DEFAULT '单选' COMMENT '题目类型',
  `ques_title` varchar(101) NOT NULL DEFAULT '' COMMENT '问题标题',
  `ques_content` varchar(301) NOT NULL DEFAULT '' COMMENT '问题描述',
  `ques_pic` varchar(101) NOT NULL DEFAULT '' COMMENT '图片',
  `standard_answer` varchar(21) NOT NULL DEFAULT '' COMMENT '问题标准答案',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='竞答题目表';


DROP TABLE IF EXISTS `prefix_question_option`;
CREATE TABLE `prefix_question_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `question_id` int(10) unsigned NOT NULL COMMENT '题目ID，对应prefix_question表ID',
  `option_name` char(1) NOT NULL DEFAULT '' COMMENT '选项名',
  `option_value` varchar(301) NOT NULL DEFAULT '' COMMENT '选项值',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='竞答选项表';


DROP TABLE IF EXISTS `prefix_question_record`;
CREATE TABLE `prefix_question_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '投票用户ID,对应mobile_user表ID,主要用于移动和h5端',
  `stb_id` varchar(51) NOT NULL DEFAULT '' COMMENT '答题人机顶盒ID',
  `ca_no` varchar(51) DEFAULT '' COMMENT '答题人CA卡号',
  `stb_ip` varchar(21) DEFAULT '' COMMENT '答题人机顶盒IP',
  `join_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '答题时间',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID，对应content表ID',
  `ques_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '题目ID, 对应question表ID',
  `quesopt_ids` varchar(11) NOT NULL COMMENT '题目答案,对应question_option表ID，多个ID以逗号隔开',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='答题记录表';


DROP TABLE IF EXISTS `prefix_answer_result`;
CREATE TABLE `prefix_answer_result` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '答题用户ID,对应mobile_user表ID，用于移动和h5端',
  `stb_id` varchar(51) NOT NULL DEFAULT '' COMMENT '答题人机顶盒ID，用户电视端',
  `ca_no` varchar(51) NOT NULL DEFAULT '' COMMENT '答题人CA卡号，用户电视端',
  `stb_ip` varchar(21) NOT NULL DEFAULT '' COMMENT '答题人机顶盒IP，用户电视端',
  `join_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '答题时间',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID，对应content表ID',
  `right_nums` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '正确题目数',
  `wrong_nums` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '错误题目数',
  `score` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '用户得分',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户答题结果记录表';


DROP TABLE IF EXISTS `prefix_consult`;
CREATE TABLE `prefix_consult` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `content_id` int(10) unsigned NOT NULL COMMENT '文章ID，对应prefix_content表ID',
  `c_title` varchar(101) NOT NULL DEFAULT '' COMMENT '征询标题',
  `c_pic` varchar(201) NOT NULL DEFAULT '' COMMENT '有图征询图片',
  `c_content` varchar(301) NOT NULL DEFAULT '' COMMENT '征询内容',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='有图征询表';


DROP TABLE IF EXISTS `prefix_consult_option`;
CREATE TABLE `prefix_consult_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID，对应prefix_content表ID',
  `consult_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '题目ID，对应prefix_consult表ID',
  `option_desc` varchar(301) NOT NULL DEFAULT '' COMMENT '选项描述',
  `vote_number` mediumint(9) NOT NULL DEFAULT '0' COMMENT '投票数',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='征询选项表';


DROP TABLE IF EXISTS `prefix_consult_record`;
CREATE TABLE `prefix_consult_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '投票用户ID,对应mobile_user表ID,主要用于移动和h5端',
  `stb_id` varchar(51) NOT NULL DEFAULT '' COMMENT '答题人机顶盒ID',
  `ca_no` varchar(51) NOT NULL DEFAULT '' COMMENT '答题人CA卡号',
  `ip` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '答题人机顶盒IP',
  `option_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '征询选项,对应prefix_option表ID',
  `join_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '投票时间',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID, 无图征询使用',
  `consult_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '征询项ID, 有图征询使用',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='征询记录表';


DROP TABLE IF EXISTS `prefix_goods`;
CREATE TABLE `prefix_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `content_id` int(10) unsigned NOT NULL COMMENT '文章ID，对应文章内容表ID',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品单价，默认2位小数',
  `unit` char(1) NOT NULL DEFAULT '' COMMENT '商品单位',
  `qrcode_url` varchar(201) NOT NULL DEFAULT '' COMMENT '商品支付二维码链接地址',
  `qrcode` varchar(201) NOT NULL DEFAULT '' COMMENT '商品支付二维码',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商品扩展表';


DROP TABLE IF EXISTS `prefix_tv_node`;
CREATE TABLE `prefix_tv_node` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'prefix_tv_node表ID',
  `text` varchar(101) NOT NULL DEFAULT '' COMMENT '栏目名称',
  `pid` smallint(5) unsigned NOT NULL COMMENT '父栏目ID',
  `node_code` SMALLINT(5) unsigned not null default 0 COMMENT '栏目编码',
  `region_id` varchar(13) NOT NULL COMMENT '所属区域ID，对应region表的code',
  `level` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '区域级别 0-省 1-市 2-区/县 3-乡镇街道 4-社区/行政村',
  `dir` varchar(101) NOT NULL DEFAULT '' COMMENT '节点文件目录',
  `url` varchar(51) NOT NULL DEFAULT '' COMMENT '叶节点程序处理路径',
  `icon_src` varchar(101) NOT NULL DEFAULT '' COMMENT '栏目图标',
  `icon_fcs` varchar(101) NOT NULL DEFAULT '' COMMENT '栏目获取焦点时的图标',
  `icon_blur` varchar(101) NOT NULL DEFAULT '' COMMENT '栏目失去焦点时的图标',
  `top` varchar(10) NOT NULL DEFAULT '0' COMMENT '顶点Y坐标',
  `left` varchar(10) NOT NULL DEFAULT '0' COMMENT '顶点X坐标',
  `width` varchar(10) NOT NULL DEFAULT '0' COMMENT '宽度',
  `height` varchar(10) NOT NULL DEFAULT '0' COMMENT '高度',
  `sort` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `click` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '栏目点击量',
  `type` enum('marquee','pageLink','videoFrame','listContent','column','focusPic','noDisplay','activeNode','activeRegion') NOT NULL COMMENT '指向文件类型：字幕、外部链接、视频框、内容、栏目、焦点图、栏目CMS不显示、动态栏目、动态区域',
  `is_leaf` enum('no','yes') NOT NULL DEFAULT 'no' COMMENT '是否为叶子节点',
  `node_type` set('tv','app','wechat') NOT NULL DEFAULT 'tv,app,wechat' COMMENT '栏目显示终端',
  `app_icon` varchar(101) NOT NULL DEFAULT '' COMMENT 'APP栏目图标',
  `tv_icon` varchar(101) NOT NULL DEFAULT '' COMMENT 'TV端栏目图标',
  `has_child` enum('no','yes') DEFAULT 'no',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='栏目节点表';


DROP TABLE IF EXISTS `prefix_tv_node_ext`;
CREATE TABLE `prefix_tv_node_ext` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `node_code` mediumint(8) unsigned NOT NULL COMMENT '栏目编码，关联tv_node表id',
  `is_fixed` enum('y','n') NOT NULL DEFAULT 'n' COMMENT 'APP 栏目是否固定y-固定 n-可编辑',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'APP 排序',
  `is_fav` enum('y','n') NOT NULL DEFAULT 'n' COMMENT 'APP 是否我的喜爱',
  `icons` varchar(101) NOT NULL DEFAULT '' COMMENT 'APP 栏目图标',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '栏目类型',
  PRIMARY KEY (`id`),
  UNIQUE KEY `node_code` (`node_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='node扩展表-移动端';


DROP TABLE IF EXISTS `prefix_node`;
CREATE TABLE `prefix_node` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `pid` smallint(5) NOT NULL DEFAULT '0' COMMENT '节点父ID',
  `name` varchar(51) NOT NULL COMMENT '节点名称',
  `method` varchar(51) NOT NULL DEFAULT '0' COMMENT '3级节点的方法',
  `class` varchar(51) NOT NULL DEFAULT '0' COMMENT '1级节点的控制器名称',
  `level` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '栏目层级',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='CMS后台栏目节点表';


DROP TABLE IF EXISTS `prefix_region`;
CREATE TABLE `prefix_region` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `text` varchar(101) NOT NULL COMMENT '区域名称',
  `pid` varchar(20) NOT NULL COMMENT '父区域ID',
  `code` varchar(20) NOT NULL COMMENT '区域码',
  `begin_ip` varchar(21) NOT NULL DEFAULT '0' COMMENT '区域起始IP的整数值',
  `end_ip` varchar(21) NOT NULL DEFAULT '0' COMMENT '区域末尾IP的整数值',
  `level` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '区域级别 0-省 1-市 2-区/县 3-乡镇街道 4-社区/行政村',
  `status` enum('上线','下线') NOT NULL DEFAULT '下线' COMMENT '项目状态',
  `temp_id` varchar(30) NOT NULL DEFAULT '0' COMMENT '模板ID',
  `logo_path` varchar(101) NOT NULL DEFAULT '' COMMENT 'logo路径',
  `pic` varchar(301) NOT NULL DEFAULT '',
  `region_desc` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='区域表';


DROP TABLE IF EXISTS `prefix_boss`;
CREATE TABLE `prefix_boss` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `name` varchar(101) NOT NULL DEFAULT '' COMMENT '用户姓名',
  `stb_id` varchar(51) NOT NULL DEFAULT '' COMMENT '机顶盒ID',
  `ca_no` varchar(51) NOT NULL DEFAULT '' COMMENT 'CA卡号',
  `stb_model` char(10) NOT NULL DEFAULT '' COMMENT '机顶盒型号',
  `region_id` varchar(13) NOT NULL DEFAULT '0' COMMENT '所属区域ID，对应region表的code',
  `address` varchar(101) NOT NULL DEFAULT '' COMMENT '地址',
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '用户电话',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '是否开通使用 0-未开通 1-开通',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='TV端用户信息表';


DROP TABLE IF EXISTS `prefix_mobile_user`;
CREATE TABLE `prefix_mobile_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `name` varchar(101) NOT NULL DEFAULT '' COMMENT '用户姓名',
  `email` varchar(101) NOT NULL DEFAULT '' COMMENT '系统用户账号1',
  `account` varchar(101) NOT NULL DEFAULT '' COMMENT '系统用户账号3',
  `nickname` varchar(101) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `bind_regionid` varchar(13) NOT NULL default 0 COMMENT '用户绑定的村社区ID',
  `bind_regionname` varchar(31) NOT NULL default '' COMMENT '用户绑定的村社区名称',
  `bind_status` tinyint unsigned NOT NULL default 0 COMMENT '用户绑定村社区状态 0-待审核 1-通过',
  `region_id` varchar(13) NOT NULL DEFAULT '0' COMMENT '所属区域ID，对应region表的code',
  `region_name` varchar(101) NOT NULL DEFAULT '' COMMENT '用户当前选择的区域地址',
  `nodes` varchar(201) NOT NULL DEFAULT '0' COMMENT '用户管理的栏目id多个以逗号隔开',
  `address` varchar(101) NOT NULL DEFAULT '' COMMENT '地址',
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '用户电话',
  `passwd` varchar(101) NOT NULL DEFAULT '' COMMENT '密码',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '是否开通使用 0-未开通 1-开通',
  `salt` varchar(101) NOT NULL DEFAULT '' COMMENT '盐值',
  `first_login` enum('y','n') NOT NULL DEFAULT 'y' COMMENT '是否为初次登陆',
  `login_source` enum('IOS','android') NOT NULL DEFAULT 'android' COMMENT '登陆来源',
  `fav_node` varchar(301) NOT NULL DEFAULT '' COMMENT '用户喜爱栏目ID（逗号隔开），顶级栏目',
  `rec_node` varchar(301) NOT NULL DEFAULT '' COMMENT '推荐栏目ID（逗号隔开），顶级栏目',
  `device_token` varchar(65) NOT NULL DEFAULT '' COMMENT '用户设备号，用于推送通知等',
  `serial_number` char(15) NOT NULL DEFAULT '' COMMENT '用户手机序列号',
  `err_time` datetime DEFAULT NULL COMMENT '最后一次错误时间, 成功之后置空 ',
  `err_times` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '错误次数, 3次之后锁定IP，10次之后锁定用户名，成功登陆之后置0',
  `lock_time` smallint(6) NOT NULL DEFAULT '5' COMMENT '错误之后锁定时间',
  `lock_status` enum('ip','un','no') NOT NULL DEFAULT 'no' COMMENT '锁定状态, ip-IP锁定 un-用户名锁定 no-未锁定',
  `block_status` enum('y','n') NOT NULL DEFAULT 'n' COMMENT '账号封禁状态 y-封禁 n-解封',
  `ip` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户最后一次登录的IP',
  `photo` varchar(101) NOT NULL DEFAULT '' COMMENT '用户头像存储地址',
  `birthday` date NOT NULL DEFAULT '1970-01-01' COMMENT '生日',
  `sex` enum('男','女') NOT NULL DEFAULT '男' COMMENT '性别',
  `user_type` enum('app','h5') NOT NULL DEFAULT 'app' COMMENT '用户类型',
  `use_type` enum('管理员0','管理员1','管理员2','管理员3','管理员4','普通管理员','普通用户') NOT NULL DEFAULT '普通用户' COMMENT '账户使用权限范围',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='移动终端用户信息表';


DROP TABLE IF EXISTS `prefix_user`;
CREATE TABLE `prefix_user` (
  `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT COMMENT 'prefix_user表ID',
  `email` varchar(101) NOT NULL DEFAULT '' COMMENT '系统用户账号1',
  `phone` varchar(101) NOT NULL DEFAULT '' COMMENT '系统用户账号2',
  `account` varchar(101) NOT NULL DEFAULT '' COMMENT '系统用户账号3',
  `real_name` varchar(101) NOT NULL DEFAULT '' COMMENT '用户姓名',
  `password` varchar(101) NOT NULL DEFAULT '' COMMENT '密码',
  `type` enum('系统总监','操作员','内容审核','内容发布','') NOT NULL DEFAULT '' COMMENT '账号类型',
  `region_id` varchar(13) NOT NULL DEFAULT '0' COMMENT '所属区域ID，对应region表的code',
  `code` varchar(13) NOT NULL DEFAULT '0' COMMENT '板块用户所属区域ID，对应region表的code',
  `nodes` varchar(101) NOT NULL DEFAULT '0' COMMENT '所属栏目ID，对应prefix_node表的id，多个时以逗号隔开',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '是否开通使用 0-未开通 1-开通',
  `salt` varchar(101) NOT NULL DEFAULT '' COMMENT '盐值',
  `first_login` enum('y','n') NOT NULL DEFAULT 'y' COMMENT '是否为初次登陆',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员用户信息表';


DROP TABLE IF EXISTS `prefix_template`;
CREATE TABLE `prefix_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `text` varchar(100) NOT NULL COMMENT '模板名称',
  `class` enum('通用模板','个性模板','未分类') NOT NULL COMMENT '模板类别',
  `path` varchar(100) NOT NULL COMMENT '模板路径',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='模板管理';


DROP TABLE IF EXISTS `prefix_active_node`;
CREATE TABLE `prefix_active_node` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `title` varchar(101) NOT NULL DEFAULT '' COMMENT '活动栏目名称，如农场名等',
  `desc` varchar(1002) NOT NULL DEFAULT '' COMMENT '活动栏目简介',
  `pic` varchar(301) NOT NULL DEFAULT '' COMMENT '活动栏目图片',
  `content_ids` varchar(1002) NOT NULL DEFAULT '' COMMENT 'content id，多个时以逗号隔开对应content表的ID',
  `node_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '栏目id，关联tv_node表id',
  `region_id` varchar(13) NOT NULL DEFAULT '0' COMMENT '所属区域ID，对应region表的code',
  `display` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否展示，1-展示 0-不展示，默认为1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='活动栏目表';


DROP TABLE IF EXISTS `prefix_logs`;
CREATE TABLE `prefix_logs` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志编号',
  `man_id` smallint(5) unsigned NOT NULL COMMENT '操作人员ID, 见user表的man_id字段',
  `log_desc` text NOT NULL COMMENT '描述',
  `oper_type` enum('add','del','sel','update') NOT NULL COMMENT '操作类型',
  `oper_tablename` char(30) NOT NULL COMMENT '操作的表名',
  `oper_ret` enum('true','false') NOT NULL DEFAULT 'true' COMMENT '操作结果',
  `log_ip` int(11) DEFAULT NULL COMMENT '操作人员的IP',
  `log_src` enum('pc','android','ios','h5') NOT NULL DEFAULT 'pc' COMMENT '操作人员的客户端类型',
  `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='操作日志记录表';


DROP TABLE IF EXISTS `prefix_app`;
CREATE TABLE `prefix_app` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `app_name` varchar(30) NOT NULL COMMENT '包名称',
  `app_desc` varchar(201) NOT NULL COMMENT '包描述',
  `user_id` tinyint(4) unsigned NOT NULL COMMENT '上传者ID,见user表ID',
  `app_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='包名表';


DROP TABLE IF EXISTS `prefix_apk`;
CREATE TABLE `prefix_apk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `app_id` tinyint(3) unsigned NOT NULL COMMENT '包ID，见apkname表id',
  `apk_packname` varchar(51) NOT NULL DEFAULT '' COMMENT '应用包名称',
  `apk_name` varchar(51) NOT NULL DEFAULT '' COMMENT '应用名称',
  `apk_vername` varchar(31) NOT NULL DEFAULT '' COMMENT '应用版本名称',
  `apk_version` varchar(31) NOT NULL DEFAULT '0' COMMENT '应用版本号',
  `apk_path` varchar(51) NOT NULL DEFAULT '' COMMENT '应用上传保存路径',
  `apk_size` float(5,2) NOT NULL DEFAULT '0' COMMENT '应用包大小',
  `apk_desc` varchar(502) NOT NULL DEFAULT '' COMMENT '应用详细描述',
  `apk_icon` varchar(51) NOT NULL DEFAULT '' COMMENT '应用图标',
  `shot_icons` varchar(201) NOT NULL DEFAULT '' COMMENT '应用截图描述，限制5张',
  `keywords` varchar(101) NOT NULL DEFAULT '' COMMENT '应用关键词描述',
  `downloads` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '下载量',
  `download_url` varchar(101) NOT NULL DEFAULT '' COMMENT '外部下载地址',
  `like_nums` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数量',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='包名表';

DROP TABLE IF EXISTS `prefix_node_permission`;
CREATE TABLE `prefix_node_permission` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '表ID',
  `node_code` varchar(1002) NOT NULL DEFAULT '' COMMENT '栏目code，关联tv_node表node_code，多个以逗号隔开',
  `region_id` varchar(13) NOT NULL DEFAULT '0' COMMENT '所属区域ID，对应region表的code',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='APP栏目权限表';
