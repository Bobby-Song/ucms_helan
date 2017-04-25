<?php
namespace Systems\Controller;

use Think\Controller;

class ContentController extends Controller
{
    /**
     * [验证session状态]
     * 本类由于涉及上传，故不能用构造函数来验证SESSION状态
     */
    public function sessionState()
    {
        $user_id = session('user_id');
        if (empty($user_id)) {
            $login = A('Admin/Login');
            $login->logOut();
            exit;
        }
    }

    /**
     * [索引页管理页面显示]
     */
    public function homeHtml()
    {
        $suffix = I("get._");
        $this->assign('suffix', $suffix);
        $this->assign('user_id', session('user_id'));
        $this->assign('user_type', session('type'));
        $this->sessionState();
        $this->display();
    }


    /**
     * [栏目内容管理页面显示]
     */
    public function dataHtml()
    {
        $suffix = I("get._");
        $this->assign('suffix', $suffix);
        $this->assign('user_id', session('user_id'));
        $this->assign('user_type', session('type'));
        $this->sessionState();
        $this->display();
    }

    /**
     * [栏目内容管理页面显示]
     */
    public function activeHtml()
    {
        $suffix = I("get._");
        $this->assign('suffix', $suffix);
        $this->assign('user_id', session('user_id'));
        $this->assign('user_type', session('type'));
        $this->sessionState();
        $this->display();
    }

    /**
     * [栏目内容管理页面显示]
     */
    public function activeDataHtml()
    {
        $suffix = I("get._");
        $this->assign('suffix', $suffix);
        $this->assign('user_id', session('user_id'));
        $this->assign('user_type', session('type'));
        $this->sessionState();
        $this->display();
    }

    public function commentDataHtml()
    {
        $suffix = I("get._");
        $this->assign('suffix', $suffix);
        $this->assign('user_id', session('user_id'));
        $this->assign('user_type', session('type'));
        $this->sessionState();
        $this->display();
    }

    /**
     * [栏目内容编辑页面显示]
     */
    public function editHtml()
    {
        $suffix = I("get._");
        $this->assign('suffix', $suffix);
        $this->sessionState();
        $this->display();
    }

    /**
     * [栏目内容审核页面显示]
     */
    public function auditHtml()
    {
        $suffix = I("get._");
        $this->assign('suffix', $suffix);
        $this->sessionState();
        $this->display();
    }

    /**
     * [索引页数据显示/增/删/改/查]
     */
    public function homeEdit()
    {
        $this->sessionState();
        $data = I('post.');
        $node_id = I('request.node_id');
        $home_type = I('request.node_type');
        $region_id = I('request.region_id');

        $content_model = M('content');
        $arr = array();
        if (is_numeric($node_id) and !empty($home_type)) { //显示数据
            $where['c.node_id'] = array('EQ', $node_id);
            $where['c.home_type'] = array('EQ', $home_type);
            $where['c.region_id'] = array('EQ', $region_id);

            $field = "c.* , r.text";
            $content_arr = $content_model->alias('c')->join('__REGION__ as r ON r.code=c.region_id')->where($where)->field($field)->select();

            if (count($content_arr) > 0) {
                $arr['total'] = count($content_arr);
                $arr['rows'] = $content_arr;
            }
            echo json_encode($arr);
        }

        if (!empty($data['flag'])) { // 数据新增、编辑
            $flag = $data['flag'];
            unset($data['flag']);
            $data['operate_time'] = date("Y-m-d") . " " . date("H:i:s");
            $data['operate_user_id'] = session('user_id');
            if ($flag == 1) {
                unset($data['id']);
                $status = $content_model->data($data)->add();
            } else if ($flag == 3) {
                unset($data['region_id']);
                $status = $content_model->save($data);
            }
            if (!empty($status)) createMsg('操作成功', 0);
            else createMsg('操作失败', 1);
        }
    }

    /**
     * [栏目页数据显示]
     */
    public function showData()
    {
        $arr = array();
        $this->sessionState();
        $content_model = M('content');
        $active_node = M('active_node');

        $node_id = I('get.node_id');
        $node_type = I('get.node_type');
        $region_code = I('get.region_id');
        $active_node_id = I('get.active_node_id');
        $user_type = session('type');
        $where = "node_id='$node_id' and region_id='$region_code'";
        if ($user_type == '内容审核' && $node_type != 'activeNode') $where .= " and status='待审核'";
        if ($user_type == '内容发布' && $node_type != 'activeNode') $where .= " and (status='已审核' or status='上线' or status='下线')";
        if ($user_type == '系统总监' && $node_type != 'activeNode') $where .= " and status='上线'";
        if (!empty($name) and !empty($value)) {
            $where .= " and $name LIKE '%$value%'";
        }

        if ($node_type == 'activeNode') {
            if ($active_node_id != '') {
                // 查询该active node下面所有的content id
                $content_ids = $active_node->where(['id' => $active_node_id])->getField('content_ids');
                if ($content_ids != '') {
                    $content_id_str = rtrim($content_ids, ',');
                    $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
                    $con_arr = $Model->query("select * from __PREFIX__content where id in (" . $content_id_str . ")");
                } else {
                    $con_arr = [];
                }
            } else {
                $con_arr = $active_node->where($where)->select();
            }
        } else {
            $con_arr = $content_model->where($where)->select();
        }
        $arr['total'] = count($con_arr);
        $page_num = I('post.rows');
        $page = I('post.page');
        $start_num = $page_num * ($page - 1);

        if ($node_type == 'activeNode') {
            if ($active_node_id != '') {
                // 查询该active node下面所有的content id
                $content_ids = $active_node->where(['id' => $active_node_id])->getField('content_ids');
                if ($content_ids != '') {
                    $content_id_str = rtrim($content_ids, ',');
                    $map['id'] = ['in', $content_id_str];
                    $con_arr = $content_model->where($where)->where($map)->order("id DESC")->limit($start_num, $page_num)->select();
                } else {
                    $con_arr = [];
                }
            } else {
                $con_arr = $active_node->where($where)->order("id DESC")->limit($start_num, $page_num)->select();
            }
        } else {
            $con_arr = $content_model->where($where)->order("sort DESC")->limit($start_num, $page_num)->select();
        }


        if (!empty($con_arr)) {
            foreach ($con_arr as $con_key => $con_value) {
                $con_arr[$con_key]['md5_id'] = md5($con_value['id']);

                if ($node_type == 'activeNode') {
                    $con_arr[$con_key]['img'] = '<img src=' . rtrim($con_value['pic'], '!!') . ' style="width:60px;height:70px">';
                }

                //FIXME 若查询结果非空，则查询无图征询的选项附在数组中
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

        $arr['rows'] = $con_arr;
        echo json_encode($arr);
    }


    //显示内容类别（获取枚举值）
    public function showTmpClass()
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
            $class_arr[$key]['id'] = $v;
            $class_arr[$key]['text'] = $v;
        }
        echo json_encode($class_arr);
    }

