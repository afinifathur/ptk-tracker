<?php
namespace App\Http\Controllers;
class ApprovalController extends Controller {
    public function approve(){ return back()->with('ok','Approved.'); }
    public function reject(){ return back()->with('ok','Rejected.'); }
}
