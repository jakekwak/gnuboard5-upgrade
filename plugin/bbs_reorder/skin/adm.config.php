<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(function_exists('auth_check_menu')) {
    auth_check_menu($auth, $this->admin_number, 'r');
} else {
    auth_check($auth[$this->admin_number], 'r');
}

$this_plugin_path_url = G5_PLUGIN_URL.'/bbs_reorder/';

add_javascript('<script src="'.$this_plugin_path_url.'skin/jquery.paging.js"></script>', 1);

add_javascript('<script src="https://code.jquery.com/ui/1.12.0/jquery-ui.min.js" integrity="sha256-eGE6blurk5sHj+rmkfsGYeKyZx3M4bG+ZlFyA7Kns7E=" crossorigin="anonymous"></script>', 1);

add_stylesheet('<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">', 1);

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$this_plugin_path_url.'skin/adm.style.css">', 0);

$action_url = $this_plugin_path_url.'ajax.reorder.php';
?>
<div class="bbs_reorder">
	
	<form id="gboard_form" name="gboard" method="get" action="<?php echo $action_url; ?>" class="local_sch02 ">
		<div class="local_sch_div ">
			<input type="hidden" name="rtype" value="board" >
			<select id="select_bo_table" name="bo_table" >
			<option value="">게시판을 선택해주세요.</option>
			<?php 
			foreach( $arr_botable as $v ){ 
			    $selected = '';
			    if( $select_bo_table === $v['bo_table'] ){
			        $selected = "selected='selected'";
			    }
			?>
			<option value="<?php echo $v['bo_table']?>" data-url="<?php echo get_pretty_url($v['bo_table']); ?>" <?php echo $selected?> ><?php echo $v['bo_subject']?></option>
			<?php } ?>
			</select>
			
			페이지번호 <input type="text" name="page" id="go_page_order" size=3  class="frm_input">
			게시물갯수 <input type="text" id="pagenum" name="pagenum" size=3  class="frm_input">
			<button type="submit" class="btn_submit">검색</button>
			<span id="go_board_url" style="display: <?php echo $select_bo_table ? 'inline' : 'none';?>" ><a href="#">게시판 확인하러 가기</a></span>
		</div>
	</form>
	
	<ul id="sortable">
	</ul>
	
	<div class="pagination pagination-centered bst">
		<ul class="comn_paging">
		</ul>
	</div>
	
	<form name="hidden_form">
		<input type="hidden" id="hidden_bo_table" name="hidden_bo_table">
		<input type="hidden" id="hidden_pagenum" name="hidden_pagenum">
		<input type="hidden" id="hidden_page" name="hidden_page">
		<input type="hidden" id="hidden_min_wr_num" name="hidden_min_wr_num">
	</form>
	
	<div id="ajax-response"></div>
	<div id="update-btn">
		<button type="button" id="save-order"> 업데이트 </button>
	</div>

    <div class="local_desc01 local_desc">
        <p>게시판 리스트 정렬 필드는 기본정렬인 wr_num, wr_reply 을 기준으로 정렬합니다.</p>
    </div>
</div>

<script>
var board_sort = {},
    reorder_path_url = "<?php echo $this_plugin_path_url; ?>",
    reorder_plugin_url = "<?php echo $action_url; ?>";