    /**
     * [索引页数据/增/改]
     */
    public function editData()
    {
        $content_model = M('content');
        $flag = I('get.flag');
        $data = I('post.');
        $node_type = $data['node_type'];
        if ($node_type == 'listContent') {
            $data['is_node'] = 'n';
            $data['home_type'] = $node_type;
        } else {
            $data['is_node'] = 'y';
            $data['home_type'] = '';
        }
        unset($data['node_type']);
        $data['operate_time'] = date("Y-m-d") . " " . date("H:i:s");

        //将富文本编辑器内容格式化 参数890将从参数表中获取
        if (in_array($data['type'], ['无图征询', '图文', '窗口视频'])) {
            $array = contentEditor($data['contents'], 890);
            $data['contents'] = $array['content'];
            if (!empty($array['icon']))
                $data['icon_path'] = $array['icon'];
            $len = '';
            if (count($array['pic']) > 0) {
                foreach ($array['pic'] as $k => $v) {
                    if ($k < count($array['pic']) - 1)
                        $len .= $v . '!!';
                    else
                        $len .= $v;
                }
                $data['path'] = $len;
            }
        } elseif (in_array($data['type'], ['相册', '有图征询'])) {
            $con_arr = explode('@@', $data['contents']);
            $data['path'] = $con_arr[0];
            if (in_array($data['type'], ['相册'])) {  // 相册
                $data['contents'] = $con_arr[1];
            } else {    // 有图征询
                $data['consults'] = $data['contents'];
                $data['contents'] = '';
            }
        } elseif ($data['type'] == '答题') {
            $ques_str = $data['contents'];
            $data['contents'] = '';
        }

        if (in_array($data['type'], ['监控', '全屏视频', '窗口视频'])) {    // FIXME 处理监控、视频的播放地址
            if (in_array($data['type'], ['监控'])) {
                //TODO 此处获取监控播放地址的方式是暂时的，APP跟TV端监控的播放地址应该是不同的，并且播放地址需按region区分
                $tv_play_url = M('type_param')->where(['text' => '监控播放地址'])->getField('value');
                $mobile_play_url = M('type_param')->where(['text' => '手机端监控播放地址'])->getField('value');

                $stream_arr = explode('!!', $data['contents']);
                $search_arr = ['{stream}', '{vsea_u}', '{vsea_p}'];
                if ($tv_play_url && $mobile_play_url) {
                    $data['tv_play_url'] = str_replace($search_arr, $stream_arr, $tv_play_url);         //TODO TV
                    $data['video_path'] = str_replace($search_arr, $stream_arr, $mobile_play_url);      //TODO APP

                    // 将监控参数保存至camera_params变量中，并将data数组中的contents清空
                    $c_params = $data['contents'];
                    $data['contents'] = '';
                } else {
                    createMsg('请在参数设置中添加监控播放地址', 1);
                    return;
                }
            } elseif (in_array($data['type'], ['全屏视频', '窗口视频'])) {

                $video = $data['video_path'];

                if (false === strpos($video, 'http')) { // 此处为上传的视频文件
                    $data['video_path'] = $video;
                    $data['tv_play_url'] = '';
                } else {
                    $data['video_path'] = '';
                    $data['tv_play_url'] = $video;
                }
            }

        }

        if ($flag == 1) {
            unset($data['id']);
            $data['operate_user_id'] = session('user_id');
            $status = $id = $content_model->data($data)->add();

            if ($data['active_node_id'] != '') {
                //将active_node表中的content_ids字段中加入此次插入内容的id
                $content_ids = M('active_node')->where(['id' => $data['active_node_id']])->getField('content_ids');
                $a_data['content_ids'] = $content_ids . $status . ',';
                M('active_node')->where(['id' => $data['active_node_id']])->save($a_data);
            }

            if (in_array($data['type'], ['无图征询', '有图征询'])) {  //FIXME 将该内容的选项插入consult_option表中
                $cons_data['content_id'] = $status;
                if ($data['type'] == '无图征询') {
                    foreach (explode('!!', $data['options']) as $cons_key => $cons_value) {
                        $cons_data['option_desc'] = $cons_value;
                        M('consult_option')->data($cons_data)->add();
                    }
                } elseif ($data['type'] = '有图征询') {
                    $consult_arr = explode('@@', $data['consults']);
                    $pic_arr = explode('!!', $consult_arr[0]);
                    $title_arr = explode('!!', $consult_arr[1]);
                    $content_arr = explode('!!', $consult_arr[2]);
                    foreach ($pic_arr as $pic_key => $pic_val) {
                        $consult_data['content_id'] = $id;
                        $consult_data['c_pic'] = $pic_val;
                        $consult_data['c_title'] = $title_arr[$pic_key];
                        $consult_data['c_content'] = $content_arr[$pic_key];
                        $cons_data['consult_id'] = $consult_id = M('consult')->data($consult_data)->add();
                        foreach (explode('!!', $data['options']) as $cons_key => $cons_value) {
                            $cons_data['option_desc'] = $cons_value;
                            M('consult_option')->data($cons_data)->add();
                        }
                    }
                }

            } elseif ($data['type'] == '监控') {
                // 将监控参数插入到camera_ext表中
                $camera_params['content_id'] = $id;
                $camera_params['params'] = $c_params;
                M('camera_ext')->data($camera_params)->add();
            } elseif ($data['type'] == '答题') {
                $ques_arr = explode('@@', $ques_str);
                foreach ($ques_arr as $q_key => $q_val) {
                    $q_arr = explode('!!', $q_val);
                    $ques_data['content_id'] = $id;
                    $ques_data['type'] = $q_arr[1];
                    $ques_data['ques_title'] = $q_arr[0];
                    $ques_data['standard_answer'] = $q_arr[2];
                    $ques_id = M('question')->add($ques_data);  //将数据插入question表中

                    $ques_opts = explode('##', $q_arr[3]);
                    foreach ($ques_opts as $q_o_k => $q_o_v) {
                        $ques_opt_data['question_id'] = $ques_id;
                        $ques_opt_data['option_value'] = $q_o_v;
                        M('question_option')->add($ques_opt_data);  //将数据插入question_option表中
                    }
                }
            }

            if (!empty($status)) { // 将新增ID写入排序字段
                $save_arr['id'] = $status;
                $save_arr['sort'] = $status;
                $save_arr['status'] = '待审核';
                $status = $content_model->save($save_arr);
            }
        } else if ($flag == '3') {
            $data['status'] = '待审核';
            $data['review_msg'] = '';
            $data['operate_user_id'] = session('user_id');
            if ($data['type'] == '有图征询') {
                //FIXME 先处理被删除的consult
                if ($data['consult_delete'] != '') {
                    $cons_del_arr_1 = explode('_', $data['consult_delete']);
                    $cons_del_arr_2 = array_filter($cons_del_arr_1);
                    if (!empty($cons_del_arr_2)) {
                        foreach ($cons_del_arr_2 as $c_d_id) {
                            // 删除consult_option表中的数据
                            M('consult_option')->where(['consult_id' => $c_d_id])->delete();
                            $cons_pic = M('consult')->where(['id' => $c_d_id])->getField('c_pic');
                            if (file_exists('..' . $cons_pic)) {
                                unlink('..' . $cons_pic);
                            }
                            M('consult')->where(['id' => $c_d_id])->delete();
                        }
                    }
                }

                //FIXME 处理被更改、新增的consult
                $consult_arr = M('consult')->where(['content_id' => $data['id']])->select();
                $consult_pic_arr = array_column($consult_arr, 'c_pic'); // consult c_pic数组
                $con_param_arr = explode('@@', $data['consults']);
                $c_pic_str = $con_param_arr[0];
                $c_title_str = $con_param_arr[1];
                $c_content_str = $con_param_arr[2];
                $c_pic_arr = explode('!!', $c_pic_str);
                $c_title_arr = explode('!!', $c_title_str);
                $c_content_arr = explode('!!', $c_content_str);
                foreach ($c_pic_arr as $c_k => $c_v) {
                    $c_data['c_title'] = $c_title_arr[$c_k];
                    $c_data['c_content'] = $c_content_arr[$c_k];
                    $c_data['content_id'] = $data['id'];

                    if (in_array($c_v, $consult_pic_arr)) { // 如果该条consult存在，则update
                        M('consult')->where(['c_pic' => $c_v])->save($c_data);
                    } else { // 如果该条consult存在，则add
                        $c_data['c_pic'] = $c_v;
                        $c_con_id = M('consult')->add($c_data);

                        $option_arr = explode('!!', $data['options']);
                        foreach ($option_arr as $o_k => $o_v) { // 添加consult_option数据
                            $o_data['consult_id'] = $c_con_id;
                            $o_data['content_id'] = $data['id'];
                            $o_data['option_desc'] = $o_v;
                            M('consult_option')->add($o_data);
                        }
                    }
                }
            } elseif ($data['type'] == '答题') {
                //FIXME 先处理被删除的question
                if ($data['consult_delete'] != '') {
                    $ques_del_arr_1 = explode('_', $data['consult_delete']);
                    $ques_del_arr_2 = array_filter($ques_del_arr_1);
                    if (!empty($ques_del_arr_2)) {
                        foreach ($ques_del_arr_2 as $q_d_id) {
                            // 删除question_option 和 question 表中的数据
                            M('question_option')->where(['question_id' => $q_d_id])->delete();
                            M('question')->where(['id' => $q_d_id])->delete();
                        }
                    }
                }

                $ques_arr = explode('@@', $ques_str);
                foreach ($ques_arr as $q_key => $q_val) {
                    $q_arr = explode('!!', $q_val);
                    $ques_data['content_id'] = $data['id'];
                    $ques_data['type'] = $q_arr[1];
                    $ques_data['ques_title'] = $q_arr[0];
                    $ques_data['standard_answer'] = $q_arr[2];

                    if ($q_arr[4] != '') {  // 更新question表中的内容
                        M('question')->where(['id' => $q_arr[4]])->save($ques_data);
                    } else {
                        $ques_id = M('question')->add($ques_data);  // 将数据插入question表中
                    }

                    $ques_opts = explode('##', $q_arr[3]);
                    foreach ($ques_opts as $q_o_k => $q_o_v) {
                        if ($q_arr[4] != '') {  // 更新question_option表中的内容
                            $ques_opt_ids = M('question_option')->where(['question_id' => $q_arr[4]])->order('id asc')->field('id')->select();
                            $ques_opt_id_arr = array_column($ques_opt_ids, 'id');
                            foreach ($ques_opt_id_arr as $q_o_i_k => $q_o_i_v) {
                                $ques_opt_data['option_value'] = $ques_opts[$q_o_i_k];
                                $ques_opt_data['question_id'] = $q_arr[4];
                                M('question_option')->where(['id' => $q_o_i_v])->save($ques_opt_data);
                            }
                        } else {
                            $ques_opt_data['option_value'] = $q_o_v;
                            $ques_opt_data['question_id'] = $ques_id;
                            M('question_option')->add($ques_opt_data);  // 将数据插入question_option表中
                        }
                    }
                }
            }

            $status = $content_model->save($data);

            if ($data['type'] == '监控') {
                // 更新camera_ext表
                $camera_params['content_id'] = $data['id'];
                $camera_params['params'] = $c_params;
                M('camera_ext')->save($camera_params);
            }
        }

        if (in_array($data['type'], ['图文', '相册', '窗口视频'])) {    //FIXME 此三种类型 需写json
            if ($flag == 1) {
                $con_id = $id;
            } elseif ($flag == 3) {
                $con_id = $data['id'];
            }

            $this->buildContentJson($con_id, 'pre');
        }

        if (!empty($status)) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }

    public function activeNodeEditData()
    {
        $data = I('post.');
        $flag = I('get.flag');
        $active = M('active_node');

        if ($flag == '1') {
            unset($data['id']);

            // 判断是否有相同地区重名的active node
            $res = $active->where(['title' => $data['title'], 'region_id' => $data['region_id'], 'node_id' => $data['node_id']])->select();
            if (!empty($res)) {
                createMsg('操作失败', 1);
            } else {
                $result = $active->add($data);
            }
        } elseif ($flag == '3') {
            $result = $active->save($data);
        }

        if ($result) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }

    public function unlinkFile()
    {
        $arr['status'] = '系统提示';
        $flag = I('post.flag');
        $img_path = I('post.img_path');
        $icon = I('post.icon');
        $node_type = I('post.node_type');
        $editsFlag = I('post.editsFlag');
        $active_node_id = I('post.active_node_id');

        if (unlink($img_path)) {
            if ($editsFlag == 3) {
                if ($flag == 'icon') {
                    $data = array();
                    if ($node_type == 'activeNode') {
                        $data['id'] = $active_node_id;
                    }

                    if (strstr($icon, '!!'))
                        $data['pic'] = $icon;
                    else
                        $data['pic'] = '';
                    M('active_node')->save($data);
                }
            }
            $arr['message'] = '操作成功';
            $arr['error'] = 1;
        } else {
            $arr['message'] = '操作失败';
            $arr['error'] = 0;
        }
        echo json_encode($arr);
    }

