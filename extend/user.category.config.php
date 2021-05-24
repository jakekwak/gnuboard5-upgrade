<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

define('G5_CATEGORY_DIR',        'category');
define('G5_CATEGORY_URL',        G5_PLUGIN_URL.'/'.G5_CATEGORY_DIR);
define('G5_CATEGORY_PATH',		G5_PLUGIN_PATH.'/'.G5_CATEGORY_DIR);

// 테이블
$g5['category_table'] = G5_TABLE_PREFIX.'categories'; // 카테고리 테이블\n");
$g5['category_board_table'] = G5_TABLE_PREFIX.'category_board'; // 카테고리와 보드 관계 테이블\n");

//tag 사용여부
$board['bo_use_category'] = true;

if (G5_IS_MOBILE) {
  $categories_skin_path      = get_skin_path('categories', 'theme/basic');
  $categories_skin_url       = get_skin_url('categories', 'theme/basic');
} else {
  $categories_skin_path      = get_skin_path('categories', 'theme/basic');
  $categories_skin_url       = get_skin_url('categories', 'theme/basic');
};

if(!sql_query(" DESC  $g5[category_table]", false)) {
	$sql_table = "create table $g5[category_table] (
		id  int(11) NOT NULL AUTO_INCREMENT,
		parent_id  int(11) NOT NULL,
		name varchar(255) NOT NULL DEFAULT '' COMMENT '카테고리', 
		bo_table varchar(20) NOT NULL DEFAULT '' COMMENT '게시판이름',
		createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
		updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
		PRIMARY KEY( id ) , 
		INDEX  $g5[category_table]_index1(name) 
		) COMMENT '카테고리 테이블'";

	sql_query( $sql_table, false );
}

if(!sql_query(" DESC  $g5[category_board_table]", false)) {
	$sql_table = "create table $g5[category_board_table] (
		wr_id  int(11) NOT NULL,
		category_id  int(11) NOT NULL
		) COMMENT '카테고리와 보드(게시판) 관계테이블'";

	sql_query( $sql_table, false );
}