(function($){
    board_sort.fn_able = function( $sortable ){
        if( !$sortable ){
            return false;
        }
        $sortable.sortable({
            "tolerance":"intersect",
            "cursor":"pointer",
            "items":"li",
            "placeholder":"placeholder",
            "nested": "ul",
            "update": function(event, ui) {
                ui.item.parent().children().each(function(index, item){
                    
                    var $item = $(item),
                        othis_index = $item.index();

                    if(othis_index != $item.attr("data-index") ){
                        $item.addClass('highlights');
                    } else {
                        $item.removeClass('highlights');
                    }
                });
            }
        });
        $sortable.disableSelection();
    }
    board_sort.fn_paging = function( hash_val,total_page ){
        $(".pagination .comn_paging").paging({
            current:hash_val ? hash_val : 1,
            max:total_page == 0 || total_page ? total_page : 45,
            length : 5,
            format:'{0}',
            next:'next',
            prev:'prev',
            first:'&lt;&lt;',last:'&gt;&gt;',
            href:'#',
            onclick:function(e,page){
                e.preventDefault();
                $("#go_page_order").val( page );
                $("form#gboard_form").trigger("submit");
            }
        });
    }
    board_sort.loading = function( el, src ){
        if( !el || !src) return;
        $(el).append("<span class='tmp_loading'><img src='"+src+"' title='loading...' ></span>");
    }
    board_sort.loadingEnd = function( el ){
        $(".tmp_loading", $(el)).remove();
    }
    var ajaxurl = reorder_plugin_url;
    $(document).on("click", "#save-order", function() {
        var $up_btn = $(this);
        $up_btn.attr("disabled","disabled");
        if( $("#sortable").is(":empty") || !$("#hidden_bo_table").val() ){
            alert("게시판의 데이터를 로드해주세요.");
            $up_btn.removeAttr("disabled");
            return false;
        }
        $.post( ajaxurl, { rtype:"reorder", bo_table : $("#hidden_bo_table").val(), min_wr_num : $("#hidden_min_wr_num").val(), order: $("#sortable").sortable("serialize") }, function(data) {
            $("#ajax-response").html( data ).show()
            .delay(3000).hide("slow");
            $("#sortable li").each(function(index, item){
                $(this).attr("data-index", index).removeClass("highlights");
            });
            $up_btn.removeAttr("disabled");
        });

    });
    $("#select_bo_table").bind("change", function(e){
        $("#go_page_order").val( 1 );
        if( $(this).val() ){
            $("#go_board_url").show();
        } else {
            $("#go_board_url").hide();
        }
    });
    $(document).on("click", "#go_board_url", function(e){
        e.preventDefault();
        var bo_table_val = $("#select_bo_table").val(),
            $data_url = $("#select_bo_table option:selected").attr("data-url");
        if( bo_table_val ){

            if( $data_url ) {
                window.open( $data_url );
            } else {
                window.open( g5_bbs_url+"/board.php?bo_table="+bo_table_val );
            }
        }
    });
    $("#gboard_form").submit(function(e){
        e.preventDefault();

        var select_bo_table = $("#select_bo_table").val();
        if ( !select_bo_table )
        {
            alert("반드시 게시판을 선택해 주셔야 합니다.");
            return false;
        }

        if( $("#pagenum").val() > 1000 ){
            
            alert("게시글 갯수는 1000 건 이하로 요청할수 있습니다.");
            $("#pagenum").val(1000);
        }

        var $form = $(this);
        $(":submit", $form ).attr("disabled","disabled");
        board_sort.loading("#gboard_form", reorder_path_url+"skin/ajax-loader.gif" ); //로딩 이미지 보여줌
        var params = $(this).serialize();
        $.getJSON(reorder_plugin_url, params, function(HttpRequest) {
            if (HttpRequest.error) {
                alert(HttpRequest.error);
                return false;
            } else {
                var $sortable = $("#sortable");
                $sortable.html( HttpRequest.list_text );
                board_sort.fn_able( $sortable );
                board_sort.fn_paging( HttpRequest.page, HttpRequest.total_page );
                $("#hidden_bo_table").val( select_bo_table );
                $("#hidden_page").val( HttpRequest.page );
                $("#hidden_pagenum").val( HttpRequest.pagenum );
                $("#hidden_min_wr_num").val( HttpRequest.min_wr_num );
                setTimeout(function() {
                    $("html,body").animate({
                        scrollTop: $sortable.offset().top - ($("#hd_top").height() + $("#container_title").height())
                    }, 300 , "swing");
                }, 1);
            }
            board_sort.loadingEnd("#gboard_form"); //로딩 이미지 지움
            $(":submit", $form ).removeAttr("disabled");
        });
    });
})(jQuery);
</script>