    /**
     * [生成内容页JSON]
     *
     */
    public function buildContentJson($_id, $mark = '')
    {
        $main_arr = [];

        // 通过content id 获取content信息
        $con_arr = M('content')->where(['id' => $_id])->find();

        // 获取根region code
        $root_region_code = M('tv_node')->where(['node_code' => $con_arr['node_id']])->getField('region_id');

        // 生成json的路径
        $path = $mark == '' ? '..' . WEB_PATH . $root_region_code . '/' . 'data/' . $con_arr['region_id'] : '..' . WEB_PATH . $root_region_code . '/' . 'preview_data/' . $con_arr['region_id'];
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        // 构建content内容数组
        $main_arr['id'] = $con_arr['id'];
        $main_arr['title'] = $con_arr['title'];
        $main_arr['node_name'] = M('tv_node')->where(['node_code' => $con_arr['node_id']])->getField('text');
        $main_arr['content'] = $con_arr['contents'];

        $video_url = M('type_param')->where(['text' => '视频播放地址'])->getField('value');
        $main_arr['video'] = $con_arr['video_path'] == '' ? '' : str_replace('{video}', $con_arr['video_path'], $video_url);
        $main_arr['album_arr'] = '';
        if (in_array($con_arr['type'], ['相册'])) {
            $pic_arr = explode('!!', $con_arr['path']);
            $name_arr = explode('!!', $con_arr['contents']);
            foreach ($pic_arr as $key => $value) {
                $main_arr['album_arr'][$key]['pic'] = $value;
                $main_arr['album_arr'][$key]['name'] = $name_arr[$key];
            }
            $main_arr['content'] = '';
        }

        // 将数据写入json文档中
        $path .= '/' . md5($_id) . '.js';
        $boole = file_put_contents($path, json_encode($main_arr));
        if (empty($boole)) {
            createMsg('操作失败', 1);
            return;
        }
    }


    // 上传视频
    public function moviesUpLoad()
    {
        $filename = $_POST['fileName'];
        $array = array();
        if ($filename) {
            file_put_contents('../' . VIDEO_PATH . $filename, file_get_contents($_FILES["file"]["tmp_name"]), FILE_APPEND);
            $array['success'] = true;
            echo json_encode($array);
        }
    }

    // 上传图片
    public function picUpload()
    {
        $path = '..' . UPLOAD_PATH . 'image/';
        $p = UPLOAD_PATH . 'image/';
        if (!is_dir($path)) {
            mkdir($path);
            @chmod($path, 0777);
        }
        $path .= date("Ymd") . '/';
        $p .= date("Ymd") . '/';
        if (!is_dir($path)) {
            mkdir($path);
            @chmod($path, 0777);
        }
        $filename = $_POST['fileName'];
        $ext = substr($filename, strripos($filename, '.'));
        $file_name = md5(date("Ymd") . time() . rand(10000, 99999)) . $ext;
        $p .= $file_name;
        $array = array();
        if ($filename) {
            file_put_contents($path . $file_name, file_get_contents($_FILES["file"]["tmp_name"]), FILE_APPEND);
            $array['success'] = true;
            $array['file_name'] = $p;
            echo json_encode($array);
        }
    }

    // 上传网页
    public function htmlUpload()
    {
        $path = '..' . UPLOAD_PATH . 'html/';
        if (!is_dir($path)) {
            mkdir($path);
            @chmod($path, 0777);
        }
        $path .= date("Ymd") . '/';
        if (!is_dir($path)) {
            mkdir($path);
            @chmod($path, 0777);
        }
        $filename = $_POST['fileName'];
        $ext = substr($filename, strripos($filename, '.'));
        $file_name = md5(date("Ymd") . time() . rand(10000, 99999)) . $ext;
        $array = array();
        if ($filename) {
            file_put_contents($path . $file_name, file_get_contents($_FILES["file"]["tmp_name"]), FILE_APPEND);
            $array['success'] = true;
            $array['file_path'] = '../../' . $path . $file_name;
            echo json_encode($array);
        }
    }

    //KingEdit 富文本编辑器 本地上传图片
    public function uploadJson()
    {
        $url = I('get.url');
        $path = '../../../webRoot/upload/image/';
        $p = '../webRoot/upload/image/';
        if (!is_dir($p)) {
            mkdir($p);
            @chmod($p, 0777);
        }
        $file = $_FILES['imgFile'];
        funUploadJson($url, $path, $file);
    }

    // 数据排序
    public function lineMove()
    {
        $from = I('post.froms');
        $fromId = I('post.fromId');
        $to = I('post.to');
        $toId = I('post.toId');
        $content_model = M('content');

        $data = array();
        $data['id'] = $toId;
        $data['sort'] = $from;

        $datas = array();
        $datas['id'] = $fromId;
        $datas['sort'] = $to;

        $status = $content_model->save($data);
        if (!empty($status)) {
            $status = $content_model->save($datas);
            if (!empty($status)) createMsg('操作成功', 0);
            else createMsg('操作失败', 1);
        } else createMsg('操作失败', 1);
    }

    // 数据置顶
    public function topMove()
    {
        $content_model = M('content');
        $id = I('post.id');
        $text_arr = $content_model->order("sort DESC")->find();
        $to_id = $text_arr['id'];
        $to_sort = $text_arr['sort'];

        $from = array();
        $from['id'] = $id;
        $from['sort'] = $to_sort;
        $to = array();
        $to['id'] = $to_id;
        $to['sort'] = $id;

        $status = $content_model->save($from);
        if (!empty($status)) {
            $status = $content_model->save($to);
            if (!empty($status)) {
                createMsg('置顶成功', 0);
            } else {
                createMsg('置顶失败', 1);
            }
        } else {
            createMsg('置顶失败', 1);
        }
    }


    // 审核发布
    public function audits()
    {
        $data = I('post.');
        $content_model = M('content');
        if ($data['flag'] == 'con_auditor') {
            $data['audit_user_id'] = session('user_id');
            $data['audit_time'] = date("Y-m-d") . " " . date("H:i:s");
            unset($data['flag']);
            $status = $content_model->save($data);
        } else if ($data['flag'] == 'con_release') {
            $data['release_user_id'] = session('user_id');
            $data['release_time'] = date("Y-m-d") . " " . date("H:i:s");
            unset($data['flag']);
            $status = $content_model->save($data);
            if (!empty($status)) {
                $type = $content_model->where(['id' => $data['id']])->getField('type');
                if (in_array($type, ['图文', '相册', '窗口视频'])) {    //FIXME 此三种类型 需写json
                    $this->buildContentJson($data['id']);
                }
                $BuildIndex = A('BuildIndex');
                $status = $BuildIndex->buildIndexJson($data['id']);
                $this->buildActiveIndexJson($data['id']);
            }
        }
        if ($status === false) {
            createMsg('操作失败', 1);
        } else {
            createMsg('操作成功', 0);
        }
    }

