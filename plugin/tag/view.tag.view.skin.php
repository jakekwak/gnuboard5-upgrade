<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if (!$board['bo_use_tag']) return;

if(!sql_query(" DESC  $g5[hash_tag_table]", false)) {
	$sql_table = "create table $g5[hash_tag_table] (
		ct_idx  int not null auto_increment,
		bo_table varchar(20) not null default '' comment '게시판코드',
		wr_id int not null default '0' comment '게시판시퀀스', 
		ct_tag varchar(100) not null default '' comment '태그', 
		ct_ip varchar(25) not null default '' comment 'ip', 
		ct_regdate datetime not null default '0000-00-00 00:00:00', 
		primary key( ct_idx ) , 
		index  $g5[hash_tag_table]_index1(ct_tag) 
		) comment '태그테이블'";

	sql_query( $sql_table, false );
}

$sql_table	=	"alter table {$write_table} add column tags varchar(200) default '' comment '태그'";
sql_query( $sql_table , false );	

$arrtag = explode(",", $view['tags']);

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
	<?php foreach( $arrtag as $key => $val ){ $val = trim($val);?>
	<a href="<?php echo G5_BBS_URL?>/board.php?bo_table=<?php echo $bo_table?>&amp;sfl=tags&amp;stx=<?php echo $val?>"><?php echo $val?></a>
	<?php }?>
</div>

<!-- //태그목록 -->

<?php }?>