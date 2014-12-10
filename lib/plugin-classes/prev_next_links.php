<?php

class VHub_Video_Nav_Links {

	protected $prefix = VHub_Prefix;

	protected $post_type;

	protected $filter_date_range = array(
			'order_type' => '',
			'date_filter' => '',
			'date_filter_fixed' => '',
			'date_filter_before' => '',
			'date_filter_after' => '',
			'date_filter_last_days' => 5,
			'youtube_time_start' => 'T00:00:01.000Z',
			'youtube_time_end' => 'T23:59:59.000Z'
		);

	private function __construct() {
		$date_ranges = new VHub_Video_DateRange();
		$this->filter_date_range = $date_ranges->get_date_range();

		$this->post_type = $this->prefix . 'videos';
	}

	public static function add_filters() {
		$links = new self();

		add_filter( 'get_next_post_sort', array($links, 'post_sort_next') );
		add_filter( 'get_previous_post_sort', array($links, 'post_sort_previous') );

		add_filter( 'get_next_post_where', array($links, 'post_where_next') );
		add_filter( 'get_previous_post_where', array($links, 'post_where_previous') );

		add_filter( 'get_next_post_join', array($links, 'post_join') );
		add_filter( 'get_previous_post_join', array($links, 'post_join') );
	}

	public static function remove_filters() {
		$links = new self();

		remove_filter( 'get_next_post_sort', array($links, 'post_sort_next') );
		remove_filter( 'get_previous_post_sort', array($links, 'post_sort_previous') );

		remove_filter( 'get_next_post_where', array($links, 'post_where_next') );
		remove_filter( 'get_previous_post_where', array($links, 'post_where_previous') );

		remove_filter( 'get_next_post_join', array($links, 'post_join') );
		remove_filter( 'get_previous_post_join', array($links, 'post_join') );
	}

	public function post_sort_next( $orderby ) {
		return $this->post_sort( $orderby, 'next' );
	}

	public function post_sort_previous( $orderby ) {
		return $this->post_sort( $orderby, 'previous' );
	}

	protected function post_sort( $orderby, $type ) {
		global $wpdb;

		$youtube_date = $this->filter_date_range['order_type']==='added_to_video_service';

		if ( $this->filter_date_range['order_type']==='added_to_video_service' ) {
			if ( $type==='next' ) {
				$orderby = " ORDER BY `crbh_meta_published`.`meta_value` ASC, `p`.`post_title` DESC LIMIT 1 ";
			} else {
				$orderby = " ORDER BY `crbh_meta_published`.`meta_value` DESC, `p`.`post_title` ASC LIMIT 1 ";
			}
		} else if ( $type==='next' ) {
			$orderby = " ORDER BY `p`.`post_date` ASC, `p`.`post_title` DESC LIMIT 1 ";
		} else {
			$orderby = " ORDER BY `p`.`post_date` DESC, `p`.`post_title` ASC LIMIT 1 ";
		}

		return "GROUP BY `p`.`ID` " . $orderby;
	}

	public function post_join( $join ) {

		global $wpdb;

		$join .= "INNER JOIN `$wpdb->postmeta` AS `crbh_meta_published` ON (
					`crbh_meta_published`.`post_id` = `p`.`ID`
					AND `crbh_meta_published`.`meta_key` = 'published_time'
				) ";

		$join .= "INNER JOIN `$wpdb->postmeta` AS `crbh_meta_blocked` ON ( `crbh_meta_blocked`.`post_id` = `p`.`ID` ) ";

