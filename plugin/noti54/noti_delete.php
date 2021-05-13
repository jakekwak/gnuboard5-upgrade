<?php
include_once("./_common.php");

if( ! class_exists('G5_NOTI_54') ){
	return;
}

if (!$is_member) { 
    alert("회원만 접근이 가능합니다.");
}

$read = preg_replace('/[^0-9a-z]/i', '', trim($_POST['read']));
$p_type = preg_replace('/[^0-9a-z]/i', '', trim($_POST['p_type']));

if( $p_type == "del" || $p_type == "read" ){
    for($i=0;$i<count($_POST['chk_bn_id']);$i++)
    {
        $k = preg_replace('/[^0-9a-z]/i', '', $_POST['chk_bn_id'][$i]);
        $ph_id = preg_replace('/[^0-9a-z\,]/i', '', $_POST['chk_g_ids'][$k]);
        $read_yn = preg_replace('/[^0-9a-z]/i', '', $_POST['chk_read_yn'][$k]);

        if( $p_type == "read" && $read_yn == "Y" ){
            continue;
        }
		
		$g5_noti_54->delete_noti($p_type, $ph_id);
    }
} else if( $p_type == "alldelete" ){
    if (!($token && get_session("noti_delete_token") == $token)){
        alert("토큰 에러로 삭제 불가합니다.");
    }
	
	$g5_noti_54->delete_all_noti($member['mb_id']);

    set_session('noti_delete_token', '');
}

goto_url("./notiview.php?page=$page&read=$read");