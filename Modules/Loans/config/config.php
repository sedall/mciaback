<?php

return [
    'name' => 'Loans',
    'rules' => [
        'min_amount' => 10000000, // ۱۰ میلیون ریال
        'max_amount' => 500000000, // ۵۰۰ میلیون ریال
        'allowed_tenures' => [3, 6, 12],
        'fee_rate' => 0.04, // کارمزد ۴ درصد
        'penalty_rate' => 0.02, // جریمه دیرکرد ۲ درصد (برای توسعه‌های بعدی)
    ]
];