    /**
     * @param $_id
     * @return mixed
     */
    public function buildActiveIndexJson($_id)
    {
        umask(0000);
        $active_id_arr = [];
        $main_arr = [];

        // 通过Content id 获取node_id 和 region_id
        $con_arr = M('content')->where(['id' => $_id])->find();
        $region_id = $con_arr['region_id'];
        $node_id = $con_arr['node_id'];
        $node_type = M('tv_node')->where(['node_code' => $node_id])->getField('type');
        if ($node_type == 'activeNode') {
            $active_arr = M('active_node')->where(['node_id' => $node_id, 'region_id' => $region_id])->select();
            if (!empty($active_arr)) {
                foreach ($active_arr as $a_key => $a_val) {
                    if ($a_val['content_ids'] != '') {
                        $content_ids_arr = explode(',', rtrim($a_val['content_ids'], ','));
                        if (in_array($_id, $content_ids_arr)) {
                            array_push($active_id_arr, $a_val['id']);
                        }
                    } else {
                        unset($v);
                    }
                }
            }
            $active_node_id = $active_id_arr[0];
            $active_data = M('active_node')->where(['id' => $active_node_id])->find();
            $active_data['icon_path'] = rtrim($active_data['pic'], '!!');
            unset($active_data['pic']);
            $main_arr['active_desc'] = $active_data;

            $root_region_code = substr_replace($active_data['region_id'], '000000', 6);
            $path = '..' . WEB_PATH . $root_region_code . '/public/active_data/' . $active_data['region_id'];
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $path .= '/' . $active_data['region_id'] . '_' . $active_node_id . '.js';

            // 查询出该active_node下的所有content信息
            $content_ids = rtrim($active_data['content_ids'], ',');
            $where['id'] = ['in', $content_ids];
            $where['status'] = ['eq', '上线'];
            $where['user_src'] = ['eq', 'user'];
            $where['node_id'] = ['eq', $active_data['node_id']];
            $where['region_id'] = ['eq', $active_data['region_id']];
            $field = 'id, title, icon_path, video_path, type, node_id, region_id, tv_play_url';
            $list_arr = M('content')->where($where)->order('sort desc')->field($field)->select();
            $list_arr_count = count($list_arr);
            for ($i = 0; $i < $list_arr_count; $i++) {
                $type = $list_arr[$i]['type'];
                if (in_array($type, ['图文', '相册', '窗口视频'])) {
                    $js_path = WEB_PATH . $root_region_code . '/data/';
                    $js_path .= $active_data['region_id'] . '/' . md5($list_arr[$i]['id']) . '.js';
                    $list_arr[$i]['js_path'] = $js_path;
                } else {
                    $list_arr[$i]['js_path'] = '';
                }

                if (in_array($type, ['全屏视频'])) {
                    $video_url = M('type_param')->where(['text' => '视频播放地址'])->getField('value');
                    $list_arr[$i]['tv_play_url'] = $list_arr[$i]['video_path'] == '' ? $list_arr[$i]['tv_play_url'] : str_replace('{video}', $list_arr[$i]['video_path'], $video_url);
                }

                $con_id = $list_arr[$i]['id'];
                $Model = new \Think\Model();
                $t = $Model->query("select (type+0) as t from __PREFIX__content where id = $con_id");
                $list_arr[$i]['type'] = $t[0]['t'];
            }
            $main_arr['active_list'] = $list_arr;

            $boole = file_put_contents($path, json_encode($main_arr));
        } else {
            $boole = true;
        }
        return $boole;
    }

    //上/下线
    public function onOrDownLine()
    {
        $data = I('post.');
        $content_model = M('content');
        $status = $content_model->save($data);
        if (!empty($status)) {
            $cont_arr = $content_model->where(['id' => $data['id']])->field(['node_id', 'region_id', 'type', 'is_node'])->find();

            if (in_array($cont_arr['type'], ['图文', '相册', '窗口视频']) && $cont_arr['is_node'] == 'y') {    // 此三种类型 需处理内容json
                if ($data['status'] == '下线') {  // 下线时，删除生成的内容json文件
                    $root_region_id = M('tv_node')->where(['code' => $cont_arr['node_id']])->getField('region_id');
                    $json_path = '..' . WEB_PATH . $root_region_id . '/' . 'data/' . $cont_arr['region_id'] . '/' . md5($data['id']) . '.js';
                    if (file_exists($json_path)) {
                        unlink($json_path);
                    }
                } else {    // 上线时，生成内容json文件
                    $this->buildContentJson($data['id']);
                }
            }

            $BuildIndex = A('BuildIndex');
            $status = $BuildIndex->buildIndexJson($data['id']);
            $this->buildActiveIndexJson($data['id']);
        }
        if (!empty($status)) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }

    //数据删除
    public function deletes()
    {
        $content_model = M('content');
        $str = I('post.str');
        $arr = explode(',', $str);

        $active_node_id = I('get.active_node_id');
        if ($active_node_id != '') {    // 如果为active node 的内容，则需更新active_node表中的content_ids字段
            $content_ids = M('active_node')->where(['id' => $active_node_id])->getField('content_ids');
            if ($content_ids != '') {
                $content_id_arr = explode(',', rtrim($content_ids, ','));
                foreach ($content_id_arr as $c_i_k => $c_i_v) {
                    foreach ($arr as $k => $v) {
                        if ($v == $c_i_v) {
                            unset($content_id_arr[$c_i_k]);
                        }
                    }
                }
                $cont_id_arr = array_filter($content_id_arr);
                if (!empty($cont_id_arr)) {
                    $c_content_id_str = implode(',', $cont_id_arr);
                    $c_data['content_ids'] = $c_content_id_str . ',';
                } else {
                    $c_data['content_ids'] = '';
                }
                M('active_node')->where(['id' => $active_node_id])->save($c_data);
            }
        }

        foreach ($arr as $key => $value) {
            $con_arr = $content_model->where("id='$value'")->find();

            // 获取根region code
            $root_region_code = M('tv_node')->where(['code' => $con_arr['node_id']])->getField('region_id');

            // 生成json的完全路径
            $json_path = '..' . WEB_PATH . $root_region_code . '/' . 'preview_data/' . $con_arr['region_id'] . '/' . md5($value) . '.js';
            if (file_exists($json_path)) {
                unlink($json_path);
            }

            if ($con_arr['path'] != '') {
                $p_arr = explode('!!', $con_arr['path']);
                foreach ($p_arr as $k => $v) {
                    $s = unlink('..' . $v);
                    if (empty($s)) {
                        createMsg('删除文件' . $v . '失败', 1);
                        exit;
                    }
                }
            }
            if ($con_arr['video_path'] != '') {
                $s = unlink('..' . VIDEO_PATH . $con_arr['video_path']);
                if (empty($s)) {
                    createMsg('删除文件' . $con_arr['video_path'] . '失败', 1);
                    exit;
                }
            }

            if ($con_arr['icon_path'] != '') {
                $s = unlink('..' . $con_arr['icon_path']);
                if (empty($s)) {
                    createMsg('删除文件' . $con_arr['icon_path'] . '失败', 1);
                    exit;
                }
            }


            $types = $content_model->where(['id' => $value])->getField('type');
            if (in_array($types, ['无图征询', '有图征询'])) {   //FIXME 删除征询选项
                M('consult_option')->where(['content_id' => $value])->delete();
                M('consult')->where(['content_id' => $value])->delete();
            } elseif ($types == '监控') {  //FIXME 删除监控参数
                M('camera_ext')->where(['content_id' => $value])->delete();
            } elseif ($types == '答题') {
                $ques_ids = M('question')->where(['content_id' => $value])->field('id')->select();
                $ques_id_arr = array_column($ques_ids, 'id');
                foreach ($ques_id_arr as $q_i_k => $q_i_v) {
                    M('question_option')->where(['question_id' => $q_i_v])->delete();
                }
                M('question')->where(['content_id' => $value])->delete();
            }

            $status = $content_model->delete($value);
            if (empty($status)) {
                createMsg('删除ID=' . $value . '数据失败', 1);
                exit;
            }

        }
        createMsg('删除成功', 0);
    }

