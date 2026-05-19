<?php

return [
    'max_file_kb' => (int) env('ENROLLMENT_MAX_FILE_KB', 10240),
    'max_request_upload_kb' => (int) env('ENROLLMENT_MAX_REQUEST_UPLOAD_KB', 51200),

    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],

    'allowed_mimes' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],

    'blocked_extensions' => [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
        'exe', 'msi', 'dll', 'com', 'bat', 'cmd', 'sh', 'bash',
        'js', 'mjs', 'cjs', 'vbs', 'vbe', 'jar', 'war',
        'asp', 'aspx', 'jsp', 'cgi', 'pl', 'py', 'rb',
        'htaccess', 'html', 'htm', 'svg', 'xml',
    ],
];
