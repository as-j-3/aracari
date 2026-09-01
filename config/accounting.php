<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Accounting Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file controls the accounting behavior in the system.
    | It allows gradual migration to double-entry bookkeeping with Accounts Receivable.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Use Receivables Account
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will use proper double-entry bookkeeping with
    | Accounts Receivable. When disabled, it uses the legacy simple accounting.
    |
    | Default: true (receivables mode for proper double-entry accounting)
    |
    */
    'use_receivables_account' => env('USE_RECEIVABLES_ACCOUNT', true),

    /*
    |--------------------------------------------------------------------------
    | Accounts Receivable Account Code
    |--------------------------------------------------------------------------
    |
    | The account code to use for Accounts Receivable (booking receivables).
    | This should match the account created in the accounts table.
    |
    */
    'ar_account_code' => env('AR_ACCOUNT_CODE', '1200'),

    /*
    |--------------------------------------------------------------------------
    | Enable Parallel Run
    |--------------------------------------------------------------------------
    |
    | When enabled alongside use_receivables_account, the system will record
    *both* the legacy and new accounting entries for comparison and verification.
    | This is useful for testing and gradual migration.
    |
    | Default: false
    |
    */
    'enable_parallel_run' => env('ENABLE_PARALLEL_RUN', false),

    /*
    |--------------------------------------------------------------------------
    | Accounting Mode
    |--------------------------------------------------------------------------
    |
    | Possible values:
    | - 'legacy': Simple accounting without Accounts Receivable
    | - 'receivables': Double-entry with Accounts Receivable
    | - 'parallel': Both systems running simultaneously for testing
    |
    */
    'accounting_mode' => env('ACCOUNTING_MODE', 'legacy'),
];
