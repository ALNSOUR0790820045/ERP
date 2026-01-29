<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Tender;

$tender = new Tender();
$tender->tender_number = 'T-2026-001';
$tender->name_ar = 'عطاء تجريبي لاختبار الواجهة';
$tender->status = 'new';
$tender->tender_type = 'open';
$tender->tender_method = 'public';
$tender->estimated_value = 500000;
$tender->submission_deadline = now()->addDays(30);
$tender->save();

echo "Created tender ID: " . $tender->id . "\n";
