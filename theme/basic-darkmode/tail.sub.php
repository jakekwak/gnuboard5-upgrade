<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<?php if ($is_admin == 'super') {  ?><!-- <div style='float:left; text-align:center;'>RUN TIME : <?php echo get_microtime()-$begin_time; ?><br></div> --><?php }  ?>

<!-- ie6,7에서 사이드뷰가 게시판 목록에서 아래 사이드뷰에 가려지는 현상 수정 -->
<!--[if lte IE 7]>
<script>
$(function() {
    var $sv_use = $(".sv_use");
    var count = $sv_use.length;

    $sv_use.each(function() {
        $(this).css("z-index", count);
        $(this).css("position", "relative");
        count = count - 1;
    });
});
</script>
<![endif]-->

<?php run_event('tail_sub'); ?>

<script type="text/javascript">
    function preloadFunc()
    {
      const night = new Night2({
        divId: 'mydarkmode',
        lightClass: 'light',
        darkClass: 'dark',
        auto: true,
        intervalForCheckSun: 5, // 5분마다 체크
        intervalForTime: 60, // 60분마다 체크
        offset: 30, // 분
        onToggle() {
          // console.log('onToggle');
        },
        onAuto() {
          // console.log('onAuto');
        },
        onLight() {},
        onDark() {}
      });
    }
    window.onpaint = preloadFunc();
</script>

</body>
</html>
<?php echo html_end(); // HTML 마지막 처리 함수 : 반드시 넣어주시기 바랍니다.