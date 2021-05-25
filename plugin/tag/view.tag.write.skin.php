<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if (!$board['bo_use_tag']) return;

$sql_table = "SELECT $g5[hash_tag_table].name
  FROM $write_table
  JOIN g5_tag_write ON $write_table.wr_id = g5_tag_write.wr_id
  JOIN $g5[hash_tag_table] ON $g5[hash_tag_table].id = g5_tag_write.tag_id
  WHERE $g5[hash_tag_table].bo_table = '$bo_table' and $write_table.wr_id = $wr_id
  ORDER BY $g5[hash_tag_table].id";
$result = sql_query( $sql_table , false);

$val = '';
for ($i=0; $row=sql_fetch_array($result); $i++) {
  if($i === 0)
    $val = trim($row['name']);
  else
    $val = $val.','.trim($row['name']);
}
?>



<!-- 태그 -->
<div class="bo_w_link write_div">
	<label><i class="fa fa-hashtag" aria-hidden="true"></i><span class="sound_only">태그/TAG</span></label>
	<input type="text" name="tags" id="wr_tags_input" class="frm_input full_input" size="50"value="<?php echo $val?>" placeholder="해시태그 입력 예) 아파치존, 아파치, AAI, UAAI, 서버, 웹패널">
</div>
<!-- //태그 -->
