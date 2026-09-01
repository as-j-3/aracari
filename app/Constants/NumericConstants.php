<?php

namespace App\Constants;

class NumericConstants
{
    const DEFAULT_PER_PAGE = 15;
    const WALLET_PER_PAGE = 20;
    
    const CACHE_TTL_FIVE_MINUTES = 300;
    const CACHE_TTL_ONE_HOUR = 3600;
    const CACHE_TTL_TWO_HOURS = 7200;
    
    const TIME_LIMIT_ONE_HOUR = 60;
    const TIME_LIMIT_TWO_HOURS = 120;
    const TIME_LIMIT_THREE_HOURS = 180;
    
    const RANDOM_STRING_LENGTH_SHORT = 32;
    const RANDOM_STRING_LENGTH_MEDIUM = 60;
    const REFERENCE_PREFIX_LIMIT = 6;
    const REFERENCE_SUFFIX_LENGTH = 4;
    
    const DEFAULT_CUSTOMER_TYPE_ID = 1;
    const DEFAULT_PENDING_STATUS_ID = 3;
    const DEFAULT_ACTIVE_STATUS_ID = 1;
    const DEFAULT_SERVICE_TYPE_ID = 1;
    
    const PHONE_NUMBER_MIN_LENGTH = 10;
    const PHONE_NUMBER_MAX_LENGTH = 15;
    const SEAT_NAME_MAX_LENGTH = 15;
    
    const QR_CODE_SIZE = 300;

     const SEAT_OPTION_KEEP = 'keep';
     const SEAT_OPTION_REGENERATE = 'regenerate';
     const SEAT_OPTION_MANUAL = 'manual';
     const SEAT_STATUS_ACTIVE = 1;
     const SEAT_STATUS_ARCHIVED = 0;
     const SEAT_GEN_AUTO = 'auto';
     const SEAT_GEN_MANUAL = 'manual';

}
