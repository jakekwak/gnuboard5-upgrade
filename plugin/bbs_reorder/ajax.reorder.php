<?php
include_once("./_common.php");
include_once(G5_ADMIN_PATH."/admin.lib.php");

if(function_exists('auth_check_menu')) {
    auth_check_menu($auth, '300901', 'w');
} else {
    auth_check($auth['300901'], 'w');
}

$arr_ajax_msg = array();
$rtype = isset($_REQUEST['rtype']) ? preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['rtype']) : '';
$page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 0;
$pagenum = isset($_REQUEST['pagenum']) ? (int) $_REQUEST['pagenum'] : 0;

if(! $page ){
    $page = 1;
}
if(! $pagenum ){
    $pagenum = $config['cf_page_rows'] ? (int) $config['cf_page_rows'] : 20;
}

if( $pagenum > 1000 ){
    $arr_ajax_msg['error'] = "게시글 갯수는 1000 건 이하로 요청할수 있습니다.";
    die( json_encode($arr_ajax_msg) );
}

if( $rtype === "board" ){

    if( !$bo_table ){
        $arr_ajax_msg['error'] = "오류 - bo_table값이 없습니다.";
        die( json_encode($arr_ajax_msg) );
    }
    $from_record = ($page - 1) * $pagenum; // 시작 열을 구함
    $write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름

    $sql = " select count(distinct wr_parent) as cnt from $write_table where wr_is_comment = 0 and wr_reply = '' ";
    $row = sql_fetch($sql);
    $total_count = isset($row['cnt']) ? $row['cnt'] : 0;

    $total_page  = ceil($total_count / $pagenum);  // 전체 페이지 계산
    $sql = "select wr_num, wr_subject, wr_parent from $write_table where wr_is_comment = 0 and wr_reply = '' order by wr_num, wr_reply limit $from_record, $pagenum ";
    $result = sql_query($sql);

    $list_text = "";
    $arr_min = array();

    $k = 0;

    while ($row = sql_fetch_array($result)){
        $list_text .= "<li id=\"parent_".$row['wr_parent']."\" data-index=\"".$k."\"><span>".$row['wr_subject']."</span></li>";
        $arr_min[] = abs($row['wr_num']);
        $k++;
    }
    $arr_ajax_msg['error'] = "";
    $arr_ajax_msg['list_text'] = $list_text;

    if( count($arr_min) ){
        $arr_ajax_msg['min_wr_num'] = max($arr_min);
    }

    $arr_ajax_msg['page'] = $page;
    $arr_ajax_msg['pagenum'] = $pagenum;
    $arr_ajax_msg['total_count'] = $total_count;
    $arr_ajax_msg['total_page'] = $total_page;

    die( json_encode($arr_ajax_msg) );

} else if( $rtype === "reorder" ){

    if( !$bo_table ){
        $arr_ajax_msg['error'] = "오류 - bo_table값이 없습니다.";
        die( $arr_ajax_msg['error'] );
    }
    $write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
    $from_record = ($page - 1) * $pagenum; // 시작 열을 구함

    $order_data = isset($_POST['order']) ? $_POST['order'] : '';

    if(! $order_data ){
        $arr_ajax_msg['error'] = "오류 - 필수 데이터가 값이 전송되지 않았습니다.";
        die( $arr_ajax_msg['error'] );
    }

    parse_str($order_data, $data);

    $sql_array_text = array();
    if ( $data && is_array($data) ){

        $arr_parent = $data['parent'];
        
        $min_parent = min($arr_parent);
        $max_parent = max($arr_parent);

        $sql = "select wr_num, wr_parent from $write_table where wr_is_comment = 0 and wr_reply = '' and wr_parent between ".(int) $min_parent." and ".(int) $max_parent." order by wr_num, wr_reply";

        $result = sql_query($sql);
        
        $list = $list_keys = array();

        while( $row=sql_fetch_array($result) ){

            if(! in_array($row['wr_parent'], $arr_parent) ) continue;

            $list[] = $row['wr_parent'];
            $list_keys[] = $row['wr_num'];
        }

        if( count($arr_parent) !== count($list) ){
            $arr_ajax_msg['error'] = "오류 - 요청한 열 갯수와 실제 DB의 열 갯수와 차이가 있습니다. 브라우저를 새로고침 후에 다시 실행해 주세요.";
            die( $arr_ajax_msg['error'] );
        }

        if( $diff_result = array_diff_assoc($arr_parent, $list) ){

            foreach((array) $diff_result as $key => $wr_parent ) 
            {
                
                $change_num = isset($list_keys[$key]) ? abs($list_keys[$key]) : 0;
                
                if( $change_num === 0 ) continue;

                if( isset($list[$key]) && $list[$key] === $wr_parent ) {
                    continue;
                }

                $sql = " select wr2.wr_num, group_concat(distinct wr2.wr_parent SEPARATOR ',') as wr_parents from $write_table wr1 INNER JOIN $write_table wr2 ON ( wr2.wr_num = wr1.wr_num ) where wr1.wr_parent = '$wr_parent' "; //답변글이 혹시 있으면 구해와야 하기 때문에 조회
                $ids = sql_fetch($sql);
                if(isset($ids['wr_parents']) && $ids['wr_parents']){
                    if( abs($ids['wr_num']) != $change_num ){ //바꾸려는 wr_num 값이 같다면 바꾸지 않는다.
                        $sql_array_text[] = "update $write_table set wr_num = '-$change_num' where wr_parent in ( ".$ids['wr_parents']." ) "; //쿼리문을 배열로 저장한다. 이 구문에서 쿼리문을 돌리면 wr_num값이 중복이 될수도 있기 때문에...
                    }
                }
            }

        }
    }

    if( count( $sql_array_text ) ){

        foreach( $sql_array_text as $text ){
            sql_query( $text );
        }

        echo "성공 - 정상적으로 업데이트 되었습니다.";

    } else {

        echo "순서를 바꾸려고 하는 데이터가 없습니다.";

    }

    exit;
}