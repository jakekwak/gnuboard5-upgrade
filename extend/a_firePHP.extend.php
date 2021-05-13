<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 밑의 return; 코드의 주석을 해제하면 사용 안함
//return;

class FIREPHP_DEBUG_CONSOLE {
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

        add_event('sql_query_after', array($this, 'sql_query_after'), 10, 4);

        // 사용안함
        add_event('tail_sub', array($this, 'fn_firephp_console'));

        add_replace('get_permission_debug_show', array($this, 'debug_return'), 10, 2);
    }

    public function debug_return($bool, $member){
        global $is_admin;

        if($is_admin === 'super'){
            return true;
        }

        return $bool;
    }

    public function sql_query_after($result, $sql, $start_time, $end_time){
        global $is_admin;
        
        if($is_admin !== 'super') return false;

        if(!function_exists('fb')){
            include_once(G5_PLUGIN_PATH.'/FirePHPCore/fb.php');
        }

        $executed_time = $end_time - $start_time;
        $show_excuted_time = number_format((float)$executed_time * 1000, 2, '.', '');
        
        try {
            fb( $sql, ' ('.$show_excuted_time.' ms)' );
        } catch (Exception $e) {
        }
    }

    public function fn_firephp_console(){
        
        global $g5, $g5_debug, $is_admin;
        
        if($is_admin !== 'super') return false;

        if(!(isset($g5_debug['sql']) && $g5_debug['sql'])){
            return;
        }

        $event_callbacks = get_hook_datas('event', 1);

        if( isset($event_callbacks['sql_query_after']) ) return;

        if(!function_exists('fb')){
            include_once(G5_PLUGIN_PATH.'/FirePHPCore/fb.php');
        }

        foreach((array) $g5_debug['sql'] as $key=>$query){
            if( empty($query) ) continue;
            
            $executed_time = $query['end_time'] - $query['start_time'];
            $show_excuted_time = number_format((float)$executed_time * 1000, 2, '.', '');
            
            try {
                fb( $query['sql'], ' ('.$show_excuted_time.' ms)' );
            } catch (Exception $e) {
            }
        }
    }
}

$GLOBALS['g5_firephp_debug_console'] = FIREPHP_DEBUG_CONSOLE::getInstance();