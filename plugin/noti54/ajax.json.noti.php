<?php
include_once("_common.php");

// 이 페이지를 볼수 있는 조건
if (!$is_member) {
    die("{\"error\":\"회원만 조회가 가능합니다.\"}");
}

switch ($_REQUEST['w']) {
    case "read":

        $g5_noti_54->read();
        break;

    case "del":

		$g_ids = isset($_REQUEST['g_ids']) ? $_REQUEST['g_ids'] : '';
		$groups_id = isset($_REQUEST['groups_id']) ? $_REQUEST['groups_id'] : '';

        $g5_noti_54->del( $g_ids, $groups_id );
        break;

    case "redirect" :

		$g_ids = isset($_REQUEST['g_ids']) ? $_REQUEST['g_ids'] : '';
		$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';

        $g5_noti_54->redirect( $g_ids, $url );
        break;

    default :
        die("{\"error\":\"\", \"count\": 0, \"response\": 0 }");
}