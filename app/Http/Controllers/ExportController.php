<?php
namespace App\Http\Controllers;
class ExportController extends Controller {
    public function excel(){ return 'Excel export'; }
    public function pdf($id){ return 'PDF export '.$id; }
    public function rangeForm(){ return view('exports.range_form'); }
    public function rangeReport(){ return view('exports.range_report'); }
    public function rangeExcel(){ return 'Range Excel'; }
    public function rangePdf(){ return 'Range PDF'; }
}
