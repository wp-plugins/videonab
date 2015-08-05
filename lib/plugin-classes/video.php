<?php

class VHub_Video {

    public function update_videos( $video_type = 'youtube' ){
        $return = array();

        $videos = self::get_videos('youtube');
        if ($videos) {
            foreach ($videos as $loop_id => $video_data) {
                if ($video_data) {

                    // # create the object
                    $updater = new VHub_Video_Updater( $video_data );
                    if ($updater) {
                        $return[] = $video_data['id'];
                    }
                }
            }
        }

        return $return; // updated videos array
    }

    public static function update_single_video( $video_url ){
        $response = array();
        if ($video_url) {
            $video_id = VHub_Video::parce_url($video_url);
            if ($video_id) {
                $video_data = VHub_Video::get_video_feed_by_id( $video_id );
                if ($video_data) {
                    $video_exists = VHub_Video::video_exists($video_id);
                    $insert_video = new VHub_Video_Updater( $video_data );

                    if ($insert_video) {
                        $response['msg']    = $video_exists ? __('The video has been updated successfully.', VHub_LangPrefix) : __('The video has been added successfully.', VHub_LangPrefix);
                        $response['type']   = 'updated';
                        $response['data']   = $video_data;
                    }else{
                        $response['msg']    = __('An error has occur and the video couldn\'t be added. "new VHub_Video_Updater( $video_data ); -> VHub_Video::update_single_video()" ', VHub_LangPrefix);
                        $response['type']   = 'error';
                    }
                }else{
                    $response['msg']    = __('Video data is not available. Please try another video url.', VHub_LangPrefix);
                    $response['type']   = 'error';
                }
            }
        }else{
            $response['msg']    = __('Please enter video URL.', VHub_LangPrefix);
            $response['type']   = 'error';
        }
        return $response;
    }

    public function get_videos( $video_type = 'youtube' ){
        $class_name = 'VHub_Video_' . ucfirst($video_type);
        $vid = new $class_name();
        return $vid->get_videos_data( get_option(VHub_Prefix . 'youtube_term') );
    }

    public static function get_video_data($video_id){
        $prefix = VHub_Prefix . 'video_';

        $video_obj = get_post($video_id);

        return $video_obj ? array(
                'title'                 =>  $video_obj->post_title,
                'id'                    =>  get_post_meta($video_obj->ID, 'video_id' ,true),
                'name'                  =>  $video_obj->post_name,
                'link'                  =>  get_permalink($video_obj->ID),
                'description'           =>  $video_obj->post_content,
                'short_description'     =>  self::shortalize( preg_replace("~\r\n|\n\r|\r|\n~", '', $video_obj->post_content), 55 ),
                'thumbnail'             =>  get_post_meta($video_obj->ID,'thumbnail',true),
                'time'                  =>  self::seconds_to_time( get_post_meta($video_obj->ID, 'duration' ,true) ),
                'metas'                 => array(
                        'video_id'                => get_post_meta($video_obj->ID, 'video_id'               ,true),
                        // 'updated'                 => get_post_meta($video_obj->ID, 'updated'                ,true), # removed in Google API v3
                        'published'               => get_post_meta($video_obj->ID, 'published'              ,true),
                        'watch_page'              => get_post_meta($video_obj->ID, 'watch_page'             ,true),
                        'flash_player_url'        => get_post_meta($video_obj->ID, 'flash_player_url'       ,true),
                        'duration'                => get_post_meta($video_obj->ID, 'duration'               ,true),
                        'youtube_view_count'      => get_post_meta($video_obj->ID, 'youtube_view_count'     ,true),
                        'video_service'           => get_post_meta($video_obj->ID, 'video_service'          ,true),
                        'rating'                  => get_post_meta($video_obj->ID, 'rating'                 ,true),
                        'fb_likes'                => get_post_meta($video_obj->ID, 'fb_likes'               ,true),
                        'fb_comments'             => get_post_meta($video_obj->ID, 'fb_comments'            ,true),
                        'fb_comments_total'       => get_post_meta($video_obj->ID, 'fb_comments_total'      ,true),
                        'fb_likes_comments_count' => 0
                    )
            ) : false;
    }

    /**
     * Truncates a string to a certain word count.
     * @param  string  $input Text to be shortalized. Any HTML will be stripped.
     * @param  integer $words_limit number of words to return
     * @param  string $end the suffix of the shortalized text
     * @return string
     */
    public static function shortalize($input, $words_limit=15, $end='...') {
        $input = strip_tags($input);
        $words_limit = abs(intval($words_limit));

        if ($words_limit == 0) {
            return $input;
        }

        $words = str_word_count($input, 2, '0123456789');
        if (count($words) <= $words_limit + 1) {
            return $input;
        }

        $loop_counter = 0;
        foreach ($words as $word_position => $word) {
            $loop_counter++;
            if ($loop_counter==$words_limit + 1) {
                return substr($input, 0, $word_position) . $end;
            }
        }
    }

