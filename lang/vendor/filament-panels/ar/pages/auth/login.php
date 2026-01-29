<?php

return [
    'title' => 'تسجيل الدخول',
    'heading' => 'تسجيل الدخول إلى حسابك',
    'actions' => [
        'register' => [
            'before' => 'أو',
            'label' => 'إنشاء حساب جديد',
        ],
        'request_password_reset' => [
            'label' => 'نسيت كلمة المرور؟',
        ],
    ],
    'form' => [
        'email' => [
            'label' => 'البريد الإلكتروني',
        ],
        'password' => [
            'label' => 'كلمة المرور',
        ],
        'remember' => [
            'label' => 'تذكرني',
        ],
        'actions' => [
            'authenticate' => [
                'label' => 'تسجيل الدخول',
            ],
        ],
    ],
    'messages' => [
        'failed' => 'بيانات الاعتماد هذه غير متطابقة مع سجلاتنا.',
    ],
    'notifications' => [
        'throttled' => [
            'title' => 'محاولات كثيرة',
            'body' => 'يرجى المحاولة مرة أخرى بعد :seconds ثانية.',
        ],
    ],
];
