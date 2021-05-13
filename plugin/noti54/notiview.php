<?php
include_once("_common.php");

if( ! class_exists('G5_NOTI_54') ){
	return;
}

set_session('noti_delete_token', $token = uniqid(time()));

$read = isset($_REQUEST['read']) ? preg_replace('/[^a-zA-Z0-9]+/', '', $_REQUEST['read']) : '';
$is_read = 'all';

if (!$is_member) {
    alert('회원이시라면 로그인 후 이용해 주십시오.', G5_BBS_URL."/login.php?url=".urlencode("{$_SERVER['REQUEST_URI']}"));
}

add_stylesheet('<link rel="stylesheet" href="'.G5_PLUGIN_URL.'/noti54/skin/noti.css">', 1);

switch ($read) {
    case "y" :
        $g5['title'] = "읽은 알림";
		$is_read = 'y';
        break;
    case "n" :
        $g5['title'] = "안 읽은 알림";
		$is_read = 'n';
        break;
    default :
        $g5['title'] = "전체 알림";
}

$noti_pid_class = 'noti_armv';

$page_rows = $config['cf_page_rows'] ? (int) $config['cf_page_rows'] : 20;

if (!$page) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $page_rows; // 시작 열을 구함

$datas = $g5_noti_54->read($page_rows, '', $from_record, $is_read, false);
$total_count = $datas[0];
$response = $datas[1];
$total_page  = ceil($total_count / $page_rows);  // 전체 페이지 계산

include_once(G5_PATH.'/head.php');
?>
<div class="noti_lc noti_lc02 <?php echo $is_mobile ? 'is_mobile' : ''; ?>">
    <p>총 <strong><?php echo number_format($total_count)?></strong> 건<?php if(defined('G5_NOTI_LIMIT_DAYS') && G5_NOTI_LIMIT_DAYS){ ?>, 알림 보관 기간은 <strong><?php echo G5_NOTI_LIMIT_DAYS; ?></strong>일입니다.<?php } ?></p>
    <?php if ($total_count) { ?>
    <span class="form_right">
        <form method="post" action="noti_delete.php" onsubmit="return alldelete_submit(this);">
            <input type="hidden" name="p_type" value="alldelete">
            <input type="hidden" name="token" value="<?php echo $token;?>">
            <button type="submit" class="all_del_btn m_btn m_btn_02">모든알림삭제</button>
        </form>
    </span>
    <?php } ?>
</div>

<form name="fnewlist" method="post" id="noti_armv" class="<?php echo $is_mobile ? 'is_mobile' : ''; ?>" action="#" onsubmit="return fnew_submit(this);">
<input type="hidden" name="read"      value="<?php echo $read; ?>">
<input type="hidden" name="page"     value="<?php echo (int) $page; ?>">
<input type="hidden" name="pressed"  value="">
<input type="hidden" name="p_type" value="" >

<div class="noti_bw02 noti_bw">
    <button type="button" class="all_chk noti_b01_adm  m_btn_01">전체선택</button>
    <input type="submit" value="선택삭제" class="noti_b01_adm  m_btn_01" data-type="del" >
    <input type="submit" value="읽음표시" class="noti_b01_adm  m_btn_01" data-type="read" >
</div>

<div class="noti_bw03 noti_bw">
    <button type="button" id="armv_all" class="noti_b01 ">전체보기</button>
    <button type="button" id="armv_read" class="noti_b01">읽은알림</button>
    <button type="button" id="armv_yet" class="noti_b01">안읽은알림</button>
</div>