    public static function seconds_to_time( $seconds ){
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds - ($hours*3600)) / 60);
        $secs = floor($seconds % 60);
        return array(
                'hours'     => $hours,
                'minutes'   => $mins,
                'seconds'   => $secs,
            );
    }

    public static function parce_url( $video_url ){
        preg_match('~[^a-zA-Z]v=([\w\d_-]{11})~', $video_url, $video_id);
        return $video_id[1];
    }

    public static function video_exists($video_id = false) { // youtube, vimeo : video ID
        $video_id = get_magic_quotes_gpc() ? stripslashes($video_id) : $video_id;

        global $wpdb;
        $video_obj = $wpdb->get_row("SELECT post_id as ID FROM `$wpdb->postmeta` AS `meta` WHERE `meta`.`meta_key` = 'video_id' AND `meta`.`meta_value` = '{$video_id}'");

        if (empty($video_obj->ID)) {
            return false;
        }

        return $video_obj->ID;
    }

    public static function get_video_feed_by_id( $video_id ){
        if (is_numeric($video_id)) { // vimeo
            # code...
        }else{ // youtube
            $video_data = VHub_Video_Youtube::get_video_data($video_id);
            if ($video_data) {
                return $video_data;
            }
        }
    }

    /**
    *    Return video embeded link according to the provided video ID (Vimeo or Youtube)
    *
    *    @param $video_ID  -> string
    *    @return string
    */
    public static function get_video_embeded_link( $video_id ){
        $link = '';
        if (is_numeric($video_id)) {
            // Vimeo
            $link = '//player.vimeo.com/video/' . $video_id;
        }else{
            // Youtube
            $link = '//www.youtube.com/embed/' . $video_id . '?rel=0';
        }

        return $link;
    }

}

class VHub_Video_Updater {

    protected $video_data;

    protected $post_id;

    function __construct( $video_data ){

        // check for video duplciation
        if (!empty($video_data)) {
            $this->video_data = $video_data;

            if ($post_id = VHub_Video::video_exists($video_data['id'])) {
                $this->post_id = $post_id;
                return $this->video_update_db();
            }else{
                return $this->video_add_to_db();
            }
        }

        return $this;
    }

    protected function video_add_to_db(){
        $data = $this->video_data;

        // just in case
        if ($data) {

            // Create post object
            $obj_data = array(
                // 'post_name'         => $data['id'],
                'post_title'        => $data['title'],
                'post_content'      => $data['description'],
                'post_status'       => 'publish',
                'post_type'         => VHub_Prefix . 'videos',
                'post_author'       => 1,
            );

            // Insert the post into the database
            $obj_id = wp_insert_post($obj_data);
            if ($obj_id) {

                // populate video meta information
                foreach ($data['metas'] as $meta_key => $meta_value) {
                    update_post_meta($obj_id, $meta_key, $meta_value);
                }

                // set as not-blocked
                update_post_meta($obj_id, '_block_video', 'No');

                // set video vategory
                if ($data['taxonomies']) {
                    $categories = VHub_Prefix . 'video_cats';
                    wp_set_object_terms( $obj_id, $data['taxonomies'][$categories], $categories );
                }

                return $obj_id;
            }
        }
    }

    protected function video_update_db(){
         $data = $this->video_data;

        // just in case
        if ($data && $this->post_id) {

             // Create post object
            $obj_data = array(
                // 'post_name'         => $data['id'],
                // 'post_title'        => $data['title'],
                // 'post_content'      => $data['description'],
                'ID'                => $this->post_id,
                'post_status'       => 'publish',
                'post_author'       => 1
            );

            // Update the post into the database
            wp_update_post( $obj_data );

            // populate video meta information
            foreach ($data['metas'] as $meta_key => $meta_value) {
                if (!in_array($meta_key, array( 'fb_comments_total', 'fb_comments', 'fb_likes' ))) {
                    update_post_meta($this->post_id, $meta_key, $meta_value);
                }
            }

            // set video vategory
            if ($data['taxonomies']) {
                $categories = VHub_Prefix . 'video_cats';
                wp_set_object_terms( $this->post_id, $data['taxonomies'][$categories], $categories );
            }

            return $this->post_id;
        }
    }
}

class VHub_Video_Youtube {

    private static $youtube = null;
    private static $categories = array();

    private static function init() {
        if ( !empty(self::$youtube) ) {
            return;
        }

        $google_client = VHub_Google_Client::get();
        self::$youtube = new Google_Service_YouTube($google_client);
    }

    public static function get_video_data($video_id) {
        return self::data_walker( self::get_video_by_id($video_id), true );
    }

    public static function get_videos_data($search_term, $page=0, $per_page=8, $orderby='date') {
        return self::data_walker( self::get_videos($search_term, $page, $per_page, $orderby) );
    }

    public static function get_related_video_data($youtube_video_id, $max_results=4) {
        return self::data_walker( self::get_related_videos($youtube_video_id, $max_results) );
    }

