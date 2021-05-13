<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( ! $is_member) return;
add_stylesheet('<link rel="stylesheet" href="'.$noti_plugin_url.'skin/noti.css">', 1);

$mb_noti_cnt = get_mb_noti_cnt(true);
$noti_nav_classes = array();
if( $mb_noti_cnt ){
	$noti_nav_classes[] = 'noti-in';
}
if( $is_mobile ){
	$noti_nav_classes[] = 'is_mobile';
}
?>
<div id="noti-nav" class="<?php echo implode(' ', $noti_nav_classes); ?>">
	<a href="#0" class="noti-nav-trigger" title="알림"><span class="tnb_nb <?php echo $mb_noti_cnt ? 'is_noti_cnt' : ''; ?>" id="arm_cnt"><?php echo $mb_noti_cnt; ?></span><i class="fa fa-bell-o"></i><span class="xbutton"></span></a>

	<nav id="noti-main-nav">
		<ul class="is_left"></ul>
	</nav>
</div>
<script>
jQuery(document).ready(function($){

	var navigationContainer = $('#noti-nav'),
		mainNavigation = navigationContainer.find('#noti-main-nav ul'),
		is_noti_open = 0,
		groups_id = [],
		noti_plugin_url = "<?php echo G5_PLUGIN_URL.'/noti54'; ?>";
	
	// 새창 환경에서는 작동되지 않게 한다.
	if( $("#gnb_all").length || $("#wrapper").length || $("#hd").length || $("#tnb").length || $("#hd_wrapper").length || $("#ft").length || $("#aside").length ){
		load_noti_ico();
	}

    function arm_cnt_print_fn(count){
        return (parseInt(count) > 9) ? '9+' : parseInt(count);
    }

	function button_box_show($selector, is_hide){
		if( is_hide == "hide" ){
			$selector.data('toggle_enable', false)
			.removeClass('menu-is-open');

			mainNavigation.off('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend').removeClass('is-visible');
		} else {
			$selector.data('toggle_enable', true)
			.addClass('menu-is-open');

			mainNavigation.off('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend').addClass('is-visible');
		}
	}

    $(document).on("click touchend", function(e){
        if ( !$('#noti-nav').has(e.target).length ){
             button_box_show($('.noti-nav-trigger'), 'hide');
        }
    });

	$('.noti-nav-trigger').on('click', function(e){
		e.preventDefault();

		var oThis = $(this);
		
		if (! oThis.data('toggle_enable')) {
			button_box_show(oThis, 'show');
		} else {
			button_box_show(oThis, 'hide');
		}

		var noti_all_txt = "알림 모두보기";

		if ( ! is_noti_open ){
			
			is_noti_open = 1;

			var oDate = new Date(),
				params = { format: 'json', w: 'read', t : oDate.getTime() },
				$next_tag = mainNavigation, inum = 0;

			if( ! $next_tag.children().length ){

				$.getJSON(noti_plugin_url+"/ajax.json.noti.php", params, function(XMLHttpRequest) {
					if (XMLHttpRequest.error) {
						alert(XMLHttpRequest.error);
						return false;
					}
					var $arm_cnt = oThis.find("#arm_cnt"),
						arm_cnt_print = arm_cnt_print_fn(XMLHttpRequest.count);
					
					$arm_cnt.text(arm_cnt_print); //총 갯수 업데이트

					if( XMLHttpRequest.count > 0 ){
						$arm_cnt.removeClass("arm0").addClass("arm1");
					} else {
						$arm_cnt.removeClass("arm1").addClass("arm0");
					}

					if( XMLHttpRequest.count > 0){
						$.each( XMLHttpRequest.response, function(seq){
							var datahtml = [],
								list_data = XMLHttpRequest.response[seq],
								tmp_class = "class=\"redirect_link\"";
							groups_id.push( list_data.g_ids );
							datahtml.push('<li data-g_ids="'+ list_data.g_ids +'" data-from_case="'+list_data.ph_from_case+'" >');
							datahtml.push('<a href="'+ list_data.url +'" ' + tmp_class + '>');
							datahtml.push(list_data.msg);
							datahtml.push('<span class="arm_time">' + list_data.wtime + '</span>');
							datahtml.push('</a>');
							datahtml.push('<a href="javascript:void(0)" class="arm_del" ><i class="fa fa-trash" aria-hidden="true"></i></a>');
							datahtml.push('</li>');
							$next_tag.append( datahtml.join('') );
							inum++;
						});
						var addhtml = [];
						addhtml.push('<li>');
						addhtml.push('<a href="'+noti_plugin_url+'/notiview.php" class="noti_all_btn">'+noti_all_txt+'</a>'); //모두보기링크
						addhtml.push('</li>');
						$next_tag.append( addhtml.join('') );
						$next_tag.find(".arm_del").on("click", {oThis : oThis, next_tag : $next_tag}, delete_noti); //삭제 클릭시 이벤트
						$next_tag.find("a.redirect_link").on("click", {oThis : oThis, next_tag : $next_tag}, noti_redirect); //글을 클릭할 경우 읽음 표시로 바꾼후 리다이렉트 한다.
					} else {
						var datahtml = [];
						datahtml.push('<li><span class="is_empty">새로운 알림이 없습니다</span></li>');
						datahtml.push('<li>');
						datahtml.push('<a href="'+noti_plugin_url+'/notiview.php" class="noti_all_btn">'+noti_all_txt+'</a>'); //모두보기링크
						datahtml.push('</li>');
						$next_tag.append( datahtml.join('') );
					}
				});
			} else {
				if( $next_tag.is(":hidden") ){
					$next_tag.show();
				} else {
					$next_tag.hide();
				}
			}

		}
	});

    function delete_noti(e){
        e.preventDefault();
        var g_ids = $(this).parent().attr("data-g_ids"),
            oDate = new Date(),
            params = { format: 'json', w: 'del', g_ids : g_ids, groups_id : groups_id.join(','), t : oDate.getTime() },
            $el = $(this);
        $.getJSON(noti_plugin_url + "/ajax.json.noti.php", params, function(XMLHttpRequest) {
            if (XMLHttpRequest.error) {
                alert(XMLHttpRequest.error);
                return false;
            } else {
                var $arm_cnt = e.data.oThis.find("#arm_cnt"),
                    arm_cnt_print = arm_cnt_print_fn(XMLHttpRequest.count);
                e.data.oThis.find("#arm_cnt").text(arm_cnt_print); //총 갯수 업데이트
                if( XMLHttpRequest.res_count > 0){
                    $arm_cnt.removeClass("sir_arm0").addClass("sir_arm1");
                    var resulthtml = [];
                    $.each( XMLHttpRequest.response, function(seq){
                        var datahtml = [],
                            list_data = XMLHttpRequest.response[seq],
                            tmp_class = "class=\"redirect_link\"",
                            $OuterDiv = $('<li></li>');
                        groups_id.push( list_data.g_ids );
                        $OuterDiv.attr({ "data-g_ids" : list_data.g_ids, "data-from_case" : list_data.ph_from_case });
                        datahtml.push('<a href="'+ list_data.url +'" ' + tmp_class + '>');
                        datahtml.push(list_data.msg);
                        datahtml.push('<span class="arm_time">' + list_data.wtime + '</span>');
                        datahtml.push('</a>');
                        datahtml.push('<a href="javascript:void(0)" class="arm_del" ><i class="fa fa-trash" aria-hidden="true"></i></a>');
                        $OuterDiv.append( datahtml.join('') );
                        resulthtml.push( $OuterDiv.wrapAll("<li/>").parent().html() );
                    });
                    $el.parent().fadeOut(300, function(){

						var $this = $(this);

                        e.data.next_tag.find("li:last-child").before( resulthtml.join('') ) //여기까지 html을 쓰고
                        .prev().find(".arm_del").on("click", {oThis : e.data.oThis, next_tag : e.data.next_tag}, delete_noti) //재귀호출 삭제문
                        .end().find("a.redirect_link").on("click", {oThis : e.data.oThis, next_tag : e.data.next_tag}, noti_redirect); //글을 클릭할 경우 읽음 표시로 바꾼후 리다이렉트 한다.

						
                    });
                } else {
                    $arm_cnt.removeClass("arm1").addClass("arm0");
                    $el.parent().fadeOut(300, function(){
                        if( $(this).siblings(':visible').length == 1 ){
                            $(this).after('<li><span class="is_empty">새로운 알림이 없습니다</span></li>');
                        }
                    });
                }
            }
        });
        return false;
    }

    function noti_redirect(e){
        e.preventDefault();

        var href = $(this).attr("href"),
            oDate = new Date(),
            g_ids = $(this).parent().attr("data-g_ids"),
            ph_type = $(this).parent().attr("data-from_case"),
            params = { format: 'json', w: 'redirect', g_ids : g_ids, t : oDate.getTime() },
            $el = $(this),
            $blank = null;
        if (e.shiftKey) {
            $blank = "blank";
        }
        if( $blank == "blank"){
            window.open(href);
        }

        if( ph_type == "memo" && !$blank ){
            win_memo( href );
			$blank = true;
        }

        $.getJSON(noti_plugin_url + "/ajax.json.noti.php", params, function(XMLHttpRequest) {
            if (XMLHttpRequest.error) {
                alert(XMLHttpRequest.error);
                return false;
            } else {
                if( XMLHttpRequest.count ){
                    var $arm_cnt = e.data.oThis.find("#arm_cnt"),
                        arm_cnt_print = arm_cnt_print_fn(XMLHttpRequest.count);
                    $arm_cnt.text(arm_cnt_print); //총 갯수 업데이트
                    if( XMLHttpRequest.count > 0 ){
                        $arm_cnt.removeClass("arm0").addClass("arm1");
                    } else {
                        $arm_cnt.removeClass("arm1").addClass("arm0");
                    }
                }
                if( !$blank ){
                    $el.parent().hide();
                    document.location.href = href;
                }
            }
        });
        return false;
    }

	function load_noti_ico() {
		navigationContainer.addClass('is-fixed').find('.noti-nav-trigger').one('webkitAnimationEnd oanimationend msAnimationEnd animationend', function(){
			mainNavigation.addClass('has-transitions');
		});
	}


});
</script>