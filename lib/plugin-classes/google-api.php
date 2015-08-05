<?php

class VHub_Google_Client {

    private static $client = null;

    public static function get() {
        // Call set_include_path() as needed to point to your client library.
        require_once( VHub_Main::get_plugin_path('lib/Google/autoload.php') );

        // https://console.developers.google.com/project/956816060539/apiui/credential
        $developer_key = get_option(VHub_Prefix . 'google_developer_key');

        if ( empty(self::$client) ) {
            self::$client = new Google_Client();
            self::$client->setDeveloperKey($developer_key);
        }

        return self::$client;
    }
}
