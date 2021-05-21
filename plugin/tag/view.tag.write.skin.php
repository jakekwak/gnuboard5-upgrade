<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if (!$board['bo_use_tag']) return;
?>

<!-- 태그 -->
<div class="bo_w_link write_div">
	<label><i class="fa fa-hashtag" aria-hidden="true"></i><span class="sound_only">태그/TAG</span></label>
	<input type="text" name="tags" id="wr_tags_input" class="frm_input full_input" size="50"value="<?php echo $write['tags']?>" placeholder="해시태그 입력 예) 아파치존, 아파치, AAI, UAAI, 서버, 웹패널">
</div>
<!-- //태그 -->
