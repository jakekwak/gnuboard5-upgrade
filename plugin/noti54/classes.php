<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

class G5_NOTI_54 {
	
	// 알림 DB 테이블
	private $db_table = 'noti_table';

	public $boards = array();

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
		$this->db_table = G5_TABLE_PREFIX.'noti_table';
        $this->add_hooks();
    }

	public function add_hooks(){
		add_event('tail_sub', array($this, 'noti_init'), 1, 0);

		add_event('comment_update_after', array($this, 'comment_insert'), 1, 5);        // 댓글
		
		add_event('write_update_after', array($this, 'write_insert'), 1, 5);            //글 답변

        add_event('bbs_increase_good_json', array($this, 'bbs_good'), 1, 3);			//좋아요 싫어요

		add_event('memo_form_update_after', array($this, 'memo_update'), 1, 3);			// 쪽지보내기

		add_event('qawrite_update', array($this, 'qawrite_update'), 1, 4);			// 쪽지보내기

		add_event('register_form_after', array($this, 'register_form_after'), 1, 3);

		add_event('register_form_update_after', array($this, 'register_form_update_after'), 1, 2);

		add_event('bbs_delete', array($this, 'bbs_delete'), 1, 2);					// 단일 글삭제시

		add_event('bbs_delete_all', array($this, 'bbs_delete_all'), 1, 2);			// 리스트에서 여러개 글삭제시

		add_event('bbs_delete_comment', array($this, 'bbs_delete'), 1, 2);			// 코멘트 삭제시

		add_event('bbs_new_delete', array($this, 'bbs_new_delete'), 1, 2);			// 새글 삭제시
	}

	public function noti_init(){
		global $g5, $is_member, $member, $is_mobile;
		
		if( ! $is_member ) return;

		$this->db_field_update();
		
		$noti_plugin_url = G5_PLUGIN_URL.'/noti54/';
		
		if( isset($member['mb_is_noti']) && $member['mb_is_noti'] ){
			include_once( G5_PLUGIN_PATH.'/noti54/skin/noti.skin.php' );
		}
	}

    // 게시판 설정값에서 제목값을 리턴
    public function board_get_subject($bo_table){

		if( ! isset($this->boards[$bo_table]) ){
			$this->boards[$bo_table] = get_board_db($bo_table, true);
		}

		return isset( $this->boards[$bo_table]['bo_subject'] ) ? $this->boards[$bo_table]['bo_subject'] : '';
    }

	public function db_create(){

		if(!sql_query(" DESC {$this->db_table} ", false)) {
			$sql = get_db_create_replace("CREATE TABLE IF NOT EXISTS `$this->db_table` (
					  `ph_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					  `ph_to_case` varchar(50) NOT NULL DEFAULT '',
					  `ph_from_case` varchar(50) NOT NULL DEFAULT '',
					  `bo_table` varchar(20) NOT NULL DEFAULT '',
					  `rel_bo_table` varchar(20) NOT NULL DEFAULT '',
					  `wr_id` int(11) NOT NULL DEFAULT 0,
					  `rel_wr_id` int(11) NOT NULL DEFAULT 0,
					  `mb_id` varchar(255) NOT NULL DEFAULT '',
					  `rel_mb_id` varchar(255) NOT NULL DEFAULT '',
					  `rel_mb_nick` varchar(255) DEFAULT NULL,
					  `rel_msg` varchar(255) NOT NULL DEFAULT '',
					  `rel_url` varchar(200) NOT NULL DEFAULT '',
					  `ph_readed` char(1) NOT NULL DEFAULT 'N',
					  `ph_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					  `parent_subject` varchar(255) NOT NULL,
					  `wr_parent` int(11) DEFAULT 0,
					  PRIMARY KEY (`ph_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

			sql_query($sql, false);
		}
	}

    public function db_field_update(){
        global $g5, $is_member, $member;
		
        if( $is_member && $member['mb_id'] && ! isset($member['mb_is_noti']) ){
            
            sql_query("ALTER TABLE `{$g5['member_table']}`
                        ADD `mb_is_noti` tinyint(4) NOT NULL DEFAULT 1,
						ADD `mb_noti_cnt` int(11) NOT NULL DEFAULT 0
						", false);
        }
    }
	
	public function noti_not_read($mb_id){
        global $g5;

        $sql = " select count(*) as cnt from ( select count(*) from ".$this->db_table." where mb_id = '".$mb_id."' and ph_readed = 'N' group by wr_id, ph_from_case , rel_bo_table ) as rowcount "; //알림 총 갯수

        $row = sql_fetch($sql, false);

        return $row['cnt'];
	}

    public function register_form_after($w, $agree, $agree2){
        global $member, $is_member, $g5, $is_mobile;

        if( $w !== 'u' || ! $member['mb_id'] ) return;
        
        include_once( G5_PLUGIN_PATH.'/noti54/skin/register_form.skin.after.php' );
    }

	public function register_form_update_after($mb_id, $w){
		global $member, $is_member, $g5;
        
        if( $w === 'u' ){
            $mb_is_noti = (isset($_POST['mb_is_noti']) && $_POST['mb_is_noti']) ? 1 : 0;
            $sql = " update {$g5['member_table']} set mb_is_noti='$mb_is_noti' where mb_id = '$mb_id' ";
        }

		sql_query($sql, false);
	}

    public function short_get_time($wdate = ""){
        if ( !$wdate ) return '방금';
        $time = time()-strtotime($wdate);
        if (!$time ) return '방금';
        $stat = ' 전';
        if ( $time<0 ) { $time*=-1; $stat = ' 후'; } // $time=abs($time);
        $ago = array();
        if( $time < 172800 ){
            //$ct = array(31536000,2592000,604800,86400,3600,60,1); // 대략(년:365일,월:30일 기준)
            //$tt = array('년','달','주','일','시간','분','초');
            $ct = array(86400,3600,60,1); // 대략(년:365일,월:30일 기준)
            $tt = array('일','시간','분','초');
            foreach ( $ct as $k => $v )
            {
                if ( $n=floor($time/$v) ) {
                $ago[] = $n.$tt[$k];
                $time-=$n*$v;
                }
            }
            return implode(' ',array_slice($ago,0,1)).$stat;
        } else {
            $con_wate = date("m", strtotime($wdate))."월 ".date("d", strtotime($wdate))."일" ;
            return $con_wate;
        }
    }

    public function read($readnum = null, $where_add = "", $from_record = 0, $is_read='n', $is_json=true){
		global $g5, $is_member, $member;

        if( !isset($readnum) ){
            $readnum = 5;
        }

		$sql_search = " where p.mb_id = '".$member['mb_id']."'";
		$sub_sql_search = " where mb_id = '".$member['mb_id']."'";

		if($is_json){
			$group_by_fields = "p.wr_id, p.ph_from_case, p.rel_bo_table";
			$sub_group_by_fields = "wr_id, ph_from_case, rel_bo_table";
		} else {
			$group_by_fields = "p.ph_readed, p.wr_id, p.ph_from_case, p.rel_bo_table";
			$sub_group_by_fields = "ph_readed, wr_id, ph_from_case, rel_bo_table";
		}

		if( $is_read ){
			if ($is_read === 'y') {
				$sql_search .= " and p.ph_readed = 'Y'";
				$sub_sql_search .= " and ph_readed = 'Y'";
			} else if($is_read === 'n') {
				$sql_search .= " and p.ph_readed = 'N'";
				$sub_sql_search .= " and ph_readed = 'N'";
			}
		}

        $total = sql_fetch(" select count(*) as count from ( select count(*) from ".$this->db_table." p $sql_search group by $group_by_fields ) as rowcount ", false);

        $sql = " select p.*, m.mb_nick, p2.num, p2.g_ids, p2.g_rel_mb from ".$this->db_table." p ";

        $sql .= " inner join ( select max(ph_id) as ph_id, count(wr_id) as num, group_concat(ph_id) as g_ids, group_concat(rel_mb_id) as g_rel_mb from ".$this->db_table." $sub_sql_search $where_add group by $sub_group_by_fields ) p2 On p.ph_id = p2.ph_id ";

        $sql .= " left outer join ".$g5['member_table']." m On p.rel_mb_id = m.mb_id $sql_search order by p.ph_datetime desc limit $from_record, $readnum ";	//데이터 최신순

        $result = sql_query($sql, false);
        $response = array();
        for ($i=0; $row=sql_fetch_array($result); $i++){
            $tmp_to_name = $row['mb_nick'] ? $row['mb_nick'] : $row['rel_mb_nick'];
            $tmp_msg = "";
            $tmp_total = $row['num'];
            $tmp_mb_count = count( array_unique( explode("," ,$row['g_rel_mb']) ) ); //참여된 인원에서 중복된 인원이 있으면 뺀다.
            $tmp_total = $tmp_mb_count ? $tmp_mb_count : $tmp_total; //참여된 인원에서 중복된 인원이 있으면 뺀 인원을 대입한다.
            $tmp_add_msg = $tmp_total > 1 ? "외 ".( (int)$tmp_total - 1 )."명이 " : "이 내 ";
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
					
					$wr_board = get_board_db($row['bo_table']);
                    $bo_table_name = strip_tags($wr_board['bo_subject']);
                    $tmp_msg = "<b>".$tmp_to_name."</b>님이 ".$bo_table_name."에 ".$pg_from_case."을 작성하였습니다.";

                    if( $row['ph_to_case'] == 'friend' ){
                        $tmp_msg = '<span class="is_arm_friend"><span class="text">친구</span> '. $tmp_msg.'</span>';
                    }
                break;
                case 'board':
                    $pg_from_case = "글";
                    $tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case."에 ".$pg_from_case."을 남기셨습니다.";
                break;
                case 'comment':
                    $pg_from_case = "댓글";
                    $tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case."에 ".$pg_from_case."을 남기셨습니다.";
                break;
                case 'good':
                    $tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case."을 좋아합니다.";
                break;
                case 'nogood':
                    $tmp_msg = "<b>내</b> ".$pg_to_case."이 싫어요 를 받았습니다.";
                break;
                case 'memo':
                    $pg_from_case = "쪽지";
                    $tmp_msg = "<b>".$tmp_to_name."</b>님으로부터 ".$pg_to_case."가 도착했습니다.";
                    $direct_url = G5_URL.$row['rel_url'];
                break;
                case 'answer':
                case 'reply':
                    $pg_from_case = "답변";
                    $tmp_msg = "<b>".$tmp_to_name."</b>님".$tmp_add_msg.$pg_to_case."에 ".$pg_from_case."을 남기셨습니다.";
                break;
            }

            $add_qry = 'noti='.$row['ph_id'];
			
			if( ! $is_json){
				$response[$i] = $row;
			}
            $response[$i]['ph_id'] = $row['ph_id'];
            $response[$i]['ph_from_case'] = $row['ph_from_case'];
            $response[$i]['msg'] = $tmp_msg;
            $response[$i]['wtime'] = $this->short_get_time($row['ph_datetime']);
            $response[$i]['url'] = $direct_url ? short_url_clean($direct_url, $add_qry) : short_url_clean(G5_URL.$row['rel_url'], $add_qry);
            $response[$i]['g_ids'] = $row['g_ids'];
        }
        $res_count = count($response);

        if(! isset($total['count'])) $total['count'] = 0;

		if( $is_json ){
			if ( 1 > $res_count ) {
				die("{\"error\":\"\", \"count\":\"".$total['count']."\", \"res_count\":\"".$res_count."\", \"response\": 0 }");
			}

			die("{\"error\":\"\", \"count\":\"".$total['count']."\", \"res_count\":\"".$res_count."\", \"response\": ".json_encode($response)." }");
		}

		return array($total['count'], $response);
    }

	public function bbs_new_delete($chk_bn_id, $save_bo_table){		// 새글 삭제시
		global $g5;
		
		$mb_ids = array();

		if( is_array($chk_bn_id) ){
			for($i=0;$i<count($chk_bn_id);$i++){
				
				$k = $chk_bn_id[$i];

				$bo_table = isset($_POST['bo_table'][$k]) ? preg_replace('/[^a-z0-9_]/i', '', $_POST['bo_table'][$k]) : '';
				$wr_id    = isset($_POST['wr_id'][$k]) ? preg_replace('/[^a-z0-9_]/i', '', $_POST['wr_id'][$k]) : 0;

				if( $wr_id && $bo_table ){
					
					// 글 삭제시 읽기 않은 알림이 있다면
					$result = sql_query(" select * from ".$this->db_table." where ph_readed = 'N' and bo_table = '".$bo_table."' and rel_wr_id = '".$wr_id."' ");

					while( $row=sql_fetch_array($result) ){
						
						sql_query(" delete from ".$this->db_table." where ph_id = '".$row['ph_id']."' ", false);
						
						$mb_ids[] = $row['mb_id'];

					}

				}
			}
		}

		if( $mb_ids ){
			$mb_ids = array_unique($mb_ids);
			foreach($mb_ids as $mb_id){
				$this->mb_noti_count_update($mb_id);
			}
		}
	}

	public function bbs_delete_all( $tmp_array, $board ){		// 글 또는 댓글 삭제시
		
		$mb_ids = array();
		
		foreach($tmp_array as $write_wr_id){
			// 글 삭제시 읽기 않은 알림이 있다면

			$result = sql_query(" select * from ".$this->db_table." where ph_readed = 'N' and bo_table = '".$board['bo_table']."' and rel_wr_id = '".$write_wr_id."' ");

			while( $row=sql_fetch_array($result) ){
				
				sql_query(" delete from ".$this->db_table." where ph_id = '".$row['ph_id']."' ", false);
				
				$mb_ids[] = $row['mb_id'];

			}
		}

		if( $mb_ids ){
			$mb_ids = array_unique($mb_ids);
			foreach($mb_ids as $mb_id){
				$this->mb_noti_count_update($mb_id);
			}
		}

	}

	public function bbs_delete($write_id, $board){

		if( is_array( $write_id ) ){
			$delete_id = $write_id['wr_id'];
		} else {
			$delete_id = $write_id;
		}

		$this->bbs_delete_all( array($delete_id), $board );
	}

    public function del($g_ids = null, $groups_id = null){
		global $g5, $member, $is_member;
		
		if( ! $is_member ) return;

        $where_add = "";
        if( !isset($g_ids) ){
            die("{\"error\":\"g_ids값이 없습니다.\"}");
        }
        if( isset($groups_id) ){
            $tmp_groups_id = array_unique( explode("," ,$groups_id) ); //중복된 id가 있다면 뺀다.
            $groups_id = implode("," , $tmp_groups_id);
            $where_add = " and ph_id not in (".$groups_id.") ";
        }
        $result = sql_query(" select ph_id, mb_id from ".$this->db_table." where ph_id in (".$g_ids.") ", false);
        $tmp_ph_id = array();
        while( $row=sql_fetch_array($result) ){
            if ( $row['mb_id'] && $member['mb_id'] == $row['mb_id']){
                array_push( $tmp_ph_id , $row['ph_id'] );
            } else {
                die("{\"error\":\"자신의 알림이 아니므로 삭제할 수 없습니다.\"}");
            }
        }

        if( count($tmp_ph_id) > 0 ){
            $group_ph_id = implode(",", $tmp_ph_id);
            //sql_query(" delete from ".$this->db_table." where ph_id in (".$group_ph_id.") ", false); //알림데이터 해당 행 삭제
            sql_query(" update ".$this->db_table." set ph_readed = 'Y' where ph_id in (".$group_ph_id.") ", false); //원래는 삭제 했었는데 나중에 읽음표시 업데이트문으로 바뀜
			
			$this->mb_noti_count_update($member['mb_id']);
        }

        $this->read('1', $where_add );
    }
	
	public function delete_all_noti($mb_id){
		global $is_member, $g5, $member;

		sql_query(" delete from ".$this->db_table." where mb_id = '".$mb_id."' ", false);
		
		$this->mb_noti_count_update($mb_id);
	}

	public function delete_noti($p_type, $ph_id){
		global $is_member, $g5, $member;

        $sql = " select ph_id, mb_id from ".$this->db_table." where ph_id in (".$ph_id.") ";
        $result = sql_query($sql);
        $tmp_ph_id = array();

        while( $row=sql_fetch_array($result) ){
            if ( $row['mb_id'] && $member['mb_id'] == $row['mb_id']){
                array_push( $tmp_ph_id , $row['ph_id'] );
            }
        }

        if( count($tmp_ph_id) > 0 ){
            $group_ph_id = implode(",", $tmp_ph_id);
            if( $p_type == "del" ){ //삭제를 원할경우
                sql_query(" delete from ".$this->db_table." where ph_id in (".$group_ph_id.") ", false); //알림데이터 해당 행 읽음으로 업데이트
            } else if( $p_type == "read" ){ //읽음 처리를 원할경우
                sql_query(" update ".$this->db_table." set ph_readed = 'Y' where ph_id in (".$group_ph_id.") ", false);
            }
			
			$this->mb_noti_count_update($member['mb_id']);

        }
	}

    public function redirect($g_ids = null, $url = null){
        global $is_mobile, $g5, $member;
		
        if( !isset($g_ids) && !isset($url) ){
            die("{\"error\":\"g_ids값이 없거나 url값이 없습니다.\"}");
        }
        $result = sql_query(" select ph_id, mb_id, bo_table, wr_parent, ph_to_case, ph_from_case from ".$this->db_table." where ph_id in (".$g_ids.") ", false);
        $tmp_ph_id = array();
		$noti_msg_cnt = 0;

        while( $row=sql_fetch_array($result) ){
            if ( $row['mb_id'] && $member['mb_id'] == $row['mb_id']){
                array_push( $tmp_ph_id , $row['ph_id'] );
            } else {
                die("{\"error\":\"자신의 알림이 아니므로 리다이렉트 할수 없습니다.\"}");
            }
            $tmp_bo_table = $row['bo_table'];
            $tmp_wr_parent = $row['wr_parent'];
        }
        if( count($tmp_ph_id) > 0 ){
            $group_ph_id = implode(",", $tmp_ph_id);
            if( $tmp_bo_table && $tmp_wr_parent ){
                $tmp_add_sql = "OR ( bo_table = '$tmp_bo_table' AND wr_parent = '$tmp_wr_parent' AND mb_id = '{$member['mb_id']}' )";
            }
            sql_query(" update ".$this->db_table." set ph_readed = 'Y' where ph_id in (".$group_ph_id.") $tmp_add_sql ", false); //알림데이터 해당 행 읽음으로 업데이트
			
			$noti_msg_cnt = $this->mb_noti_count_update($member['mb_id']);
        }

        die("{\"error\":\"\", \"count\":\"".$noti_msg_cnt."\", \"response\": \"update_success\" }");
    }

	public function qawrite_update($qa_id=0, $write=array(), $w='', $qaconfig){
		global $g5, $is_member, $member, $is_admin;
		
		// 1:1 문의 답변시
		if( $is_admin && $qa_id && $w === 'a' ){
			$qa_id = (int) $qa_id;

			$sql = " select * from {$g5['qa_content_table']} where qa_id = '$qa_id' ";
			
			$qa_write = sql_fetch($sql);

			if ( $qa_write['mb_id'] !== $member['mb_id'] ){
				
				$noti_enable = get_member($qa_write['mb_id'], '*', true);
				
				if( $noti_enable['mb_is_noti'] ){
					$ph_to_case = 'inquire';
					$ph_from_case = 'answer';
					$direct_url = '/'.G5_BBS_DIR.'/qaview.php?qa_id='.$qa_id;
					$wr_name = $member['mb_nick'];
					$qa_answer = isset($_POST['qa_subject']) ? $_POST['qa_subject'] : '';
					
					$sql = " insert into ".$this->db_table." set ph_to_case = '".$ph_to_case."', ph_from_case = '".$ph_from_case."', bo_table = '', rel_bo_table = '', wr_id = '$qa_id', rel_wr_id = '$qa_id', mb_id = '".$qa_write['mb_id']."', rel_mb_id = '".$member['mb_id']."', rel_mb_nick = '$wr_name', rel_msg = '".sql_real_escape_string(cut_str(strip_tags($qa_answer), 70))."', parent_subject = '".sql_real_escape_string(cut_str(strip_tags($qa_write['qa_subject']), 70))."', rel_url = '".$direct_url."', ph_readed = 'N' , ph_datetime = '".G5_TIME_YMDHIS."' ";

					$result = sql_query($sql, false);
					
					if( ! $result ){
						$this->db_create();
					}

					$this->mb_noti_count_update($qa_write['mb_id']);

				}
			}
		}
	}

	public function memo_update($member_list, $str_nick_list, $redirect_url, $memo=''){
		global $g5, $is_member, $member;

		if( ! $is_member ) return;

		if( ! $memo && isset($_POST['me_memo']) ){
			$memo = $_POST['me_memo'];
		}

		if( isset($member_list['id']) && $member_list['id'] ){
			for($i=0;$i<count($member_list['id']);$i++){
				
				$mb_id = $member_list['id'][$i];
				$me_id = isset($member_list['me_id'][$i]) ? (int) $member_list['me_id'][$i] : 0;

				if( ($mb_id === $member['mb_id']) || ! $me_id )	return;

				$noti_enable = get_member($mb_id, '*', true);

				if( $noti_enable['mb_is_noti'] ){
					
					$ph_to_case = $ph_from_case = 'memo';
					$direct_url = "/".G5_BBS_DIR."/memo_view.php?me_id=".$me_id."&kind=recv";
					$wr_name = $member['mb_nick'];
					
					$sql = " insert into ".$this->db_table." set ph_to_case = '".$ph_to_case."', ph_from_case = '".$ph_from_case."', bo_table = '', rel_bo_table = '', wr_id = '$me_id', rel_wr_id = '$me_id', mb_id = '".$mb_id."', rel_mb_id = '".$member['mb_id']."', rel_mb_nick = '$wr_name', rel_msg = '".sql_real_escape_string(cut_str(strip_tags($memo), 70))."', parent_subject = '', rel_url = '".$direct_url."', ph_readed = 'N' , ph_datetime = '".G5_TIME_YMDHIS."' ";

					$result = sql_query($sql, false);
					
					if( ! $result ){
						$this->db_create();
					}

					$this->mb_noti_count_update($mb_id);

				}
			}
		}
	}

	public function mb_noti_count_update($mb_id){
		global $g5, $is_member, $member;
		
		if( defined('G5_NOTI_LIMIT_DAYS') && G5_NOTI_LIMIT_DAYS ){

			$sql_datetime = date("Y-m-d H:i:s", G5_SERVER_TIME - ((int) G5_NOTI_LIMIT_DAYS * 86400));

			sql_query(" delete from ".$this->db_table." where mb_id = '".$mb_id."' and ph_datetime < '".$sql_datetime."'", false);
		}

		$noti_cnt = $this->noti_not_read($mb_id);
		
		$sql = " update ".$g5['member_table']." set mb_noti_cnt = '$noti_cnt' where mb_id = '".$mb_id."' ";
		sql_query($sql, false);
		
		return $noti_cnt;
	}

    public function bbs_good($bo_table, $wr_id, $good){
        global $g5, $is_member, $member;

        $ph_to_case = 'board';
        $ph_from_case = ($good === 'good') ? 'good' : 'nogood';

        $write = get_write(get_write_table_name($bo_table), (int) $wr_id, true);
        
        if( $write['mb_id'] ){
            $noti_enable = get_member($write['mb_id'], '*', true);
            if( $noti_enable['mb_is_noti'] ){ //상대방 회원정보에서 알림상태가 활성인 경우
                $tmp_url = "/".G5_BBS_DIR."/board.php?bo_table=".$bo_table."&wr_id=".$wr_id;
                $sql = " insert into ".$this->db_table." set ph_to_case = '".$ph_to_case."', ph_from_case = '".$ph_from_case."', bo_table = '$bo_table', rel_bo_table = '$bo_table', wr_id = '$wr_id', rel_wr_id = '$wr_id', mb_id = '{$write['mb_id']}', rel_mb_id = '{$member['mb_id']}', rel_mb_nick = '{$member['mb_nick']}', rel_msg = '".sql_real_escape_string(cut_str(strip_tags($write['wr_content']), 50))."', parent_subject = '".sql_real_escape_string(cut_str(strip_tags($write['wr_subject']), 40))."', rel_url = '".$tmp_url."', ph_readed = 'N' , ph_datetime = '".G5_TIME_YMDHIS."' ";
                $result = sql_query($sql, false);
				
				if( ! $result ){
					$this->db_create();
				}

				$this->mb_noti_count_update($write['mb_id']);
            }
        }
    }

	public function write_insert($board, $wr_id, $w, $qstr, $redirect_url){			// 글에 답변을 한 경우
		global $g5, $is_member, $member;

		if( $w == 'r' && isset($_POST['wr_id']) && $_POST['wr_id'] ){
			
			$wr = get_write(get_write_table_name($board['bo_table']), (int) $_POST['wr_id'], true);
			
			if( $wr['mb_id'] && $member['mb_id'] !== $wr['mb_id'] ){		// 원글을 쓴 사람이 회원이면

				$noti_enable = get_member($wr['mb_id'], '*', true);
				if( $noti_enable['mb_is_noti'] ){		//상대방 회원정보에서 알림상태가 활성인 경우

					$ph_to_case = "board";
					$ph_from_case = "reply";
					$tmp_url = "/".G5_BBS_DIR."/board.php?bo_table=".$board['bo_table']."&wr_id=".$wr_id;

					$wr_reply = get_write(get_write_table_name($board['bo_table']), (int) $wr_id, true);

					$tmp_wr_subject = cut_str(strip_tags($wr['wr_subject']), 90);

					if( $is_member ){
						$rel_mb_nick = addslashes(clean_xss_tags($board['bo_use_name'] ? $member['mb_name'] : $member['mb_nick']));
					} else {
						$rel_mb_nick = addslashes($wr_reply['wr_name']);
					}

					$sql = " insert into ".$this->db_table." set ph_to_case = '".$ph_to_case."', ph_from_case = '".$ph_from_case."', bo_table = '".$board['bo_table']."', rel_bo_table = '".$board['bo_table']."', wr_id = '{$wr['wr_id']}', rel_wr_id = '{$wr_reply['wr_id']}', mb_id = '{$wr['mb_id']}', rel_mb_id = '{$wr_reply['mb_id']}', rel_mb_nick = '$rel_mb_nick', rel_msg = '".sql_real_escape_string(cut_str(strip_tags($wr['wr_content']), 70))."', parent_subject = '".sql_real_escape_string($tmp_wr_subject)."', rel_url = '".$tmp_url."', ph_readed = 'N' , ph_datetime = '".G5_TIME_YMDHIS."', wr_parent = '{$wr['wr_id']}' ";
					$result = sql_query($sql, false);
					
					if( ! $result ){
						$this->db_create();
					}

					$this->mb_noti_count_update($wr['mb_id']);

				}   //END IF

			}   //END IF
		}   //END IF
	}   // end function

	public function comment_insert($board, $wr_id, $w, $qstr, $redirect_url){		// 글에 댓글 또는 댓글에 댓글을 한 경우
		global $g5, $is_member, $member, $comment_id; 

		if ( ! ($comment_id && $w === 'c') ) return;

		$bo_table = $board['bo_table'];
		$write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블;
		$wr = get_write($write_table, $wr_id, true);	// 원글을 가져옵니다.

		$request_comment_id = (isset($_POST['comment_id']) && $_POST['comment_id'] ) ? (int) $_POST['comment_id'] : 0;

		$reply_array = $request_comment_id ? get_write($write_table, $request_comment_id, true) : array();	// 부모 코멘트 정보를 가져옵니다.
		$comment_wr = get_write($write_table, $comment_id, true);	// 현재 쓴 코멘트 정보를 가져옵니다.

		//자신이 쓴 코멘트에 코멘트를 남긴것이 아니라면 알림기능에서 사용
		$is_reply_noti = ( isset($reply_array['mb_id']) && $reply_array['mb_id'] !== $member['mb_id'] ) ? true : false;
		$mb_id = $member['mb_id'] ? $member['mb_id'] : '';

		$action1 = "board";
		$action2 = "comment";

		$noti_update_id = array();
		if( $is_member ){
			$rel_mb_nick = addslashes(clean_xss_tags($board['bo_use_name'] ? $member['mb_name'] : $member['mb_nick']));
		} else {
			$rel_mb_nick = addslashes($comment_wr['wr_name']);
		}

		if( ($wr['mb_id'] && $wr['mb_id'] != $member['mb_id']) || $is_reply_noti ){	// 댓글을 남긴 경우
			
			if( isset($reply_array['wr_is_comment']) && $reply_array['wr_is_comment'] ){	// 댓글에 댓글을 남긴 경우

				$ph_to_case = $ph_from_case = $action2;
                $tmp_mb_id = $reply_array['mb_id'] ? $reply_array['mb_id'] : $wr['mb_id'];
                $tmp_wr_id = $reply_array['wr_id'] ? $reply_array['wr_id'] : $wr_id;
                $tmp_wr_subject = cut_str(strip_tags($reply_array['wr_content']), 90);

			} else {	// 글에 댓글을 남긴 경우

                $ph_to_case = $action1;
                $ph_from_case = $action2;
                $tmp_mb_id = $wr['mb_id'];
                $tmp_wr_id = $wr_id;
                $tmp_wr_subject = cut_str(strip_tags($wr['wr_subject']), 90);

			}
            
            if( $tmp_mb_id !== $member['mb_id'] ) {

                $noti_enable = get_member($tmp_mb_id, '*', true);
                if( $noti_enable['mb_is_noti'] ){		//상대방 회원정보에서 알림상태가 활성인 경우

                    $tmp_url = "/".G5_BBS_DIR."/board.php?bo_table=".$bo_table."&wr_id=".$wr_id."#c_".$comment_id;
                    $sql = " insert into ".$this->db_table." set 
                        ph_to_case = '".$ph_to_case."',
                        ph_from_case = '".$ph_from_case."',
                        bo_table = '$bo_table',
                        rel_bo_table = '$bo_table',
                        wr_id = '$tmp_wr_id',
                        rel_wr_id = '$comment_id',
                        mb_id = '$tmp_mb_id',
                        rel_mb_id = '$mb_id',
                        rel_mb_nick = '$rel_mb_nick',
                        rel_msg = '".sql_real_escape_string(cut_str(strip_tags($comment_wr['wr_content']), 70))."',
                        parent_subject = '".sql_real_escape_string($tmp_wr_subject)."',
                        rel_url = '".$tmp_url."',
                        ph_readed = 'N' ,
                        ph_datetime = '".G5_TIME_YMDHIS."',
                        wr_parent = '{$wr['wr_parent']}' 
                        ";

                    $result = sql_query($sql, false);
                    
                    if( ! $result ){
                        $this->db_create();
                    }

                    array_push($noti_update_id, $tmp_mb_id);

                }

            }

			if( isset($reply_array['wr_id']) && $reply_array['wr_id'] && ($wr['mb_id'] && $wr['mb_id'] != $member['mb_id']) ){		//코멘트에서 코멘트를 남긴 경우에는 원글에도 알림 기록을 남긴다
				$ph_to_case = $action1;
				$ph_from_case = $action2;

				if( !($reply_array['mb_id'] === $wr['mb_id']) ){

					$noti_enable = get_member($wr['mb_id'], '*', true);
					if( $noti_enable['mb_is_noti'] ){
						array_push($noti_update_id, $wr['mb_id'] );
					}
				}
				
				$ph_readed = '';

				if( $reply_array['mb_id'] && !strcmp($reply_array['mb_id'], $wr['mb_id']) ){ // 원글을 쓴 회원이 댓글을 써서 그 댓글에 댓글을 다는 경우가 맞다면... sql에서 insert 하지 않는다.
					$ph_readed = 'Y';
				}

				if( $noti_enable['mb_is_noti'] && $ph_readed !== 'Y' ){ //원글을 쓴 상대방의 회원정보에서 알림상태가 활성인 경우

					$tmp_url = "/".G5_BBS_DIR."/board.php?bo_table=".$bo_table."&wr_id=".$wr_id."#c_".$comment_id;

					$sql = " insert into ".$this->db_table." set 
						ph_to_case = '".$ph_to_case."',
						ph_from_case = '".$ph_from_case."',
						bo_table = '$bo_table',
						rel_bo_table = '$bo_table',
						wr_id = '$wr_id',
						rel_wr_id = '$comment_id',
						mb_id = '".$wr['mb_id']."',
						rel_mb_id = '$mb_id',
						rel_mb_nick = '$rel_mb_nick',
						rel_msg = '".sql_real_escape_string(cut_str(get_text($wr_content), 70))."',
						parent_subject = '".sql_real_escape_string(cut_str(strip_tags($comment_wr['wr_content']), 70))."',
						rel_url = '".$tmp_url."',
						ph_readed = 'N',
						ph_datetime = '".G5_TIME_YMDHIS."',
						wr_parent = '{$wr['wr_parent']}' ";

					$result = sql_query($sql, false);
					
					if( ! $result ){
						$this->db_create();
					}
				}

			}	//end if

			if( $noti_update_id && count($noti_update_id) > 0 ){

				for($j= 0; $j < count($noti_update_id); $j++){
					$this->mb_noti_count_update($noti_update_id[$j]);
				}

			}

		}	//end if

	}	// end function
}