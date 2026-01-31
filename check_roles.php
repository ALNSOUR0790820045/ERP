<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== الأدوار الحالية ===\n";
$roles = App\Models\Role::select('id', 'name', 'name_ar', 'level')->orderBy('level', 'desc')->get();
foreach ($roles as $role) {
    echo "- {$role->name_ar} (level: {$role->level})\n";
}

echo "\n=== الوحدات الحالية ===\n";
$modules = App\Models\SystemModule::select('id', 'code', 'name_ar')->where('is_active', true)->get();
foreach ($modules as $module) {
    echo "- {$module->name_ar} ({$module->code})\n";
}

echo "\n=== عدد الشاشات ===\n";
echo "إجمالي الشاشات: " . App\Models\SystemScreen::count() . "\n";
