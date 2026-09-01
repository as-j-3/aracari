<?php

namespace App\Constants;

class UserAction
{
    const REGISTER_OTP_SENT = 'register_otp_sent';
    const REGISTER_ALREADY_EXISTS = 'register_already_exists';
    const REGISTER_PENDING_ACTIVATION = 'register_pending_activation';
    const LOGIN_SUCCESS = 'login_success';
    const LOGIN_INVALID_CREDENTIALS = 'login_invalid_credentials';
    const LOGIN_ACCOUNT_PENDING = 'login_account_pending';
    const LOGIN_ACCOUNT_SUSPENDED = 'login_account_suspended';
    const OTP_VERIFIED = 'otp_verified';
    const OTP_INVALID = 'otp_invalid';
    const OTP_EXPIRED = 'otp_expired';
    const OTP_RATE_LIMITED = 'otp_rate_limited';
    const PASSWORD_SET = 'password_set';
    const PASSWORD_INVALID_STATE = 'password_invalid_state';
    const PASSWORD_RESET_OTP_SENT = 'password_reset_otp_sent';
    const PASSWORD_RESET_SUCCESS = 'password_reset_success';
    const PROFILE_UPDATED = 'profile_updated';
    const PROFILE_WRONG_PASSWORD = 'profile_wrong_password';
    const PROFILE_WRONG_PHONE = 'profile_wrong_phone';
}