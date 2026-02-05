<?php

use App\Models\User;
use App\Models\PTK;

echo "--- DEBUG START ---\n";

$mtc = User::whereHas('roles', function ($q) {
    $q->where('name', 'admin_mtc');
})->get();

echo "Admin MTC Users:\n";
foreach ($mtc as $u) {
    echo "ID: {$u->id}, Name: {$u->name}\n";
}

$kabag = User::whereHas('roles', function ($q) {
    $q->where('name', 'kabag_mtc');
})->get();

echo "Kabag MTC Users:\n";
foreach ($kabag as $u) {
    echo "ID: {$u->id}, Name: {$u->name}\n";
}

echo "Latest 5 PTKs:\n";
$ptks = PTK::latest()->take(5)->get();
foreach ($ptks as $p) {
    echo "ID: {$p->id}, CreatedBy: {$p->created_by}, Date: {$p->form_date}\n";
}

echo "--- DEBUG END ---\n";
