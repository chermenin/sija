<?php
/**
 * Configuration file.
 *
 * @package api-framework
 * @author  Alex Chermenin <alex@chermenin.ru>
 */

class Config {

    /**
     * Debug mode switcher.
     */
    static $debug = true;

    /**
     * Default values.
     */
    static $defaultOffset = 0;
    static $defaultLimit = 10;

    /**
     * Database settings.
     */
    static $modelsDirectory = 'classes/models';
    static $connections = array(
        'development' => 'mysql://username:password@localhost/database_name',
        'production' => 'mysql://username:password@localhost/database_name',
    );
    static $connection = 'development';

}