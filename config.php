<?php
/**
 * config.example.php
 *
 * Copy this file to "config.php" (same folder) and fill in your own
 * values. config.php is listed in .gitignore and must NEVER be committed,
 * screenshotted, or exposed to the browser — it is included only by
 * server-side PHP files.
 */

return [
    // XAMPP MySQL defaults: host "localhost", user "root", empty password.
    'db' => [
        'host'     => 'localhost',
        'name'     => 'gold_jewellery',
        'user'     => 'root',
        'password' => '',
    ],

    // MetalpriceAPI key - https://metalpriceapi.com/
    'gold_api_key' => '1fed406ea7e9f012d1d4db66c198d9a6',

    // AbstractAPI Exchange Rates key - https://www.abstractapi.com/api/exchange-rate-api
    'exchange_api_key' => '1823c5724ab54f8e85c20a9182596481',
];
