<?php
namespace App\Exports;
use App\Models\AccessPoint;
use App\Queries\InventoryReportQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
class InventoryExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, WithTitle {
    public function __construct(private readonly array $filters) {}
    public function query(): Builder { return InventoryReportQuery::forFilters($this->filters); }
    public function title(): string { return 'Access point inventory'; }
    public function headings(): array { return ['Name','Vendor','Model','Serial','Building','Last observed status','Observed at (UTC)']; }
    public function map(mixed $device): array {
        return [$device->name,$device->vendor,$device->model ?? '',$device->serial ?? '',$device->building?->name ?? 'Unassigned',$device->observed_at ? ($device->status ?? 'Unavailable') : 'Unavailable',$device->observed_at?->utc()->toIso8601String() ?? ''];
    }
    // Provider-supplied names and serials must never become spreadsheet formulas.
    public function bindValue(Cell $cell, mixed $value): bool { $cell->setValueExplicit((string)($value ?? ''),DataType::TYPE_STRING); return true; }
}
