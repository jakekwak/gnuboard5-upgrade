<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$board['bo_use_tag']) return;

// 게시판에 tags라는 필드를 추가함.  매번???
$sql_table	=	"ALTER TABLE {$write_table} ADD COLUMN tags varchar(255) DEFAULT '' COMMENT '태그'";
sql_query( $sql_table , false );	

if( $wr_id && $_POST['tags'] ){
	$tags = $_POST['tags'];

  // 게시판 글 $wr_id에 $tags 값을 추가함.
  $sql_table	=	"update $write_table set tags = '$tags' where wr_id = '$wr_id'";
	sql_query( $sql_table , false );	
	
  // ,로 된것을 분리함.
	$arrtag = explode(",", $tags);

	foreach( $arrtag as $key => $val ){ 
		$val = trim($val);
		$sql_table	=	"INSERT INTO $g5[hash_tag_table] SET bo_table = '$bo_table', name = '".$val."', ip = '{$_SERVER['REMOTE_ADDR']}';";
		sql_query( $sql_table , false );	
    $tag_id = $g5['connect_db']->insert_id;	
    $sql_table = "INSERT INTO $g5[tag_write_table] SET wr_id = $wr_id, tag_id = $tag_id;";
		sql_query( $sql_table , false );	
	}
}

