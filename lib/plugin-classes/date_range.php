<?php 

class VHub_Video_DateRange {

	protected $prefix = VHub_Prefix,
			$filter_date_range = array(
				'order_type' => '',
				'date_filter' => '',
				'date_filter_fixed' => '',
				'date_filter_before' => '',
				'date_filter_after' => '',
				'date_filter_last_days' => 5,
				'youtube_time_start' => 'T00:00:01.000Z',
				'youtube_time_end' => 'T23:59:59.000Z'
			);

	public function __construct() {
		$this->set_date_range();
	}

	protected function set_date_range() {
		$this->filter_date_range['order_type'] = get_option( $this->prefix . 'newest' );
		$this->filter_date_range['date_filter'] = get_option( $this->prefix . 'date_filter' );
		$this->filter_date_range['date_filter_fixed'] = get_option( $this->prefix . 'date_filter_fixed' );
		$this->filter_date_range['date_filter_before'] = get_option( $this->prefix . 'date_filter_before' );
		$this->filter_date_range['date_filter_after'] = get_option( $this->prefix . 'date_filter_after' );
		$this->filter_date_range['date_filter_last_days'] = get_option( $this->prefix . 'date_filter_last_days' );

		return $this;
	}

	public function get_date_range() {
		return $this->filter_date_range;
	}

	public function is_in_range( $video_service_publish_date__or__post_id = null ) {

		if ( !$video_service_publish_date__or__post_id ) {
			return;
		}

		if ( is_numeric($video_service_publish_date__or__post_id) ) {
			$date_to_compare = get_post_meta( $video_service_publish_date__or__post_id, 'published_time', true );
		} else {
			$date_to_compare = strtotime( $video_service_publish_date__or__post_id );
		}

		if ( !$date_to_compare ) {
			return;
		}

		$youtube_start_time = $this->filter_date_range['youtube_time_start'];
		$youtube_end_time = $this->filter_date_range['youtube_time_end'];

		$date_filter = $this->filter_date_range['date_filter'];
		$date_filter_fixed = strtotime( $this->filter_date_range['date_filter_fixed'] . $youtube_start_time );
		$date_filter_before = strtotime( $this->filter_date_range['date_filter_before'] . $youtube_end_time );
		$date_filter_after = strtotime( $this->filter_date_range['date_filter_after'] . $youtube_start_time );

		$date_filter_last_days = $this->filter_date_range['date_filter_last_days'];
		if ( !is_numeric($date_filter_last_days) ) {
			$date_filter_last_days = 5;
		}
		$date_filter_last_days_time = strtotime( "-{$date_filter_last_days} day" . date('Y-m-d') . $youtube_start_time );


		if ( $date_filter==='after_specific_date' ) {
			return $date_to_compare>=$date_filter_after;
		}

		if ( $date_filter==='before_specific_date' ) {
			return $date_to_compare<=$date_filter_before;
		}

		if ( $date_filter==='between_specific_date' ) {
			return $date_to_compare>=$date_filter_after && $date_to_compare<=$date_filter_before;
		}

		if ( $date_filter==='specific_date' ) {
			return date('Y-m-d',$date_to_compare)===date('Y-m-d',$date_filter_fixed);
		}

		if ( $date_filter==='within_last_days' ) {
			return $date_to_compare>=$date_filter_last_days_time;
		}

		return true;
	}

}
