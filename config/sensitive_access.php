<?php

return [
  'otp_valid_minutes' => (int) env('SENSITIVE_OTP_VALID_MINUTES', 5),
  'session_valid_minutes' => (int) env('SENSITIVE_OTP_SESSION_MINUTES', 5),
  'max_attempts' => (int) env('SENSITIVE_OTP_MAX_ATTEMPTS', 5),
];
