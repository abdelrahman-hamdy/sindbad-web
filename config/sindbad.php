<?php

return [
    'otp_ttl' => env('OTP_TTL_SECONDS', 600),
    'request_prefix_service' => env('REQUEST_PREFIX_SERVICE', 'T-'),
    'request_prefix_installation' => env('REQUEST_PREFIX_INSTALLATION', 'B-'),
    'odoo_product_limit' => env('ODOO_PRODUCT_LIMIT', 100),
];
