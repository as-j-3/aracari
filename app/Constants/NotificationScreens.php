<?php

namespace App\Constants;


class NotificationScreens
{
    const BOOKING_DETAILS = 'BookingDetailsScreen';
    const BOOKING_LIST = 'BookingListScreen';
    
    const TRIP_DETAILS = 'TripDetailsScreen';
    const TRIP_SEARCH = 'TripSearchScreen';
    
    const WALLET = 'WalletScreen';
    const WALLET_TRANSACTIONS = 'WalletTransactionsScreen';
    
    const PROFILE = 'ProfileScreen';
    const SETTINGS = 'SettingsScreen';
    
    const HOME = 'HomeScreen';
    const NOTIFICATIONS = 'NotificationsScreen';
    
    public static function getScreenByType(string $type): string
    {
        return match ($type) {
            'booking_confirmed', 'booking_cancelled' => self::BOOKING_DETAILS,
            'trip_reminder' => self::TRIP_DETAILS,
            'wallet_credited' => self::WALLET,
            'profile_updated' => self::PROFILE,
            default => self::HOME,
        };
    }
}