<ul class="pushmsg_list armv_list">
    <?php
	$i = 0;
    foreach($response as $row){

        $tmp_to_name = $row['mb_nick'] ? $row['mb_nick'] : $row['rel_mb_nick'];

        $tmp_msg = "";
        $tmp_total = $row['num'];
        $tmp_mb_count = count( array_unique( explode("," ,$row['g_rel_mb']) ) ); //참여된 인원에서 중복된 인원이 있으면 뺀다.
        $tmp_total = $tmp_mb_count ? $tmp_mb_count : $tmp_total; //참여된 인원에서 중복된 인원이 있으면 뺀 인원을 대입한다.
        $tmp_add_msg = (int)$tmp_total > 1 ? "외 ".( (int)$tmp_total - 1 )."명이 " : "이 내 ";
        $row['subject'] = $row['parent_subject'] ? "[".cut_str($row['parent_subject'], 70)."] " : "";
        $direct_url = '';

        switch($row['ph_to_case']) {
            case 'board':
                $pg_to_case = "글";
            break;
            case 'comment':
                $pg_to_case = "댓글";
            break;
            case 'memo':
                $pg_to_case = "쪽지";
            break;
            case 'answer':
                $pg_to_case = "답변";
            break;
			case 'inquire':
				$pg_to_case = "문의";
            break;
            case 'bbs':
                $pg_to_case = "게시판";
            break;
        }
        switch($row['ph_from_case']) {
            case 'write':
                $pg_from_case = "글";
                $bo_table_name = $g5_noti_54->board_get_subject($row['bo_table']);
                $tmp_msg = "<b>".$tmp_to_name."</b>님이 ".$bo_table_name."에 $pg_from_case ".$row['subject']."을 작성하였습니다.";
            break;
            case 'board':
                $pg_from_case = "글";
                $tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case."{$row['subject']}에 ".$pg_from_case."을 남기셨습니다.";
            break;
            case 'comment':
                $pg_from_case = "댓글";
                $bo_table_name = $g5_noti_54->board_get_subject($row['bo_table']);
				$tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case.$row['subject']."에 ".$pg_from_case."을 남기셨습니다. <em>".$row['num']."</em>";

            break;
            case 'good':
                $tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case."{$row['subject']}을 좋아합니다. <em>".$row['num']."</em>";
            break;
            case 'nogood':
                $tmp_msg = "<b>내</b> ".$pg_to_case."{$row['subject']}이 싫어요 를 받았습니다.";
            break;
            case 'memo':
                $pg_from_case = "쪽지";
                $tmp_msg = "<b>".$tmp_to_name."</b>님으로부터 ".$pg_to_case."가 도착했습니다.";
            break;
			case 'answer':
            case 'reply':
                $pg_from_case = "답변";
                $tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case."{$row['subject']}에 ".$pg_from_case."을 남기셨습니다.";
            break;

        }
        $row['g_ids'] = $row['g_ids'];
        $row['ph_from_case'] = $row['ph_from_case'];
        $row['msg'] = $tmp_msg;
        $row['wtime'] = $g5_noti_54->short_get_time($row['ph_datetime']);
        $row['num'] = $row['num'];
        $row['url'] = $direct_url ? short_url_clean($direct_url) : short_url_clean(G5_URL.$row['rel_url']);
        $row['ph_readed'] = $row['ph_readed'];
        $row['readed'] = $row['ph_readed'] == "Y" ? "읽음" : "읽기 전";
        $row['class'] = $row['ph_readed'] == "Y" ? "list_read" : "";
        $row['mreaded'] = $row['ph_readed'] == "Y" ? "<i class=\"fa fa-envelope-open-o\" ></i>" : "<i class=\"fa fa-envelope\"></i>";

    ?>
    <li data-from_case="<?php echo $row['ph_from_case']?>">
        <span class="list_chk">
            <label for="chk_bn_id_<?php echo $i; ?>" class="noti_sr"><?php echo $i?>번</label>
            <input type="checkbox" name="chk_bn_id[]" value="<?php echo $i?>" id="chk_bn_id_<?php echo $i; ?>">
        </span>
        <input type="hidden" name="chk_g_ids[<?php echo $i?>]" class="hidden_ids" value="<?php echo $row['g_ids']?>" >
        <input type="hidden" name="chk_read_yn[<?php echo $i?>]" value="<?php echo $row['ph_readed']; ?>" >


    <?php if ($is_mobile) { ?>
        <a href="<?php echo $row['url']; ?>" class="<?php echo $row['class']; ?> list_link">
            <span class="list_stat"><?php echo $row['mreaded']; ?></span>
            <span class="list_tit"><?php echo $row['msg']; ?></span>
            <span class="list_time"><?php echo $row['wtime']; ?></span>
        </a>
        <a href="javascript:void(0);" class="list_del"><i class="fa fa-trash-o" aria-hidden="true"></i></a>

    <?php } else { ?>
        <a href="<?php echo $row['url']; ?>" class="<?php echo $row['class']; ?> list_link">
            <span class="list_time"><?php echo $row['wtime']; ?></span>
            <span class="list_stat"><?php echo $row['readed']; ?></span>
            <span class="list_tit"><?php echo $row['msg']; ?></span>
        </a>
        <a href="javascript:void(0);" class="list_del" title="알림삭제"><i class="fa fa-trash" aria-hidden="true"></i></a>

    <?php } ?>


    </li>
    <?php
	$i++;

    }	//end foreach

    if ($i == 0) {
        echo '<li id="list_empty">알림이 없습니다.</li>';
    }

    $query_string = preg_replace("/&?page\=\d+/", "", clean_query_string($_SERVER['QUERY_STRING']));
    $write_pages = get_paging($config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$query_string&amp;page=");
    ?>
</ul>


<div class="noti_bw02 noti_bw">
    <button type="button" class="all_chk noti_b01_adm m_btn_01">전체선택</button>
    <input type="submit" value="선택삭제" class="noti_b01_adm m_btn_01" data-type="del" >
    <input type="submit" value="읽음표시" class="noti_b01_adm m_btn_01" data-type="read" >
</div>

</form>

<?php if ($write_pages) {?>
<!-- 페이지 -->
<div class="noti_pg_wrap <?php echo $is_mobile ? 'is_mobile' : ''; ?>"><?php echo $write_pages?></div>
<?php } ?>

<script>
(function($){

	var noti_plugin_url = "<?php echo G5_PLUGIN_URL; ?>/noti54";

    function noti_redirect(e){
        var href = $(this).attr("href"),
            g_ids = $(this).siblings(".hidden_ids").val(),
            ph_type = $(this).parent().attr("data-from_case"),
            params = { format: 'json', w: 'redirect', g_ids : g_ids },
            $el = $(this),
            $blank = null;
        if (e.shiftKey) {
            $blank = "blank";
        }
        if( $blank == "blank"){
            window.open(href);
        }

        if( ph_type == "memo" && !$blank ){		// 메모는 새창으로
            win_memo( href );
			$blank = true;
        }

        if( $el.hasClass('list_read') ){ //읽음표시 클래스를 가지고 있다면
            if( !$blank ){
               document.location.href = href;
            }
        } else { //읽음표시 클래스를 가지고 있지 않다면 읽음표시로 업데이트 해준후 리다이렉트 한다.
            $.getJSON(noti_plugin_url + "/ajax.json.noti.php", params, function(XMLHttpRequest) {
                if (XMLHttpRequest.error) {
                    alert(XMLHttpRequest.error);
                    return false;
                } else {
                    if(XMLHttpRequest.response == "update_success"){
                        $el.addClass("list_read")
                        .find(".list_stat").text("읽음");
                    }
                    if( !$blank ){
                        document.location.href = href;
                    }
                }
            });
        }
        return false;
    }
    $('.all_chk').bind("click", function(){
        if (!$(this).data('toggle_enable')) {
            $(this).data('toggle_enable', true);
        } else {
            $(this).data('toggle_enable', false);
        }
        $('[name="chk_bn_id[]"]').attr('checked', $(this).data('toggle_enable') );
    });
    $('.pushmsg_list > li a.list_link').on("click", noti_redirect); //리다이렉트 구문
    $('.pushmsg_list > li a.list_del').on("click", function(e){ //개별삭제시
        document.pressed = "삭제";
        $parent = $(this).parent("li");
        $('[name="chk_bn_id[]"]').attr('checked', false);
        $parent.find('[name="chk_bn_id[]"]').attr('checked', true);
        $("form[name='fnewlist']")
        .find("input[name='p_type']").val("del")
        .end()
        .trigger("submit");
    });
    $('#armv_all').bind('click', function(e){ //전체보기 클릭
        var url = "<?php echo $_SERVER['PHP_SELF'].'?page='.$page?>";
        document.location.href = url;
    });
    $('#armv_read').bind('click', function(e){ //읽은 알림 클릭
        var url = "<?php echo $_SERVER['PHP_SELF'].'?page='.$page.'&read=y'?>";
        document.location.href = url;
    });
    $('#armv_yet').bind('click', function(e){ //안 읽은 알림 클릭
        var url = "<?php echo $_SERVER['PHP_SELF'].'?page='.$page.'&read=n'?>";
        document.location.href = url;
    });

    $("form[name='fnewlist'] input[type='submit']").bind("click", function(e){
        e.preventDefault();
        var p_type = $(this).attr("data-type")
            $form = $("form[name='fnewlist']");
        if( !p_type ){
            alert('어트리뷰티 data-type 빼먹었음 ㅠㅠ');
            return false;
        }
        document.pressed = $(this).val();
        $form.find("input[name='p_type']").val( p_type );
        if( p_type ){
            $form.submit();
        }
    });
})(jQuery);

function fnew_submit(f)
{
    f.pressed.value = document.pressed;

    var cnt = 0;
    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_bn_id[]" && f.elements[i].checked)
            cnt++;
    }

    if (!cnt) {
        alert(document.pressed+"할 알림을 하나 이상 선택하세요.");
        return false;
    }
    if( f.p_type.value == "del" ){
        if (!confirm("선택한 알림을 정말 "+document.pressed+" 하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다")) {
            return false;
        }
    }

    f.action = "./noti_delete.php";

    return true;
}

function alldelete_submit(f){
    if (!confirm("모든 알림을 정말 삭제 하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다")) {
        return false;
    }

    return true;
}

</script>

<?php
include_once(G5_PATH.'/_tail.php');