    public function activeDeletes()
    {
        $str = I('post.str');
        $arr = explode(',', $str);

        $active = M('active_node');

        foreach ($arr as $key => $value) {
            $active_arr = $active->where("id='$value'")->find();

            if ($active_arr['pic'] != '') {
                $pic_arr = explode('!!', $active_arr['pic']);
                $p_arr = array_filter($pic_arr);
                foreach ($p_arr as $k => $v) {
                    if (file_exists('..' . $v)) {
                        $s = unlink('..' . $v);
                        if (empty($s)) {
                            createMsg('删除文件' . $v . '失败', 1);
                            exit;
                        }
                    }
                }
            }

            $status = $active->delete($value);
            if (empty($status)) {
                createMsg('删除ID=' . $value . '数据失败', 1);
                exit;
            }

        }
        createMsg('删除成功', 0);
    }

    //生成内容页JSON
    public function createConJson($_id)
    {
        $text = M('text');
        $text_arr = $text->where("id='$_id'")->find();
        $file_path = WEB_ROOT . $text_arr['node_id'] . '/';
        if (!file_exists($file_path)) mkdir($file_path, 0777);
        $file_path .= 'json/';
        if (!file_exists($file_path)) mkdir($file_path, 0777);
        $file = $file_path . $_id . '.json';
        file_put_contents($file, json_encode($text_arr));
    }


    /**
     * 生成电视端播放地址
     * @param $_id int content id
     * @return $play_url string 监控或者视频播放地址
     */
    public function createTVPlayUrl($_id)
    {
        $con_arr = M('content')->where(['id' => $_id])->find();
        $type = $con_arr['type'];
        $region_code = $con_arr['region_id'];

        if ($type == '监控') {
            return;
        } elseif ($type == '全屏视频' || $type == '窗口视频') {
            return;
        }
    }


    /**
     * 获取无图征询内容以及选项票数
     * @param id int 文章ID
     * @return mixed
     */
    public function getConsultInfo()
    {
        $_id = I('get.id');
        $list_arr = [];
        $con_arr = M('content')->where(['id' => $_id])->field(['title', 'contents'])->find();
        $list_arr['content_id'] = $_id;
        $list_arr['title'] = $con_arr['title'];
        $list_arr['contents'] = $con_arr['contents'];

        $option_arr = M('consult_option')->where(['content_id' => $_id])->field(['id', 'option_desc', 'vote_number'])->select();
        $list_arr['options'] = $option_arr;

        echo json_encode($list_arr);
    }

    /**
     * 获取有图征询数据
     */
    public function getConsultPicInfo()
    {
        $id = I("get.id");

        $cDb = M('content');
        $conDb = M('consult');
        //$conOptDb = M('consult_option');

        $ret = $cDb->field('id, title, contents, video_path')->where('id=%s', array($id))->find();

        $url = 'http://' . C("SERVER_IP") . ':' . C("SERVER_PORT");

        $conRet = $conDb->where('content_id=' . $ret['id'])->select();

        foreach ($conRet as $tk => $tv) {
            //$optRet = $conOptDb->field('id, option_desc as opt, vote_number as vote')->where('consult_id='.$tv['id'].' and content_id='.$ret['id'])->select();
            $ret['consultPic'][$tk]['id'] = $tv['id'];
            $ret['consultPic'][$tk]['title'] = $tv['c_title'];
            $ret['consultPic'][$tk]['content'] = $tv['c_content'];
            $ret['consultPic'][$tk]['picture'] = empty($tv['c_pic']) ? '' : $url . $tv['c_pic'];
            //$ret['consultPic'][$tk]['data'] = $optRet;
        }

        echo json_encode($ret);
    }

    /**
     * 获取答题数据
     */
    public function getQuestionInfo()
    {
        $id = I("get.id");

        $cDb = M('content');
        $quesDb = M('question');
        $quesOptDb = M('question_option');

        $ret = $cDb->field('id, title, contents')->where('id=%s', array($id))->find();

        $url = 'http://' . C("SERVER_IP") . ':' . C("SERVER_PORT");

        $quesRet = $quesDb->field('id, type, (type+0) as typeid, ques_title, ques_content, ques_pic')->where('content_id=' . $ret['id'])->select();

        foreach ($quesRet as $tk => $tv) {
            $optRet = $quesOptDb->field('id, option_name as opt, option_value as val')->where('question_id=' . $tv['id'])->select();
            $ret['question'][$tk]['id'] = $tv['id'];
            $ret['question'][$tk]['type'] = $tv['type'];
            $ret['question'][$tk]['typeid'] = $tv['typeid'];
            $ret['question'][$tk]['title'] = $tv['ques_title'];
            $ret['question'][$tk]['content'] = $tv['ques_content'];
            $ret['question'][$tk]['picture'] = empty($tv['ques_pic']) ? '' : $url . $tv['ques_pic'];
            $ret['question'][$tk]['data'] = $optRet;
        }

        echo json_encode($ret);
    }

    public function answerQuestion()
    {
        $conId = $_GET["conId"];
        $stbid = $_GET['stbid'];

        $where['stb_id'] = array("EQ", $stbid);
        $where['content_id'] = array('EQ', $conId);

        $quesRecDb = M('question_record');
        $num = $quesRecDb->where($where)->count();

        if ($num != 0) {
            echo ERR_VOTE_REPEAT;
            exit;
        }

        $data = array();
        $ip = get_client_ip(1);
        $tmpData = json_decode(urldecode($_GET['data']), true);

        foreach ($tmpData as $k => $va) {
            $data[$k]['stb_id'] = $stbid;
            $data[$k]['content_id'] = $conId;
            $data[$k]['ques_id'] = $va['quesId'];
            $data[$k]['quesopt_ids'] = $va['quesOptIds'];
            $data[$k]['ip'] = $ip;
            $data[$k]['join_time'] = date("Y-m-d H:i:s");
        }

        $ret = $quesRecDb->addAll($data);
        if ($ret) {
            echo SUC_OPERATE;
        } else {
            echo ERR_OPERATE;
        }
    }

    /**
     * 获取有图征询详细信息
     */
    public function getConsultPicDetail()
    {
        $id = I("get.id");

        $conDb = M('consult');
        $conOptDb = M('consult_option');

        $ret = $conDb->field('id, content_id, c_title as title, c_pic as pic, c_content as contents')->where('id=%s', array($id))->find();

        $optRet = $conOptDb->field('id, option_desc, vote_number')->where('consult_id=%s', array($id))->select();

        $url = 'http://' . C("SERVER_IP") . ':' . C("SERVER_PORT");

        $ret['pic'] = empty($ret['pic']) ? '' : $url . $ret['pic'];
        $ret['options'] = $optRet;

        echo json_encode($ret);
    }

