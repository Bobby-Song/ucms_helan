<?php
namespace Systems\Controller;

use Think\Controller;

class SongController extends Controller
{
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
     * [生成内容页JSON]
     *
     */
    public function buildContentJson($_id)
    {
        $this->sessionState();

        $main_arr = [];

        // 通过content id 获取content信息
        $con_arr = M('content')->where(['id' => $_id])->find();

        // 获取根region code
        $root_region_id = M('tv_node')->where(['id' => $con_arr['node_id']])->getField('region_id');
        $root_region_code = M('region')->where(['id' => $root_region_id])->getField('code');

        // 生成json的路径
        $path = '..' . WEB_PATH . $root_region_code . '/' . 'data/' . $con_arr['region_id'];
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        // 构建content内容数组
        $main_arr['title'] = $con_arr['title'];
        $main_arr['node_name'] = M('tv_node')->where(['id' => $con_arr['node_id']])->getField('text');
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


}