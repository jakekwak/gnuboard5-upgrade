<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$board['bo_use_tag']) return;

if(!sql_query(" DESC  $g5[hash_tag_table]", false)) {
	$sql_table = "create table $g5[hash_tag_table] (
		id  int(11) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '' COMMENT '태그', 
		bo_table varchar(20) NOT NULL DEFAULT '' COMMENT '게시판이름',
		ip varchar(25) NOT NULL DEFAULT '' COMMENT 'ip', 
		createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
		updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
		PRIMARY KEY( id ) , 
		INDEX  $g5[hash_tag_table]_index1(name) 
		) COMMENT '태그테이블'";

	sql_query( $sql_table, false );
}

if(!sql_query(" DESC  $g5[tag_write_table]", false)) {
	$sql_table = "create table $g5[tag_write_table] (
		wr_id  int(11) NOT NULL,
		tag_id  int(11) NOT NULL
		) COMMENT '글과 태그 관계테이블'";

	sql_query( $sql_table, false );
}

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

