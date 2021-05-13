<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(function_exists('auth_check_menu')){
    auth_check_menu($auth, $this->admin_number, 'w');
} else {
    auth_check($auth[$this->admin_number], 'w');
}

if(! function_exists('curl_version')){

    echo '<div>PHP_CURL 이 설치가 안되어 있거나 비활성화 되어 있어서 이 기능을 사용할수 없습니다.</div>';

    return;
}

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_PLUGIN_URL.'/'.G5_DUMMYDATA_DIR.'/skin/adm.style.css">', 0);

add_stylesheet('<link rel="stylesheet" href="'.G5_PLUGIN_URL.'/'.G5_DUMMYDATA_DIR.'/js/tagify.css">', 1);

add_javascript('<script src="'.G5_PLUGIN_URL.'/'.G5_DUMMYDATA_DIR.'/js/jQuery.tagify.min.js"></script>', 12);
?>
<div class="write_import_area">
	<form name="gmail_smtp_config" id="f_import_data" method="post" onsubmit="return f_import_data_submit(this);">
	<input type="hidden" name="action" value="get_writes" >
	<input type="hidden" name="token" value="" id="token">
		<button id="anc_cf_basic" type="button" class="tab_tit close">글 (더미데이터) 가져오기 설정</button>
		<section class="tab_con">
			<h2 class="h2_frm">글 (더미데이터) 가져오기 설정</h2>
            
            <ul class="frm_ul">
                <li class="find-word">
                    <span class="lb_block"><label for="">주제</label>
                    </span>
                    <div id="get-categories-word">
                    </div>
                </li>
                <li class="categories-select-words">
                    <span class="lb_block"><label for="">주제 입력 또는 선택</label>
                        <?php echo help('주제를 입력하거나 위의 주제를 클릭하여 선택할수 있습니다.'); ?>
                    </span>
                    <input type="text" name="categories" value="" class="frm_input" size="50">
                </li>
                <li>
                    <span class="lb_block"><label for="select_rows">가져올 갯수</label>
                        <?php echo help('가져올 게시물 갯수를 선택해 주세요.'); ?>
                    </span>
                    <div>
                        <select id="select_rows" name="select_rows" >
                        <option value="10">10개</option>
                        <option value="20">20개</option>
                        <option value="30">30개</option>
                        </select>
                    </div>
                </li>
                <li>
                    <span class="lb_block"><label for="select_bo_table">게시판 선택</label>
                        <?php echo help('데이터를 적용할 게시판을 선택해 주세요.'); ?>
                    </span>
                    <select id="select_bo_table" name="select_bo_table" >
                    <option value="">선택해주세요.</option>
                    <?php 
                    foreach( $arr_botable as $v ){ 
                    ?>
                    <option value="<?php echo $v['bo_table']?>" data-url="<?php echo get_pretty_url($v['bo_table']); ?>"><?php echo $v['bo_subject']?></option>
                    <?php } ?>
                    </select>
                    <span id="go_board_url" ><a href="#">게시판 확인하러 가기</a></span>
                </li>
                <li>
                    <button type="submit" class="btn btn_03">글 (더미데이터) 가져오기</button>

                    <div class="loading">
                    </div>
                </li>
            </ul>

		</section>
	</form>
</div>
<script>

var import_ajax_url = "<?php echo G5_PLUGIN_URL.'/'.G5_DUMMYDATA_DIR.'/get_item.php'; ?>";
var is_submit_ing = false;

function f_loading(is_show){
    
    var $loading = jQuery(".write_import_area .loading"),
        img_url = "<?php echo G5_PLUGIN_URL.'/'.G5_DUMMYDATA_DIR.'/skin/loading.gif'; ?>";

    if(is_show){
        is_submit_ing = true;
        $loading.html("<img src='"+img_url+"' />데이터를 가져오는 중입니다...");
    } else {
        is_submit_ing = false;
        $loading.html("");
    }
}

function f_import_data_submit(f){
    
    if( is_submit_ing ){
        return false;
    }

    var select_bo_table = f.select_bo_table.value;

    if(! select_bo_table){
        alert("반드시 게시판을 선택해야 합니다.");
        f.select_bo_table.focus();
        return false;
    }
    
    f_loading(true);

    $.ajax({
        type: "POST",
        url: import_ajax_url,
        dataType: "json",
        data: jQuery(f).serialize() + "&token="+localStorage.token,
        success: function(data)
        {
            f_loading(false);

            if(data.msg == "success") {
                alert("성공 : 완료되었습니다.");
            } else {
                alert("실패 : "+data.msg);
            }
        },
        error : function(request, status, error){
            f_loading(false);
            alert(request.responseText);
        }
    });

	return false;
}

(function($){
    
    $("#select_bo_table").bind("change", function(e){
        if( $(this).val() ){
            $("#go_board_url").show();
        } else {
            $("#go_board_url").hide();
        }
    });
    
    $("#go_board_url").hide();
    $(document).on("click", "#go_board_url", function(e){
        e.preventDefault();
        var bo_table_url = $("#select_bo_table option:selected").attr("data-url");
        if( bo_table_url ){
            window.open( bo_table_url );
        }
    });

    $.ajax({
        url: import_ajax_url,
        type: "POST",
        data: {
            "action" : "get_catetory",
        },
        dataType: "json",
        cache : false,
        async: false,
        success: function(data, textStatus) {
            if(data.msg == "success") {
                var categories = data.categories;

                for (i = 0; i < categories.length; i++) {
                    var html = $("<span></span>").addClass("word").text(categories[i]);

                    $("#get-categories-word").append(html);
                }

                localStorage.token = data.token;

            } else {
                alert(data.msg);
            }
        },
        error : function(request, status, error){
            alert(request.responseText);
        }
    });

    var tag_input = "input[name=categories]";

    $(tag_input).tagify().on("add", function(e, tagData){
    });

    $(".find-word").on("click", ".word", function(e){
        var $text = $(this).text();
        $(tag_input).data("tagify").addTags($text);
    });


})(jQuery);
</script>