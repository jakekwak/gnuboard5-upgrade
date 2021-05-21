<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

define('G5_TAG_DIR',        'tag');
define('G5_TAG_URL',        G5_PLUGIN_URL.'/'.G5_TAG_DIR);
define('G5_TAG_PATH',		G5_PLUGIN_PATH.'/'.G5_TAG_DIR);

// 테이블
$g5['hash_tag_table'] = G5_TABLE_PREFIX.'tags'; // 해시 태그 테이블\n");
$g5['tag_write_table'] = G5_TABLE_PREFIX.'tag_write'; // 태그와 게시판 관계 테이블\n");

//tag 사용여부
$board['bo_use_tag'] = true;

if (G5_IS_MOBILE) {
  $tags_skin_path      = get_skin_path('tags', 'theme/basic');
  $tags_skin_url       = get_skin_url('tags', 'theme/basic');
} else {
  $tags_skin_path      = get_skin_path('tags', 'theme/basic');
  $tags_skin_url       = get_skin_url('tags', 'theme/basic');
};

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