    public static function data_walker($video_feed, $single=false) {
        if ( !$video_feed ) {
            return false;
        }

        $results = array();

        if ($single) {
            $results = self::data_processor($video_feed);
        }else{
            $videos_to_loop = $video_feed->getItems();

            $videos = array();
            foreach ($videos_to_loop as $video_entry) {
                $videos[] = self::get_video_data($video_entry['modelData']['id']['videoId']);
            }

            $results['next_page_token'] = $video_feed['nextPageToken'];
            $results['prev_page_token'] = $video_feed['prevPageToken'];
            $results['videos'] = $videos;
        }

        return $results;
    }

    public static function data_processor($video_entry) {
        $title = $video_entry['snippet']['title'];
        $video_id = $video_entry['id'];
        $description = $video_entry['snippet']['description'];
        $published_date = $video_entry['snippet']['publishedAt'];
        $views_count = intval($video_entry['statistics']['viewCount']);
        $thumbnail_src = preg_replace('~default\.jpg~', '0.jpg', $video_entry['snippet']['modelData']['thumbnails']['default']['url']);

        $likes = intval($video_entry['statistics']['likeCount']);
        $dislikes = intval($video_entry['statistics']['dislikeCount']);
        $raters = $likes + $dislikes;

        // https://developers.google.com/youtube/2.0/developers_guide_protocol_ratings
        $min = 1;
        $max = 5;
        if ( !$raters ) {
            $average_rating = 0;
        } else {
            $positive_percent_of_total_votes = ($likes * 100) / $raters;
            $average_rating = max($min, ($positive_percent_of_total_votes / 100) * $max);
        }

        // PT1H24S
        // PT1H4M12S
        preg_match_all('~\d+[a-z]+~i', 'PT1H4M12S', $matches);
        $duration = 0;
        if ( $matches ) {
            foreach ($matches[0] as $time) {
                preg_match('~(\d+)([a-z]+)~i', $time, $time_info);
                $time_value = $time_info[1];
                $time_type = $time_info[2];

                switch ($time_type) {
                    case 'H':
                        $duration += $time_value * 60 * 60; // hours in seconds
                        break;

                    case 'M':
                        $duration += $time_value * 60; // minutes in seconds
                        break;

                    case 'S':
                    default:
                        $duration += $time_value; // should be in seconds
                        break;
                }
            }
        }

        $video_tags = $video_entry['snippet']['tags'];

        $category_id = $video_entry['snippet']['categoryId'];
        $category_obj = self::get_category_by_id($category_id);
        $category_name = $category_obj ? $category_obj['modelData']['snippet']['title'] : '';

        return array(
            'title'                 => $title,
            'id'                    => $video_id,
            'description'           => $description,
            'metas'                 => array(
                    'video_id'              => $video_id,
                    'updated'               => '', # removed in Google API v3
                    'updated_time'          => '', # removed in Google API v3
                    'published'             => $published_date,
                    'published_time'        => strtotime($published_date),
                    'watch_page'            => "https://www.youtube.com/watch?v={$video_entry['id']}&feature=youtube_gdata_player",
                    'flash_player_url'      => "http://www.youtube.com/v/{$video_entry['id']}?version=3&f=videos&app=youtube_gdata",
                    'duration'              => $duration,
                    'youtube_view_count'    => $views_count,
                    'thumbnail'             => $thumbnail_src,
                    'video_service'         => 'youtube',
                    'rating'                => $average_rating,
                    'raters'                => $raters,
                    'fb_likes'              => 0,
                    'fb_comments'           => 0,
                    'fb_comments_total'     => 0
                ),
            'taxonomies'            => array(
                    VHub_Prefix . 'video_cats'      => $category_name,
                    VHub_Prefix . 'video_tags'      => $video_tags,
                )
        );
    }

    /**
     * Allowed values: [date, rating, relevance, title, videocount, viewcount]
     */
    public static function get_videos($search_term, $page_token=null, $max_results=8, $orderby='date') {
        self::init();

        $params = array(
            'q' => $search_term,
            'type' => 'video',
            'order' => $orderby,
            'maxResults' => intval($max_results),
        );

        if ( $page_token ) {
            $params['pageToken'] = $page_token;
        }

        $videos = self::$youtube->search->listSearch(
            'id',
            $params
        );

        return $videos;
    }

    public static function get_related_videos($video_id, $max_results=4) {
        self::init();

        $videos = self::$youtube->search->listSearch(
            'id',
            array(
                'type' => 'video',
                'relatedToVideoId' => $video_id,
                'maxResults' => intval($max_results)
            )
        );

        return $videos;
    }

    public static function get_video_by_id( $video_id ) {
        self::init();

        $videos = self::$youtube->videos->listVideos(
            'id, snippet, contentDetails, statistics',
            array(
                'id' => $video_id,
                'maxResults' => 1
            )
        );

        $video_items = $videos->getItems();

        return $video_items ? $video_items[0] : false;
    }

    public static function get_category_by_id( $category_id ) {
        self::init();

        if ( !empty(self::$categories[$category_id]) ) {
            return self::$categories[$category_id];
        }

        $categories = self::$youtube->videoCategories->listVideoCategories(
            'id, snippet',
            array(
                'id' => $category_id
            )
        );

        $categories_items = $categories->getItems();

        self::$categories[$category_id] = $categories_items ? $categories_items[0] : false;

        return self::$categories[$category_id];
    }
}