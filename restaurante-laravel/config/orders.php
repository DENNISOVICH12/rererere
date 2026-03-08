<?php

return [
    'hold_window_seconds' => (int) env('ORDER_HOLD_WINDOW_SECONDS', 300),
    'change_request_max_per_order' => (int) env('ORDER_CHANGE_REQUEST_MAX_PER_ORDER', 1),
    'change_request_sla_seconds' => (int) env('ORDER_CHANGE_REQUEST_SLA_SECONDS', 600),
];
