<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 25/06/2018
 * Time: 10:54 PM
 */

use function Hummingbird\env;

return [

    "databases" => [

        "mysql" => [
            "host" => env("DB_HOST", "localhost"),
            "port" => env("DB_PORT", "3306"),
            "database" => env("DB_NAME", ""),
            "username" => env("DB_USER", ""),
            "passport" => env("DB_PASS", ""),
        ],

    ],
];
