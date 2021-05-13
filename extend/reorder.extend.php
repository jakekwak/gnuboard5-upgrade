<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 아래 return 주석을 풀면 사용안함
//return;

class G5_BBS_NUM_REORDER {

	public $admin_number = 300901;

    // Hook 포함 클래스 작성 요령
    // https://github.com/Josantonius/PHP-Hook/blob/master/tests/Example.php
    /**
     * Class instance.
     */

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public static function singletonMethod()
    {
        return self::getInstance();
    }

    public function __construct() {
        $this->add_hooks();
    }

    public function add_hooks(){
		// 관리자 메뉴 추가
		add_replace('admin_menu', array($this, 'add_admin_menu'), 1, 1);

		// 관리자 페이지 추가
		add_event('admin_get_page_bbs_num_reorder', array($this, 'admin_page_config'), 1, 2);
    }

	public function add_admin_menu($admin_menu){
		
        // 관리자 -> 게시판관리 메뉴의 기본키는 menu300 입니다. ( adm/admin.menu300.php 파일을 참고)
        $admin_menu['menu300'][] = array(
            300901, '게시글 재정렬', G5_ADMIN_URL.'/view.php?call=bbs_num_reorder', 'bbs_num_reorder'
        );

        return $admin_menu;
	}

    public function admin_page_config($arr_query, $token){
        global $is_admin, $auth, $config, $g5;

        $select_bo_table = isset($_REQUEST['tbl_name']) ? preg_replace('/[^0-9a-z_]/i', '', $_REQUEST['tbl_name']) : '';
        $sql = " select bo_table, bo_subject from `{$g5['board_table']}` ";
        $result = sql_query($sql);
        $arr_botable = array();
        while( $row = sql_fetch_array( $result ) ){
            $arr_botable[] = $row;
        }

        include_once( G5_PLUGIN_PATH.'/bbs_reorder/skin/adm.config.php');
    }

}

$GLOBALS['g5_bbs_num_reorder'] = G5_BBS_NUM_REORDER::getInstance();