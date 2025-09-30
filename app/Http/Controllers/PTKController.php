<?php
namespace App\Http\Controllers;
class PTKController extends Controller {
    public function index(){ return view('ptk.index'); }
    public function kanban(){ return view('ptk.kanban'); }
    public function queue(){ return view('ptk.queue'); }
    public function recycle(){ return view('ptk.recycle'); }
    public function restore($id){ return back()->with('ok','Restored.'); }
    public function quickStatus(){ return response()->json(['ok'=>true]); }
    public function import(){ return back()->with('ok','Import finished.'); }
}