    /**
     * 无图征询投票
     */
    public function tvVoteConsult()
    {
        $optId = I("get.optId");
        $stbid = I("get.stbid");
        $conId = I("get.conId");

        $ip = get_client_ip(1);

        if (empty($optId) || !check_positive_int($optId)) {
            logs('Controller: Interface | tvVoteConsult | optId error');
            echo 1;
            exit;
        }

        if (empty($stbid)) {
            logs('Controller: Interface | tvVoteConsult | stbid is empty');
            echo 1;
            exit;
        }

        if (empty($conId)) {
            logs('Controller: Interface | tvVoteConsult | conId is empty');
            echo 1;
            exit;
        }

        $where['stb_id'] = array("EQ", $stbid);
        $where['content_id'] = array('EQ', $conId);

        $rDb = M('consult_record');
        $num = $rDb->where($where)->count();

        if ($num == 0) {
            $cDb = M('consult_option');
            $ret = $cDb->where('id=%s', array($optId))->setInc("vote_number", 1);

            if ($ret) {
                $data['content_id'] = $conId;
                $data['option_id'] = $optId;
                $data['stb_id'] = $stbid;
                $data['ip'] = $ip;
                $data['join_time'] = date("Y-m-d H:i:s");

                $rDb->add($data);

                echo 0;
                exit;
            } else {
                echo 1;
                exit;
            }
        } else {
            echo 2;
            exit;
        }
    }

    /**
     * 有图征询投票
     */
    public function tvVoteConsultPic()
    {
        $optId = I("get.optId");
        $conId = I("get.conId");
        $stbid = I("get.stbid");
        $couId = I("get.couId");

        $ip = get_client_ip(1);

        if (empty($optId) || !check_positive_int($optId)) {
            logs('Controller: Interface | tvVoteConsult | optId error');
            echo 1;
            exit;
        }

        if (empty($conId) || !check_positive_int($conId)) {
            logs('Controller: Interface | tvVoteConsult | optId error');
            echo 1;
            exit;
        }

        if (empty($stbid)) {
            logs('Controller: Interface | tvVoteConsult | stbid is empty');
            echo 1;
            exit;
        }

        if (empty($couId) || !check_positive_int($couId)) {
            logs('Controller: Interface | tvVoteConsult | consultId is empty');
            echo 1;
            exit;
        }

        $where['stb_id'] = array("EQ", $stbid);
        $where['consult_id'] = array('EQ', $couId);

        $rDb = M('consult_record');
        $num = $rDb->where($where)->count();

        if ($num == 0) {
            $cDb = M('consult_option');
            $ret = $cDb->where('id=%s', array($optId))->setInc("vote_number", 1);

            if ($ret) {
                $data['content_id'] = $conId;
                $data['consult_id'] = $couId;
                $data['option_id'] = $optId;
                $data['stb_id'] = $stbid;
                $data['ip'] = $ip;
                $data['join_time'] = date("Y-m-d H:i:s");

                $rDb->add($data);

                echo 0;
                exit;
            } else {
                echo 1;
                exit;
            }
        } else {
            echo 2;
            exit;
        }
    }

    /**
     * 获取内容的评论
     */
    public function getComment()
    {
        $conId = $_GET['conId'];

        $url = 'http://' . C("SERVER_IP") . ':' . C("SERVER_PORT");

        $comDb = M('comment');
        $ret = $comDb->alias('c')->field('c.id,c.content_id as conid,c.comment,c.comm_time as time,u.name,u.nickname,u.photo')->join('__MOBILE_USER__ as u ON u.id=c.author')->where('c.content_id=%s', array($conId))->select();

        if ($ret) {
            foreach ($ret as $k => $v) {
                $ret[$k]['comment'] = stripslashes($v['comment']);
                $ret[$k]['photo'] = empty($v['photo']) ? '' : $url . $v['photo'];
            }

            echo json_encode($ret);
        } else {
            echo '[]';
        }
    }

    /**
     * 点赞喜欢的文章
     */
    public function likeContent()
    {
        $conId = $_GET['conId'];

        $conDB = M('content');

        $ret = $conDB->where('id=%s', array($conId))->setInc('click_num', 1);
        $num = $conDB->field('click_num')->where('id=%s', array($conId))->find();

        if ($ret) {
            echo $num['click_num'];
        } else {
            echo 1;
        }
    }

    /**
     * 用户载入提示当前数据变化（操作、审核、发布数据变化提示）
     */
    public function prompt()
    {
        $content_model = M('content');

        $type = session('type');
        $user_id = session('user_id');
        if ($type == '操作员') {
            $con_arr = $content_model->where("operate_user_id='" . $user_id . "' and status='驳回'")->select();
            $title = "驳回条目";
        } else if ($type == '内容审核') {

            $con_arr = $content_model->where("status='待审核'")->select();
            $title = "待审核条目";
        } else if ($type == '内容发布') {
            $con_arr = $content_model->where("status='已审核'")->select();
            $title = "待发布条目";
        }
        $return_arr = array();
        $data = array();
        if (!empty($con_arr)) {
            $data['total'] = count($con_arr);
            $data['rows'] = $con_arr;

            $return_arr['error'] = '0';
            $return_arr['title'] = $title;
            $return_arr['data'] = $data;
        } else {
            $return_arr['error'] = '1';
        }
        echo json_encode($return_arr);
    }


    /**
     * 获取文章所有评论
     * @param content_id int
     * @return mixed
     */
    public function showCommentData()
    {
        $content_id = I('get.content_id');
        $user_type = session('type');
        $comment = M('comment');

        $where = [];
        if ($user_type == '系统总监') {
            $where['status'] = 1;
        }
        if (!empty($name) and !empty($value)) {
            $where[$name] = ['like' => '%' . $value . '%'];
        }

        $where['content_id'] = $content_id;
        $comment_arr = $comment->where($where)->select();
        $arr['total'] = count($comment_arr);
        $page_num = I('post.rows');
        $page = I('post.page');
        $start_num = $page_num * ($page - 1);

        $comment_arr = $comment->where($where)->field(['id', 'author', 'comment', 'comm_time', 'status'])->order("id DESC")->limit($start_num, $page_num)->select();
        if (!empty($comment_arr)) {
            foreach ($comment_arr as $c_key => $c_val) {
                if ($c_val['author'] == 0) {
                    $comment_arr[$c_key]['author_name'] = '手机用户';
                } else {
                    $comment_arr[$c_key]['author_name'] = M('mobile_user')->where(['id' => $c_val['author']])->getField('name');
                }
                $comment_arr[$c_key]['status_type'] = $c_val['status'] == 0 ? '未展示' : '已展示';
            }
        }
        $arr['rows'] = $comment_arr;
        echo json_encode($arr);
    }


