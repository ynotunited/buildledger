<?php

return [
    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 30),
    'renewal_grace_hours' => (int) env('BILLING_RENEWAL_GRACE_HOURS', 72),
];
