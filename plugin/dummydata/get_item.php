<?php
include_once('_common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');

if(function_exists('auth_check_menu')){
    auth_check_menu($auth, $dummydata_gnu5->admin_number, 'r');
} else {
    auth_check($auth[$dummydata_gnu5->admin_number], 'r');
}

if (! function_exists('alert_json_print')) {
    function alert_json_print($msg='', $url='', $error=true, $post=false){
        die(json_encode(array('msg' => $msg, 'url' => $url, 'error' => $error, 'post' => $post)));
    }
}

if(! function_exists('yc5_get_import_file')){
    function yc5_get_import_file($img_url, $store_path){
        $fp = fopen($store_path, 'wb');

        if($fp){
            $ch = curl_init($img_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $result = parse_url($img_url);
            curl_setopt($ch, CURLOPT_REFERER, $result['scheme'].'://'.$result['host']);
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0');
            $raw=curl_exec($ch);
            curl_close ($ch);
            if($raw){
                fwrite($fp, $raw);
            }
            fclose($fp);
            if(!$raw){
                @unlink($store_path);
                return false;
            }
            return true;
        }

        return false;
    }
}

if(! function_exists('curl_import_post_return')) {
    function curl_import_post_return($url, $params){

        $args = array(
            'header'=>array(),
            'connect_time_out'=>3,
            'timeout'=>null,
        );

        $query_string = http_build_query($params);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 인증서 체크같은데 true 시 안되는 경우가 많다.
        //curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $args['connect_time_out']); # timeout after 10 seconds, you can increase it
        if( !empty($args['timeout']) ){
            curl_setopt($ch, CURLOPT_TIMEOUT, $args['timeout']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);

        $response = curl_exec($ch);
        $cinfo = curl_getinfo($ch);

        curl_close($ch);

        return $response;

    }
}

$url = 'https://sir.kr/main/api/';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$move_bo_table = isset($_POST['select_bo_table']) ? substr(preg_replace('/[^a-z0-9_]/i', '', $_POST['select_bo_table']), 0, 20) : '';
$q_rows = isset($_POST['select_rows']) ? (int) $_POST['select_rows'] : 0;
$token = isset($_POST['token']) ? preg_replace('/[^0-9a-z_\-\.]/i', '', $_POST['token']) : '';
$categories = (isset($_POST['categories']) && $_POST['categories']) ? implode(',', array_column(json_decode(stripslashes($_POST['categories'])), 'value')) : '';
$is_image_file = (isset($_POST['is_image_file']) && $_POST['is_image_file']) ? 1 : 0;

if($action === 'get_catetory'){

    $params = array('action'=>$action);

    $data = curl_import_post_return($url, $params);

    echo $data;

} else if($action === 'get_writes') {
    
    add_event('alert', 'alert_json_print', 1, 4);   //alert 를 json 형식으로 die 할수 있게 hook을 건다.

    if( ! $move_bo_table ){
        alert('게시판 선택을 해 주세요.', G5_URL);
    }

    $params = array('action'=>$action, 'categories'=>$categories, 'token'=>$token, 'is_image_file'=>$is_image_file, 'q_rows'=>$q_rows, 'g5_url'=>G5_URL);

    $data = curl_import_post_return($url, $params);

    $response = (array) json_decode($data);

    if(! isset($response['msg'])) {
        $response['msg'] = '잘못된 요청입니다.2';
    }

    if($response['msg'] !== 'success') {
        alert($response['msg'], G5_URL);
    }

    if( empty($response['list']) ){
        alert('해당 주제의 글이 없습니다. 다른 주제를 선택해 주세요.', G5_URL);
    }

    $list = array_reverse((array) $response['list']);
    
    $tmp_password = get_encrypt_string(md5(pack('V*', rand(), rand(), rand(), rand())));

    $insert_datas = array();

    foreach((array) $list as $rows ){
        
        $row = (array) $rows;
        
        if (! (isset($row['wr_subject']) && $row['wr_subject'])) continue;
        
        $move_write_table = $g5['write_prefix'] . $move_bo_table;
        
        $sql2 = " select count(*) as cnt from {$move_write_table} where wr_subject = '".addslashes($row['wr_subject'])."' ";
        $row2 = sql_fetch($sql2);

        if(isset($row2['cnt']) && $row2['cnt']) {
            continue;
        }

        $next_wr_num = get_next_num($move_write_table);
        
        $row['wr_ip'] = $_SERVER['SERVER_ADDR'];
        
        $regx_pattern = 'src="(http\:|https\:)?\/\/sir.kr';
        $replace_src = 'src="'.G5_URL;
        
        $ori_wr_content = $row['wr_content'];

        $row['wr_content'] = preg_replace('/'.$regx_pattern.'/i', $replace_src, $row['wr_content']);

        $sql = " insert into $move_write_table
                    set wr_num = '$next_wr_num',
                         wr_reply = '{$row['wr_reply']}',
                         wr_is_comment = '{$row['wr_is_comment']}',
                         wr_comment = '0',
                         wr_comment_reply = '{$row['wr_comment_reply']}',
                         ca_name = '".addslashes($row['ca_name'])."',
                         wr_option = '{$row['wr_option']}',
                         wr_subject = '".addslashes($row['wr_subject'])."',
                         wr_content = '".addslashes($row['wr_content'])."',
                         wr_link1 = '',
                         wr_link2 = '',
                         wr_link1_hit = '0',
                         wr_link2_hit = '0',
                         wr_hit = '1',
                         wr_good = '0',
                         wr_nogood = '0',
                         mb_id = '',
                         wr_password = '{$tmp_password}',
                         wr_name = '".addslashes($row['wr_name'])."',
                         wr_email = '',
                         wr_homepage = '',
                         wr_datetime = '{$row['wr_datetime']}',
                         wr_file = '{$row['wr_file']}',
                         wr_last = '',
                         wr_ip = '{$_SERVER['SERVER_ADDR']}',
                         wr_1 = '',
                         wr_2 = '',
                         wr_3 = '',
                         wr_4 = '',
                         wr_5 = '',
                         wr_6 = '',
                         wr_7 = '',
                         wr_8 = '',
                         wr_9 = '',
                         wr_10 = '' ";

        $result = sql_query($sql, false);
        
        if( $insert_id = sql_insert_id() ){
            
            // 부모 아이디에 UPDATE
            sql_query(" update `{$move_write_table}` set wr_parent = '$insert_id' where wr_id = '$insert_id' ", false);

            // 새글 INSERT
            sql_query(" insert into {$g5['board_new_table']} ( bo_table, wr_id, wr_parent, bn_datetime, mb_id ) values ( '{$move_bo_table}', '{$insert_id}', '{$insert_id}', '".G5_TIME_YMDHIS."', '' ) ", false);

            $insert_datas[] = $insert_id;

            if(isset($row['file']) && $row['file']) {

                $file_path = G5_DATA_PATH.'/file/'.$move_bo_table;
                if(! is_dir($file_path)){
                    // 디렉토리가 없다면 생성합니다. (퍼미션도 변경하구요.)
                    @mkdir($file_path, G5_DIR_PERMISSION);
                    @chmod($file_path, G5_DIR_PERMISSION);
                }

                foreach((array) $row['file'] as $files){
                    
                    $files = json_decode(json_encode($files), true);
                    if(! isset($files['image_type'])) continue;
                    
                    if($files['image_type'] && $files['image_width'] && $files['image_height'] && $files['file']){
                        $img_url = $files['path'].'/'.$files['file'];
                        
                        $copy_file_path = $file_path.'/'.$files['file'];

                        if(yc5_get_import_file($img_url, $copy_file_path)) {

                            $filesize = filesize($copy_file_path);

                            $sql = " insert into {$g5['board_file_table']}
                                        set bo_table = '{$move_bo_table}',
                                             wr_id = '{$insert_id}',
                                             bf_no = '0',
                                             bf_source = '".addslashes($files['source'])."',
                                             bf_file = '".addslashes($files['file'])."',
                                             bf_content = '".addslashes($files['bf_content'])."',
                                             bf_fileurl = '',
                                             bf_thumburl = '',
                                             bf_storage = '',
                                             bf_download = 0,
                                             bf_filesize = '".$filesize."',
                                             bf_width = '".$files['image_width']."',
                                             bf_height = '".$files['image_height']."',
                                             bf_type = '".$files['image_type']."',
                                             bf_datetime = '".G5_TIME_YMDHIS."' ";
                            sql_query($sql, false);

                            break;
                        }

                        
                    }
                }
            }

            // 에디터로 업로드 한 사진 가져오기
            if( $matches = get_editor_image($ori_wr_content, false) ){

                for($i=0; $i<count($matches[1]); $i++) {

                    $p = parse_url($matches[1][$i]);
                    
                    if(isset($p['path']) && strstr($p['path'], '/data/editor/')){
                        if(preg_match("/\.({$config['cf_image_extension']})$/i", $p['path'])) {
                            
                            $tmp_scheme = isset($p['scheme']) ? $p['scheme'] : 'https';

                            $img_url = $tmp_scheme.'://'.$p['host'].$p['path'];

                            preg_match("/\/data\/editor\/([a-z0-9]+)\/([a-z0-9_\.]+\.[a-z]+[^\/])/i", $p['path'], $match);
                            
                            if(isset($match[1]) && isset($match[2]) && $match[1] && $match[2]) {

                                $editor_path = G5_DATA_PATH.'/'.G5_EDITOR_DIR.'/'.$match[1];
                                $editor_save_file_path = $editor_path.'/'.$match[2];

                                if(! is_dir($editor_path) ) {
                                    @mkdir($editor_path, G5_DIR_PERMISSION);
                                    @chmod($editor_path, G5_DIR_PERMISSION);
                                }

                                if(! file_exists($editor_save_file_path))
                                    yc5_get_import_file($img_url, $editor_save_file_path);
                            }

                        }
                    }
                }   // end for

            }   // end if

        }   // end if $insert_id

        //sql_query($sql);
    }

    // 게시판의 글 수
    $sql = " select count(*) as cnt from {$g5['write_prefix']}{$move_bo_table} where wr_is_comment = 0 ";

    $row = sql_fetch($sql, false);
    if(isset($row['cnt']) && $row['cnt']) {
        sql_query(" update {$g5['board_table']} set bo_count_write = '{$row['cnt']}' where bo_table = '$move_bo_table' ", false);
    }

    delete_cache_latest($move_bo_table);

    $result = array(
        'msg'=>$response['msg']
        );

    echo json_encode($result);
}