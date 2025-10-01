<?php

namespace App\Http\Controllers;

use App\Models\PTK;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\{PTKExport, RangeExport};
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function excel(Request $request){
        return Excel::download(new PTKExport($request), 'ptk.xlsx');
    }

    public function pdf(PTK $ptk){
        $ptk->load(['attachments','pic','department','category']);
        $docHash = hash('sha256', json_encode([
            'id'=>$ptk->id,'number'=>$ptk->number,'status'=>$ptk->status,
            'due'=>$ptk->due_date?->format('Y-m-d'),
            'approved_at'=>$ptk->approved_at?->format('c'),
            'updated_at'=>$ptk->updated_at?->format('c'),
        ]));
        $pdf = Pdf::loadView('exports.ptk_pdf', compact('ptk','docHash'));
        return $pdf->download('ptk-'.$ptk->number.'.pdf');
    }

    public function rangeForm(){ return view('exports.range_form'); }

    public function rangeReport(Request $request){
        $data = $request->validate(['start'=>'required|date','end'=>'required|date|after_or_equal:start']);
        $items = PTK::with(['pic','department','category'])
            ->whereBetween('created_at', [$data['start'], $data['end']])->get();
        return view('exports.range_report', compact('items','data'));
    }

    public function rangeExcel(Request $request){
        $data = $request->validate(['start'=>'required|date','end'=>'required|date|after_or_equal:start']);
        return Excel::download(new RangeExport($data['start'],$data['end']), 'ptk-range.xlsx');
    }

    public function rangePdf(Request $request){
        $data = $request->validate(['start'=>'required|date','end'=>'required|date|after_or_equal:start']);
        $items = PTK::with(['pic','department','category'])
            ->whereBetween('created_at', [$data['start'], $data['end']])->get();
        $docHash = hash('sha256', json_encode(['range'=>$data,'count'=>$items->count(),'ts'=>now()->format('c')]));
        $pdf = Pdf::loadView('exports.range_pdf', compact('items','data','docHash'));
        return $pdf->download('ptk-range-'.$data['start'].'_to_'.$data['end'].'.pdf');
    }
}