		return $join;
	}

	public function post_where_next( $where ) {
		return $this->post_where( $where, 'next' );
	}

	public function post_where_previous( $where ) {
		return $this->post_where( $where, 'previous' );
	}

	protected function post_where( $where, $type ) {

		// return $where;

		global $wpdb;
		global $post;
		$post_id = esc_sql( $post->ID );

		$where = " WHERE `p`.`post_type` = '{$this->post_type}'
					AND `p`.`ID` != '{$post_id}'
					AND `p`.`post_status` = 'publish'
					AND (
						`crbh_meta_blocked`.`meta_key` = '_block_video'
						AND `crbh_meta_blocked`.`meta_value` != 'Yes'
					)
				";

		if ( $type==='next' ) {
			$where = $this->_post_where_next( $where );
		} else {
			$where = $this->_post_where_previous( $where );
		}

		if ( get_option( $this->prefix . 'enable_listing_filter' )==='Yes' ) {
			$where = $this->post_where_date_filtration( $where );
		}

		return $where;
	}

	protected function post_where_date_filtration( $where ) {

		global $wpdb;

		$date_filter = $this->filter_date_range['date_filter'];
		$date_filter_before = strtotime( $this->filter_date_range['date_filter_before'] );
		$date_filter_after = strtotime( $this->filter_date_range['date_filter_after'] );

		$date_filter_fixed_start = strtotime( $this->filter_date_range['date_filter_fixed'] . ' 00:00:00' );
		$date_filter_fixed_end = strtotime( $this->filter_date_range['date_filter_fixed'] . ' 23:59:59' );

		$date_filter_last_days = $this->filter_date_range['date_filter_last_days'];
		if ( !is_numeric($date_filter_last_days) ) {
			$date_filter_last_days = 5;
		}
		$date_filter_last_days_time = strtotime( "-{$date_filter_last_days} day" . date('Y-m-d') );

		$date = " CAST( unix_timestamp(`p`.`post_date`) AS SIGNED) ";

		$youtube_date = $this->filter_date_range['order_type']==='added_to_video_service';

		if (
			(
				$date_filter==='after_specific_date'
				|| $date_filter==='between_specific_date'
			)
			&& $date_filter_after
		) {

			if ( $youtube_date ) {
				$where .= " AND CAST(`crbh_meta_published`.`meta_value` AS SIGNED) >= '{$date_filter_after}' ";
			} else {
				$where .= " AND {$date} >='{$date_filter_after}' ";
			}

		}

		if (
			(
				$date_filter==='before_specific_date'
				|| $date_filter==='between_specific_date'
			)
			&& $date_filter_before
		) {
			if ( $youtube_date ) {
				$where .= " AND CAST(`crbh_meta_published`.`meta_value` AS SIGNED) <= '{$date_filter_before}' ";
			} else {
				$where .= " AND {$date} <= '{$date_filter_before}' ";
			}
		}

		if (
			$date_filter==='specific_date'
			&& $date_filter_fixed
		) {
			if ( $youtube_date ) {
				$where .= " AND (
								CAST(`crbh_meta_published`.`meta_value` AS SIGNED) >= '{$date_filter_after}'
								AND CAST(`crbh_meta_published`.`meta_value` AS SIGNED) <= '{$date_filter_before}'
							) ";
			} else {
				$where .= " AND {$date} >= '{$date_filter_fixed_start}' ";
				$where .= "  AND {$date} <= '{$date_filter_fixed_end}' ";
			}
		}

		if (
			$date_filter==='within_last_days'
		) {
			if ( $youtube_date ) {
				$where .= " AND CAST(`crbh_meta_published`.`meta_value` AS SIGNED)  >= '{$date_filter_last_days_time}' ";
			} else {
				$where .= " AND {$date} >='{$date_filter_last_days_time}' ";
			}
		}

		return $where;
	}

	protected function _post_where_next( $where ) {
		global $post;
		$post_title = esc_sql( $post->post_title );

		$newest_order = get_option( $this->prefix . 'newest' ); # added_to_video_service, added_to_website
		if ( !$newest_order ) {
			$newest_order = 'added_to_website';
		}

		if( $newest_order==='added_to_website' ) { // added_to_website
			$where .= " AND (
						`p`.`post_date` > '{$post->post_date}'
						OR (
							`p`.`post_date` = '{$post->post_date}'
							AND `p`.`post_title` < '{$post_title}'
						)
					) ";
		} else { // added_to_video_service
			$published_date = esc_sql( get_post_meta( $post->ID, 'published_time', true) );
			$where .= " AND (
						`crbh_meta_published`.`meta_value` > '{$published_date}'
						OR (
							`crbh_meta_published`.`meta_value` = '{$published_date}'
							AND `p`.`post_title` < '{$post_title}'
						)
					) ";
		}

		return $where;
	}

	protected function _post_where_previous( $where ) {
		global $post;
		$post_title = esc_sql( $post->post_title );

		$newest_order = get_option( $this->prefix . 'newest' ); # added_to_video_service, added_to_website
		if ( !$newest_order ) {
			$newest_order = 'added_to_website';
		}

		if( $newest_order==='added_to_website' ) { // added_to_website
			$where .= " AND (
						`p`.`post_date` < '{$post->post_date}'
						OR (
							`p`.`post_date` = '{$post->post_date}'
							AND `p`.`post_title` > '{$post_title}'
						)
					) ";
		} else { // added_to_video_service
			$published_date = esc_sql( get_post_meta( $post->ID, 'published_time', true) );
			$where .= " AND (
						`crbh_meta_published`.`meta_value` < '{$published_date}'
						OR (
							`crbh_meta_published`.`meta_value` = '{$published_date}'
							AND `p`.`post_title` > '{$post_title}'
						)
					) ";
		}

		return $where;
	}

}