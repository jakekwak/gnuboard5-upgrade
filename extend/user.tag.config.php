<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

define('G5_TAG_DIR',        'tag');
define('G5_TAG_URL',        G5_PLUGIN_URL.'/'.G5_TAG_DIR);
define('G5_TAG_PATH',		G5_PLUGIN_PATH.'/'.G5_TAG_DIR);

// 테이블
define('COMP_TAG', 'comp_tag');

//tag 사용여부
$board['bo_use_tag'] = true;
