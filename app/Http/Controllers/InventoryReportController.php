<?php
namespace App\Http\Controllers;
use App\Exports\InventoryExport;
use App\Queries\InventoryReportQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Building;
use Illuminate\Http\Request;
class InventoryReportController extends Controller {
    private function filters(Request $request): array { return $request->validate(['building'=>'nullable|integer|exists:buildings,id','vendor'=>'nullable|in:meraki,unifi']); }
    public function index(Request $request) { $filters=$this->filters($request); return view('reports.inventory',['devices'=>InventoryReportQuery::forFilters($filters)->get(),'buildings'=>Building::orderBy('name')->get(),'filters'=>$filters]); }
    public function download(Request $request) {
        $filters=$this->filters($request);
        return response()->streamDownload(function () use ($filters) {
            $out=fopen('php://output','w');
            fputcsv($out,['Name','Vendor','Model','Serial','Building','Last observed status','Observed at (UTC)']);
            foreach(InventoryReportQuery::forFilters($filters)->cursor() as $device) {
                $values=[$device->name,$device->vendor,$device->model,$device->serial,$device->building?->name,$device->observed_at ? $device->status : null,$device->observed_at?->utc()->toIso8601String()];
                $safe=array_map(function($value) { $text=(string)($value ?? ''); return preg_match('/^[\\x00-\\x20]*[=+@-]/u',$text) ? "'".$text : $text; },$values);
                fputcsv($out,$safe);
            }
            fclose($out);
        },'access-point-inventory.csv',['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store']);
    }
    public function xlsx(Request $request) {
        $filters=$this->filters($request);
        return Excel::download(new InventoryExport($filters),'access-point-inventory.xlsx',null,['Cache-Control'=>'no-store']);
    }
    public function pdf(Request $request) {
        $filters=$this->filters($request);
        $devices=InventoryReportQuery::forFilters($filters)->get();
        $building=isset($filters['building']) ? Building::find($filters['building'])?->name : null;
        return Pdf::loadView('reports.inventory-pdf',['devices'=>$devices,'filters'=>$filters,'building'=>$building,'generatedAt'=>now('UTC')])
            ->setOption(['isRemoteEnabled'=>false,'isPhpEnabled'=>false,'isJavascriptEnabled'=>false])
            ->setPaper('a4','landscape')->download('access-point-inventory.pdf')->header('Cache-Control','no-store');
    }

}
