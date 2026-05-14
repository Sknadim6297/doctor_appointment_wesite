<?php

return [
    'otp_valid_minutes' => (int) env('ENROLLMENT_EDIT_OTP_MINUTES', 10),

    'edit_session_minutes' => (int) env('ENROLLMENT_EDIT_SESSION_MINUTES', 60),

    'max_otp_attempts' => 5,
];
