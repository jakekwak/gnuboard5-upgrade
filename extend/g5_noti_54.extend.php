<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

//	return;		// 사용안함시 주석을 풀것

if( defined('G5_IS_ADMIN') ){		// 관리자페이지에서는 사용 안함
	return;
}

define('G5_NOTI_LIMIT_DAYS', 120);		// 알림 보관 기간일, 0 이면 사용안함

include_once(G5_PLUGIN_PATH.'/noti54/classes.php');
include_once(G5_PLUGIN_PATH.'/noti54/functions.php');

$GLOBALS['g5_noti_54'] = G5_NOTI_54::getInstance();