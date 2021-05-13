<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

class DUMMYDATA_GNU5 {
    private $is_admin_copy = false;
    private $db_table = 'dummydata_config';
    private $config = array();

	public $admin_number = 300921;

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
		add_event('admin_get_page_dummydata_config', array($this, 'dummydata_config'), 1, 2);
    }

	public function add_admin_menu($admin_menu){
		
		$admin_menu['menu300'][] = array(
			$this->admin_number, '글 가져오기', G5_ADMIN_URL.'/view.php?call=dummydata_config', 'dummydata_config'
			);
		
		return $admin_menu;
	}

	public function dummydata_config($arr_query, $token){
		global $is_admin, $auth, $config, $g5;
		
        $sql = " select bo_table, bo_subject from `{$g5['board_table']}` ";
        $result = sql_query($sql);
        $arr_botable = array();

        while( $row = sql_fetch_array( $result ) ){
            $arr_botable[] = $row;
        }
        
		include_once( G5_PLUGIN_PATH.'/dummydata/skin/adm.config.php');
	}

}

$GLOBALS['dummydata_gnu5'] = DUMMYDATA_GNU5::getInstance();