    public function createCommentExcel()
    {
        $content_id = I('get.content_id');
        $content_title = M('content')->where(['id' => $content_id])->getField('title');
        $fileName = $content_title . '_评论汇总表.xls';

        $dateFrom = I('get.dateFrom');
        $dateTo = I('get.dateTo');
        $showStatus = I('get.showStatus');

        $data = [];
        $begin_time = $dateFrom . ' 00:00:00';
        $end_time = $dateTo . ' 23:59:59';
        if ($dateFrom == 1 && $dateTo != 1) {
            $data['comm_time'] = ['elt', $end_time];
        } elseif ($dateFrom != 1 && $dateTo != 1) {
            $data['comm_time'] = ['between',[$begin_time, $end_time]];
        } elseif ($dateFrom != 1 && $dateTo == 1) {
            $data['comm_time'] = ['egt', $begin_time];
        }

        if ($showStatus != 2) {
            $data['status'] = ['eq', $showStatus];
        }

        $data['content_id'] = ['eq', $content_id];

        $comment_arr = M('comment')->where($data)->order('id')->select();
        if (!empty($comment_arr)) {
            foreach ($comment_arr as $c_k => $c_v) {
                $excel_arr[$c_k]['id'] = $c_v['id'];
                $excel_arr[$c_k]['title'] = $content_title;
                $excel_arr[$c_k]['author_name'] = $c_v['value'] == 0 ? '手机用户' : M('mobile_user')->where(['id' => $c_v['author']])->getField('name');
                $excel_arr[$c_k]['comment'] = $c_v['comment'];
                $excel_arr[$c_k]['comm_time'] = $c_v['comm_time'];
                $excel_arr[$c_k]['showStatus'] = $c_v['status'] == 0 ? '未展示' : '已展示';
            }
        }

        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();

        vendor("PHPExcel.Writer.Excel5");
        $objWriter=new \PHPExcel_Writer_Excel5($objPHPExcel);

        $letter = array('A','B','C','D','E','F');
        $tableHeader = array('序号','内容标题','评论人','评论内容','评论时间','展示状态');

        //填充表头信息
        for($i = 0;$i < count($tableHeader);$i++) {
            $objPHPExcel->getActiveSheet()->setCellValue("$letter[$i]1","$tableHeader[$i]");
        }


        for ($i = 2;$i <= count($excel_arr) + 1;$i++) {
            $j = 0;
            foreach ($excel_arr[$i - 2] as $key=>$value) {
                $objPHPExcel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
                $j++;
            }
        }

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$fileName.'"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }


    /**
     * 评论增加、修改
     */
    public function commentEditData()
    {
        $flag = I('get.flag');
        $data['comment'] = I('post.comment');
        $data['content_id'] = I('post.content_id');
        $data['comm_time'] = date('Y-m-d H:i:s');

        if ($flag == 1) { // 新增评论
            $data['author'] = 0;
            $ret = M('comment')->add($data);
        } elseif ($flag == 3) { // 修改评论
            $data['id'] = I('post.id');
            $ret = M('comment')->save($data);
        }

        if (!empty($ret)) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }

    /**
     * 评论发布、取消发布
     */
    public function commentAudits()
    {
        $id_str = I('post.str');
        $id_arr = explode(',', $id_str);
        $comment = M('comment');
        foreach ($id_arr as $id_k => $id_v) {
            $c_status = $comment->where(['id' => $id_v])->getField('status');
            $status = $c_status == 0 ? 1 : 0;
            $ret = $comment->where(['id' => $id_v])->setField('status', $status);
        }
        if (!empty($ret)) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }

    /**
     * 评论删除
     */
    public function commentDeletes()
    {
        $id_str = I('post.str');
        $comment = M('comment');
        $map['id'] = ['in', $id_str];
        $ret = $comment->where($map)->delete();
        if (!empty($ret)) createMsg('操作成功', 0);
        else createMsg('操作失败', 1);
    }


    /**
     * @param $_table string 操作的表名
     * @param $_flag string 操作类型：('add', 'del', 'sel', 'update')
     * @param $_status string 操作结果：('true', 'false')
     * @param $_desc string 操作描述
     * @param $_src string 客户端来源：('pc', 'android', 'ios', 'h5')
     * @return mixed
     */
    public function recordOperation($_table, $_flag, $_status, $_desc, $_src)
    {
        $logs = M('logs');

        $data['man_id'] = session('user_id');
        $data['log_desc'] = $_desc;
        $data['oper_type'] = $_flag;
        $data['oper_tablename'] = $_table;
        $data['oper_ret'] = $_status;
        $data['log_ip '] = get_client_ip();
        $data['log_src '] = $_src;
        $data['log_time '] = date('Y-m-d H:i:s');

        $ret = $logs->add($data);
        return $ret;
    }

    /**
     * 批量生成已上线内容的json
     * @param mark string 标识 当为pre时表示生成内容预览json；当为空时表示生成已上线内容的json
     * @return mixed
     */
    public function buildAllContentsJson()
    {
        $mark = I('get.mark');
        $main_arr = [];

        $where['status'] = ['eq', '上线'];
        $where['is_node'] = ['eq', 'y'];
        $where['type'] = ['in', ['图文', '相册', '窗口视频']];
        $contents_arr = M('content')->where($where)->select();
        if (!empty($contents_arr)) {
            foreach ($contents_arr as $c_key => $c_val) {
                $root_region_code = M('tv_node')->where(['node_code' => $c_val['node_id']])->getField('region_id');

                $path = $mark != '' ? '..' . WEB_PATH . $root_region_code . '/' . 'preview_data/' . $c_val['region_id'] : '..' . WEB_PATH . $root_region_code . '/' . 'data/' . $c_val['region_id'];

                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }

                // 构建content内容数组
                $main_arr['id'] = $c_val['id'];
                $main_arr['title'] = $c_val['title'];
                $main_arr['node_name'] = M('tv_node')->where(['node_code' => $c_val['node_id']])->getField('text');
                $main_arr['content'] = $c_val['contents'];

                $video_url = M('type_param')->where(['text' => '视频播放地址'])->getField('value');
                $main_arr['video'] = $c_val['video_path'] == '' ? '' : str_replace('{video}', $c_val['video_path'], $video_url);
                $main_arr['album_arr'] = '';
                if (in_array($c_val['type'], ['相册'])) {
                    $pic_arr = explode('!!', $c_val['path']);
                    $name_arr = explode('!!', $c_val['contents']);
                    foreach ($pic_arr as $key => $value) {
                        $main_arr['album_arr'][$key]['pic'] = $value;
                        $main_arr['album_arr'][$key]['name'] = $name_arr[$key];
                    }
                    $main_arr['content'] = '';
                }
                // 将数据写入json文档中
                $path .= '/' . md5($c_val['id']) . '.js';
                $boole = file_put_contents($path, json_encode($main_arr));
                if (empty($boole)) {
                    echo '批量生成内容json失败！';
                } else {
                    echo '批量生成内容json成功！';
                }
            }
        }
    }
}