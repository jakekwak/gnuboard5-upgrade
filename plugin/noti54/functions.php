<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if( ! function_exists('get_mb_noti_cnt')){
	function get_mb_noti_cnt($is_short=false){
		global $g5, $member, $is_member;

		if( ! $is_member ) return '';

		$noti_cnt = (isset($member['mb_noti_cnt']) && $member['mb_noti_cnt']) ? (int) $member['mb_noti_cnt'] : 0;

		if( $is_short && $noti_cnt > 9 ){
			$noti_cnt = '9+';
		}

		return $noti_cnt;
	}
}