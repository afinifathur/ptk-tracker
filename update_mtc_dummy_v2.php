<?php

use App\Models\PTK;

$ptk2 = PTK::where('number', 'PTK/MTC/2026/01/002')->first();
if ($ptk2 && $ptk2->mtcDetail) {
    echo "Updating PTK 002 Spareparts...\n";

    // Update Spareparts: 4 Items
    $ptk2->mtcDetail->spareparts()->delete();
    $ptk2->mtcDetail->spareparts()->createMany([
        [
            'name' => 'Spindle Bearing Set NSK 7014',
            'spec' => 'P4 Precision',
            'qty' => 1,
            'supplier' => 'NSK Japan',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-31',
            'actual_arrival_date' => '2026-02-07',
        ],
        [
            'name' => 'Grease Kluber Isoflex NBU 15',
            'spec' => '1 Kg Can',
            'qty' => 2,
            'supplier' => 'Lokal',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-14',
            'actual_arrival_date' => '2026-01-13',
        ],
        // Duplicates
        [
            'name' => 'Spindle Bearing Set NSK 7014',
            'spec' => 'P4 Precision',
            'qty' => 1,
            'supplier' => 'NSK Japan',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-31',
            'actual_arrival_date' => '2026-02-07',
        ],
        [
            'name' => 'Grease Kluber Isoflex NBU 15',
            'spec' => '1 Kg Can',
            'qty' => 2,
            'supplier' => 'Lokal',
            'order_date' => '2026-01-11',
            'status' => 'Received',
            'est_arrival_date' => '2026-01-14',
            'actual_arrival_date' => '2026-01-13',
        ]
    ]);

    echo "Done. Count: " . $ptk2->mtcDetail->spareparts()->count() . "\n";
} else {
    echo "PTK 002 not found.\n";
}
