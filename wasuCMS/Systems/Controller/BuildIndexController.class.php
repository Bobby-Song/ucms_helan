<?php
namespace Systems\Controller;

use Think\Controller;

class BuildIndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $user_id = session('user_id');
        $type = session('type');
        if (empty($user_id) || $type == '系统总监') {
            $login = A('Admin/Login');
            $login->logOut();
            return;
        }
    }


    /**
     * @param $_id
     * @return int|void
     */
    public function buildIndexJson($_id)
    {
        //$this -> sessionState();
        $content_model = M('content');
        $region_model = M('region');
        $tv_node = M('tv_node');
        $active = M('active_node');

        $con_arr = $content_model->where("id='" . $_id . "'")->find();
        $where = "code='" . $con_arr['region_id'] . "'";
        $reg_arr = $region_model->where($where)->field(" id , text , code , level ")->find();

        $reg_id = session('region_id');

        if ($con_arr['is_node'] == 'y') { // 如果为栏目
            $node_type = $tv_node->where(['node_code' => $con_arr['node_id']])->getField('type');

            if ($node_type == 'activeNode') {
                $list_arr = $active->where(['region_id' => $reg_arr['code'], 'node_id' => $con_arr['node_id']])->order('id desc')->field(['id', 'pic', 'title'])->select();
                if (!empty($list_arr)) {
                    foreach ($list_arr as $a_key => $a_val) {
                        $list_arr[$a_key]['icon_path'] = rtrim($a_val['pic'], '!!');
                        unset($list_arr[$a_key]['pic']);
                        $list_arr[$a_key]['js_path'] = WEB_PATH . $reg_id . '/public/active_data/' . $reg_arr['code'] . '/' . $reg_arr['code'] . '_' . $a_val['id'] . '.js';
                    }
                }
            } else {
                $where = "region_id='" . $con_arr['region_id'] . "' and node_id='" . $con_arr['node_id'] . "' and status='上线'";
                $where .= " and is_node='y' and user_src = 'user'";
                $field = "id,title,icon_path,video_path,type,node_id,region_id,tv_play_url";
                $list_arr = $content_model->where($where)->field($field)->order('sort desc')->select();
                $list_arr_count = count($list_arr);
                for ($i = 0; $i < $list_arr_count; $i++) {
                    $type = $list_arr[$i]['type'];
                    if (in_array($type, ['图文', '相册', '窗口视频'])) {
                        $js_path = WEB_PATH . $reg_id . '/data/';
                        $js_path .= $reg_arr['code'] . '/' . md5($list_arr[$i]['id']) . '.js';
                        $list_arr[$i]['js_path'] = $js_path;
                    } else {
                        $list_arr[$i]['js_path'] = '';
                    }

                    if (in_array($type, ['全屏视频'])) {
                        $video_url = M('type_param')->where(['text' => '视频播放地址'])->getField('value');
                        $list_arr[$i]['tv_play_url'] = $list_arr[$i]['video_path'] == '' ? $list_arr[$i]['tv_play_url'] : str_replace('{video}', $list_arr[$i]['video_path'], $video_url);
                        $list_arr[$i]['video_path'] = $_SERVER['SERVER_PORT'] == '' ? 'http://' . $_SERVER['SERVER_ADDR'] . '/video/' . $list_arr[$i]['video_path'] : 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/video/' . $list_arr[$i]['video_path'];
                    }

                    $con_id = $list_arr[$i]['id'];
                    $Model = new \Think\Model();
                    $t = $Model->query("select (type+0) as t from __PREFIX__content where id = $con_id");
                    $list_arr[$i]['type'] = $t[0]['t'];

                    if ($list_arr[$i]['icon_path'] == '') {
                        $list_arr[$i]['icon_path'] = '/webRoot/img/default_title_pic.jpg';
                    }
                }
            }

            $path = '..' . WEB_PATH . $reg_id . '/public/data/' . $reg_arr['code'];

            if (!is_dir($path)) {
                mkdir($path);
                @chmod($path, 0777);
            }
            $path .= '/' . $reg_arr['code'] . '_' . $con_arr['node_id'] . '.js';
            $boole = file_put_contents($path, json_encode($list_arr));
            if (empty($boole)) {
                createMsg('生成索引失败', 1);
                return;
            }
        } else {
            $home_node_arr = $tv_node->where(['node_code' => $con_arr['node_id']])->field(['type', 'region_id'])->find();

            $type = $home_node_arr['type'];   // 获取栏目类型
            $root_region_code = $home_node_arr['region_id'];   // 获取根区域码
            $node_id = $content_model->where(['id' => $_id])->getField('node_id');  // 获取node_id

            $map['node_id'] = $con_arr['node_id'];
            $map['region_id'] = $con_arr['region_id'];
            $map['home_type'] = $type;
            $map['status'] = '上线';
            $cont_arr = $content_model->where($map)->order('id desc')->limit(1)->find();    // 获取条目内容
            if (!empty($cont_arr)) {
                if ($type == 'focusPic') {
                    $pic_arr = explode('#', $cont_arr['contents']);
                    $con_str = json_encode($pic_arr);
                } elseif ($type == 'marquee') {
                    $con_str = $cont_arr['contents'];
                } elseif ($type == 'pageLink') {
                    if (false === strpos($cont_arr['contents'], 'http')) {
                        $con_str = substr($cont_arr['contents'], 8);
                    } else {
                        $con_str = $cont_arr['contents'];
                    }
                    $con_str = $con_str;
                } elseif ($type == 'videoFrame') {
                    $http_sta = strpos($cont_arr['contents'], 'http');
                    $rtsp_sta = strpos($cont_arr['contents'], 'rtsp');
                    $con_param_arr = explode('!!', $cont_arr['contents']);
                    if (false !== $http_sta || false !== $rtsp_sta) {
                        $con_str = str_replace('amp;', '', $con_param_arr[0]);
                    } else {
                        $con_str = 'http://192.168.38.21/play/?vsea_action=test&vsea_first=/video/' . $con_param_arr[0];
                    }
                    $con_str = $con_str;
                } elseif ($type == 'listContent') {
                    // video_path
                    if ($cont_arr['type'] == '监控') {
                        $video_path = $cont_arr['tv_play_url'];
                    } elseif (in_array($cont_arr['type'], ['全屏视频'])) {
                        $video_url = M('type_param')->where(['text' => '视频播放地址'])->getField('value');
                        $video_path = $cont_arr['video_path'] == '' ? $cont_arr['tv_play_url'] : str_replace('{video}', $cont_arr['video_path'], $video_url);
                    } else {
                        $video_path = '';
                    }

                    // js_path
                    if (in_array($cont_arr['type'], ['图文', '相册', '窗口视频'])) {
                        $js_path = WEB_PATH . $root_region_code . '/data/' . $reg_arr['code'] . '/' . md5($cont_arr['id']) . '.js';
                    } else {
                        $js_path = '';
                    }

                    $Model = new \Think\Model();
                    $t = $Model->query("select (type+0) as t from __PREFIX__content where id = " . $cont_arr['id']);
                    $con_type = $t[0]['t'];
                    $con_list_arr = ['id' => $cont_arr['id'], 'type' => $con_type, 'video_path' => $video_path, 'js_path' => $js_path];
                    $con_str = json_encode($con_list_arr);
                }
            } else {
                if ($type == 'focusPic') {
                    $con_str = json_encode([]);
                } elseif ($type == 'marquee') {
                    $con_str = '""';
                } elseif ($type == 'pageLink') {
                    $con_str = '""';
                } elseif ($type == 'videoFrame') {
                    $con_str = '""';
                } elseif ($type == 'listContent') {
                    $con_str = json_encode([]);
                }
            }


            $path = '..' . WEB_PATH . $root_region_code . '/data/' . $reg_arr['code'];
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $path .= '/' . $reg_arr['code'] . '_' . $node_id . '.js';
            $boole = file_put_contents($path, $con_str);
            if (empty($boole)) {
                createMsg('生成home_type索引失败', 1);
                return;
            }
        }

        $node_id = $tv_node->where(['node_code' => $con_arr['node_id']])->getField('id');
        $arr = $this->recursiveFindDir($node_id, $tv_node);
        $boole = $this->buildDirData($arr, $tv_node, $reg_id, $reg_arr['code']);    //FIXME 方法改写，多带最后一个参数
        return $boole;

    }

    //显示内容类别（获取枚举值数组）
    public function getTmpArr()
    {
        $prefix = C('DB_PREFIX'); // 获取前缀
        $content_model = M('content');
        $sql = "show columns from " . $prefix . "content where field='type'";
        $arr = $content_model->query($sql);
        $str = $arr[0]['type'];
        $str = substr($str, 5, strlen($str) - 6);
        $arr = explode(',', $str);// 计算出枚举列预置数据
        $class_arr = array();
        foreach ($arr as $key => $value) {
            $v = remove_quote($value);
            $class_arr[$key] = $v;
        }
        return $class_arr;
    }

    //递归找到上级有dir属性的节点
    public function recursiveFindDir($_node_id, $_tv_node_m)
    {
        $tv_arr = $_tv_node_m->where("id='" . $_node_id . "'")->field("id,pid,dir")->find();
        if (!empty($tv_arr['dir'])) return $tv_arr;
        else return $this->recursiveFindDir($tv_arr['pid'], $_tv_node_m);
    }

    //FIXME 递归找到上级有dir属性的节点，如果当前栏目有dir属性，则继续查询上一级(栏目管理时专用)
    public function recursiveFindDirPlus($_node_id, $_tv_node_m)
    {
        $tv_arr = $_tv_node_m->where("id='" . $_node_id . "'")->find();
        if (!empty($tv_arr['dir']) && $tv_arr['pid'] != 0) {
            $tv_arr_new = $_tv_node_m->where(['id' => $tv_arr['pid']])->field("id, pid, dir")->find();
            return $tv_arr_new;
        } else {
            return $this->recursiveFindDir($tv_arr['id'], $_tv_node_m);
        }
    }

    //向下生成上级dir_data.js索引数据
    public function buildDirData($_arr, $_tv_node_m, $_root_code, $_reg_id = '')   //FIXME 方法改写，多带最后一个参数
    {
        $arr = $this->recursiveFindNode($_arr['id'], $_tv_node_m); // 递归遍历获取所属所有子节点数组
        $ser_arr = $this->serializeArr($arr, $_arr['id']); // 按父子节点序列化数组

        $marquee = array();
        foreach ($ser_arr as $key => $value) {
            //$map['region_id'] = $_reg_id;     //Fixme 如果固定该region_id,则其他非node的栏目会受此参数影响而查不到，故取消该条件17-04-21
            $map['node_id'] = $value['node_code'];
            $map['status'] = '上线';
            if ($value['type'] == 'marquee') {
                $marquee = $value;
                unset($ser_arr[$key]);
            } elseif ($value['type'] == 'listContent') {    //FIXME 将生成的文件中listContent类型的text换成内容标题
                $node_title = M('content')->where($map)->order('id desc')->limit(1)->getField('title');
                if ($node_title) {
                    $ser_arr[$key]['text'] = $node_title;
                }
            } elseif ($value['type'] == 'pageLink') {
                $pageLink_text = M('content')->where($map)->order('id desc')->limit(1)->getField('contents');
                if ($pageLink_text) {
                    if (false === strpos($pageLink_text, 'http')) {
                        $pageLinkText = substr($pageLink_text, 8);
                    } else {
                        $pageLinkText = str_replace('amp;', '', $pageLink_text);
                    }
                } else {
                    $pageLinkText = '';
                }
                $ser_arr[$key]['url'] = $pageLinkText;
            }/* elseif ($value['type'] == 'videoFrame') {
                $videoFrame_text = M('content')->where($map)->order('id desc')->limit(1)->getField('contents');
                if ($videoFrame_text) {
                    $sta = strpos($videoFrame_text, 'http');
                    $videoFrame_arr = explode('!!', $videoFrame_text);
                    if (false === $sta) {
                        $videoFrameText = 'http://192.168.38.21/play/?vsea_action=test&vsea_first=/video/' . $videoFrame_arr[0];
                    } else {
                        $videoFrameText = $videoFrame_arr[0];
                    }
                } else {
                    $videoFrameText = '';
                }
                $ser_arr[$key]['dir'] = $videoFrameText;
            }*/
        }
        $arrs['marquee'] = $marquee;
        $arrs['node'] = array_values($ser_arr);
        $js_contents = 'var mainArray =' . json_encode($arrs) . ';';
        if ($_arr['dir'] == '/' . $_root_code)
            $file_path = '..' . WEB_PATH . $_root_code . '/public/data/' . 'index_data.js';
        else
            $file_path = '..' . WEB_PATH . $_root_code . '/public/data/' . $_arr['dir'] . '_data.js';
        $boole = file_put_contents($file_path, $js_contents);
        return $boole;
    }

    //获取所有子节点数组
    public function recursiveFindNode($_p_id, $_tv_node_m)
    {
        $field = "id,pid,text,level,dir,url,icon_src,icon_fcs,icon_blur,top,left,width,height,type,is_leaf,node_code,tv_icon";
        $node_arr = $_tv_node_m->where("pid='" . $_p_id . "'")->order('sort,id')->field($field)->select();
        static $arr;
        foreach ($node_arr as $key => $value) {
            $arr[] = $value;
            if ($value['is_leaf'] == 'no' and empty($value['dir'])) {
                $this->recursiveFindNode($value['id'], $_tv_node_m);
            }
        }
        return $arr;
    }

    //父子节点序列化子节点数组

    public function serializeArr($arr, $p_id = '0')
    {
        $tree = array();
        foreach ($arr as $row) {
            if ($row['pid'] == $p_id) {
                $tmp = $this->serializeArr($arr, $row['id']);
                if ($tmp) {
                    $row['node'] = $tmp;
                }
                $tree[] = $row;
            }
        }
        return $tree;
    }
}