<?php
/**
 * Oryzenx — default configuration.
 * Copy this file to config.php and edit your real values.
 * config.php is git-ignored and overrides these defaults.
 */
return [
    'site_name' => 'AH NAYON — Portfolio',

    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => '',          // e.g. 'oryzenx'  (empty = DB disabled, contact form falls back to mail/log)
        'user' => 'root',
        'pass' => '',
    ],

    // Contact form notification
    'mail_to'   => 'mdnayon718@gmail.com',
    'mail_from' => 'no-reply@localhost',
    'send_mail' => true,      // set false on hosts without mail()
];
