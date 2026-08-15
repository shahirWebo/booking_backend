<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduler Mutex Store
    |--------------------------------------------------------------------------
    |
    | Cluster-wide scheduler locks are coordination state. They must not share
    | the evictable application-data cache in deployed environments.
    |
    */

    'lock_store' => env(
        'SCHEDULE_LOCK_STORE',
        in_array(env('APP_ENV'), ['staging', 'production'], true) ? 'scheduler' : 'database',
    ),

    /*
    |--------------------------------------------------------------------------
    | Operational Retention
    |--------------------------------------------------------------------------
    */

    'failed_jobs_retention_hours' => (int) env('SCHEDULE_FAILED_JOBS_RETENTION_HOURS', 720),
    'failed_jobs_prune_time' => env('SCHEDULE_FAILED_JOBS_PRUNE_TIME', '03:17'),
    'mutex_expiration_minutes' => (int) env('SCHEDULE_MUTEX_EXPIRATION_MINUTES', 60),

];
