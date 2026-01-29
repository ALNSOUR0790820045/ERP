<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // إضافة RTL للعربية
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => Blade::render('
                <style>
                    /* تحسينات RTL للعربية */
                    :root {
                        --font-family: "Tajawal", sans-serif;
                    }
                    
                    body {
                        font-family: "Tajawal", sans-serif !important;
                        direction: rtl;
                    }
                    
                    /* تحسين القائمة الجانبية */
                    .fi-sidebar {
                        direction: rtl;
                    }
                    
                    .fi-sidebar-nav-groups {
                        direction: rtl;
                    }
                    
                    /* تحسين الجداول */
                    .fi-ta-table {
                        direction: rtl;
                    }
                    
                    /* تحسين الفورمات */
                    .fi-fo-field-wrp {
                        direction: rtl;
                    }
                    
                    /* تحسين الأيقونات */
                    .fi-icon-btn {
                        direction: ltr;
                    }
                    
                    /* خط عربي أفضل */
                    @import url("https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap");
                    
                    /* تحسين عرض الأرقام */
                    .fi-ta-text {
                        font-feature-settings: "tnum";
                    }
                </style>
            ')
        );
    }
}
