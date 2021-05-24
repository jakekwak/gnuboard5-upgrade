<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if (!$board['bo_use_tag']) return;

// 게시글 $view에서 $tags를 분리해서 표시함.
$arrtag = explode(",", $view['tags']);

$sql_table = "SELECT $g5[hash_tag_table].name
  FROM $write_table
  JOIN g5_tag_write ON $write_table.wr_id = g5_tag_write.wr_id
  JOIN $g5[hash_tag_table] ON $g5[hash_tag_table].id = g5_tag_write.tag_id
  WHERE $g5[hash_tag_table].bo_table = '$bo_table' and $write_table.wr_id = $wr_id";
$result = sql_query( $sql_table , false);

if( $view['tags'] ){
?>
<style>
.hash_tag {
    -moz-border-bottom-colors: none;
    -moz-border-left-colors: none;
    -moz-border-right-colors: none;
    -moz-border-top-colors: none;
    background: #f7f7f7 url("<?php echo G5_TAG_URL?>/img/tag.jpg") no-repeat scroll 15px 50%;
    border-color: -moz-use-text-color #e4e4e4 #e4e4e4;
    border-image: none;
    border-style: #ccc solid;
    border-width: medium 1px 1px;
    padding: 15px 37px;
	margin-bottom: 10px;
}

.hash_tag a {
    border: 1px solid #9db4c2;
    color: #9db4c2;
    display: inline-block;
    font-size: 0.92em;
    letter-spacing: -1px;
    padding: 3px 5px;
}

.hash_tag a:hover {
    background: #3baeff none repeat scroll 0 0;
    border: 1px solid #3baeff;
    color: #fff;
    text-decoration: none;
}
</style>
<!-- 태그목록 -->
<div class="hash_tag">  
	<?php 
    for ($i=0; $row=sql_fetch_array($result); $i++) {
      $val = trim($row['name']);
  ?>
	<a href="<?php echo G5_BBS_URL?>/tags.php?q=<?php echo $val?>"><?php echo $val?></a>
	<?php }?>
</div>

<!-- //태그목록 -->

<?php }?>