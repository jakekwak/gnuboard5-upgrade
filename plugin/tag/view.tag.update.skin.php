<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$board['bo_use_tag']) return;

if( $wr_id && $_POST['tags'] ){
	$tags = $_POST['tags'];

	$sql_table	=	"update $write_table set tags = '$tags' where wr_id = '$wr_id'";

	sql_query( $sql_table , false );	
	
	$arrtag = explode(",", $tags);

	foreach( $arrtag as $key => $val ){ 
		$val = trim($val);
		$sql_table	=	"insert into $g5[hash_tag_table] set bo_table = '$bo_table', wr_id = '$wr_id', ct_tag = '".$val."', ct_ip = '{$_SERVER['REMOTE_ADDR']}', ct_regdate = now()";

    fb('$sql_table', $sql_table);
		sql_query( $sql_table , false );	
	}
}

