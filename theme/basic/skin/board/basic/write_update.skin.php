<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$board['bo_use_tag']) return;
include_once(G5_TAG_PATH."/view.tag.update.skin.php");
