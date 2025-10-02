<?php
namespace App\Http\Controllers;

use App\Models\PTK;

class VerifyController extends Controller
{
    public function show(PTK $ptk, string $hash)
    {
        $calc = hash('sha256', json_encode([
            'id'=>$ptk->id,
            'number'=>$ptk->number,
            'status'=>$ptk->status,
            'due'=>$ptk->due_date?->format('Y-m-d'),
            'approved_at'=>$ptk->approved_at?->format('c'),
            'updated_at'=>$ptk->updated_at?->format('c'),
        ]));
        $valid = hash_equals($calc, $hash);
        return view('verify.show', compact('ptk','valid','hash'));
    }
}