<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if( !$is_member ) return;

ob_start();
?>
	<input type="checkbox" name="mb_is_noti" value="1" id="reg_mb_is_noti" <?php echo ($w=='' || $member['mb_is_noti'])?'checked':''; ?> class="selec_chk">
	<label for="reg_mb_is_noti">
		<span></span>
		<b class="sound_only">알림설정</b>
	</label>
	<span class="chk_li">사이트 내 알림을 사용할 경우 체크하세요.</span>
	<?php if( ! $is_mobile ){ ?>
	<button type="button" class="tooltip_icon"><i class="fa fa-question-circle-o" aria-hidden="true"></i><span class="sound_only">설명보기</span></button>
		<span class="tooltip">사이트 내 알림을 체크 해제시, 알림표시가 화면에 출력되지 않습니다.</span>
	<?php } ?>
<?php
$noti_output = ob_get_contents();
ob_end_clean();
?>
<script>
jQuery(function($){

	var $i_html = <?php echo json_encode($noti_output); ?>;

	var $selectors = null;

	if( $("#reg_mb_open").length ){
		$selectors = $("#reg_mb_open");
	} else if( $("#reg_mb_mailling").length ){
		$selectors = $("#reg_mb_mailling");
	}

    if( $selectors !== null && $selectors.length ){
        var tagname = $selectors.parent().prop('tagName');
        
        var el_html = $('<'+tagname+' />',{
                html: $i_html,
                class: 'chk_box'
            }).insertBefore($selectors.parent());
        
    } else {

		var $el_selectors = null;

		if( $("#register_form").length ){
			$el_selectors = $("#register_form");
		} else if( $("#fregisterform").first().length ){
			$el_selectors = $("#fregisterform").first();
		}
		
		if( $el_selectors !== null && $el_selectors.length ){
			var el_html = $('<li/>',{
					html: $i_html,
					class: 'chk_box'
				}).appendTo($el_selectors);
		}
    }

});
</script>