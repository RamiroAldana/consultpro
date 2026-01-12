<?php

namespace App\Livewire\Consultas;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\RequestedQuery;
use App\Models\DetailQuery;
use App\Models\ResultQuery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Http\File as HttpFile;

class IndexConsultComponent extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $name;
    public $selectedSources = [];
    public $excel;

    public $availableSources = [
        'runt_personas' => 'RUNT Personas',
        'simmit' => 'SIMMIT',
        'rama_judicial' => 'Rama Judicial',
    ];

    protected $updatesQueryString = ['search', 'perPage'];

    // report properties
    public $reportRequestedId = null;
    public $reportTotals = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'pending' => 0,
    ];
    public $reportGenerating = false;

    public function getSourceName($reference)
    {
        return $this->availableSources[$reference] ?? $reference;
    }

    public function openReportModal($requestedId)
    {
        $this->reportRequestedId = $requestedId;
        $this->reportTotals = ['total'=>0,'success'=>0,'failed'=>0,'pending'=>0];

        $requested = RequestedQuery::with(['details.results'])->find($requestedId);
        if (!$requested) {
            $this->addError('report', 'Solicitud no encontrada.');
            return;
        }

        $total = 0; $succ = 0; $fail = 0; $pend = 0;
        foreach ($requested->details as $d) {
            $total++;
            $latest = $d->results->sortByDesc('created_at')->first();
            if (!$latest) { $pend++; continue; }
            $ok = false; $err = false;
            if (!empty($latest->status_response)) {
                if (strtolower($latest->status_response) === 'ok') $ok = true;
                if (strtolower($latest->status_response) === 'error') $err = true;
            }
            if (!$ok && is_array($latest->response_json) && isset($latest->response_json['success'])) {
                if ($latest->response_json['success']) $ok = true; else $err = true;
            }
            if ($ok) $succ++; elseif ($err) $fail++; else $pend++;
        }

        $this->reportTotals = ['total'=>$total,'success'=>$succ,'failed'=>$fail,'pending'=>$pend];

        // open modal via browser event
        $this->dispatch('open-report-modal');
    }

    public function generateReport()
    {
        if (!$this->reportRequestedId) {
            $this->addError('report', 'No hay solicitud seleccionada.');
            return;
        }

        if (!class_exists('\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $this->addError('report', 'Para generar el informe instale phpoffice/phpspreadsheet (composer require phpoffice/phpspreadsheet).');
            return;
        }

        $this->reportGenerating = true;

        $requested = RequestedQuery::with(['details.results'])->find($this->reportRequestedId);
        if (!$requested) {
            $this->addError('report', 'Solicitud no encontrada.');
            $this->reportGenerating = false;
            return;
        }

        // ensure reports directory
        Storage::disk('public')->makeDirectory('reports');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Informe_'.$requested->id);

        $headers = ['Detalle ID','Nombre','Tipo documento','Número documento','Fuente','Estado detalle','Resultado estado','Mensaje/Detalle','Imagen'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        foreach ($requested->details as $detail) {
            $latest = $detail->results->sortByDesc('created_at')->first();
            $mensaje = '';
            $resultStatus = $latest->status_response ?? ($latest->status ?? '');
            if ($latest && is_array($latest->response_json)) {
                $mensaje = $latest->response_json['mensaje'] ?? $latest->response_json['detalle'] ?? ($latest->response_json['mensaje'] ?? '');
            }

            $sheet->setCellValue('A'.$row, $detail->id);
            $sheet->setCellValue('B'.$row, $detail->full_name ?? '');
            $sheet->setCellValue('C'.$row, $detail->document_type ?? '');
            $sheet->setCellValue('D'.$row, $detail->document_number ?? '');
            $sheet->setCellValue('E'.$row, $this->getSourceName($detail->source ?? ''));
            $sheet->setCellValue('F'.$row, $detail->status ?? '');
            $sheet->setCellValue('G'.$row, $resultStatus);
            $sheet->setCellValue('H'.$row, $mensaje);

            // image handling
            $imagePath = $latest->image_path ?? ($latest->response_json['screenshots']['resultado'] ?? null);
            if ($imagePath) {
                $tmp = sys_get_temp_dir().'/report_img_'.uniqid().'.png';
                try {
                    if (str_starts_with($imagePath, '/')) {
                        $full = public_path($imagePath);
                        if (file_exists($full)) copy($full, $tmp);
                    } else {
                        // try storage public
                        $p = ltrim($imagePath, '/');
                        if (Storage::disk('public')->exists($p)) {
                            copy(Storage::disk('public')->path($p), $tmp);
                        } else {
                            $contents = @file_get_contents($imagePath);
                            if ($contents) file_put_contents($tmp, $contents);
                        }
                    }

                    if (file_exists($tmp)) {
                        $drawing = new Drawing();
                        $drawing->setPath($tmp);
                        $drawing->setHeight(80);
                        $drawing->setCoordinates('I'.$row);
                        $drawing->setWorksheet($sheet);
                        $sheet->getRowDimension($row)->setRowHeight(60);
                    }
                } catch (\Exception $e) {
                    // ignore image
                }
            }

            $row++;
        }

        $filename = 'informe_consulta_'.$requested->id.'_'.date('Ymd_His').'.xlsx';
        $tmpfile = storage_path('app/public/reports/'.$filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpfile);

        $url = asset('storage/reports/'.$filename);
        $this->reportGenerating = false;
        $this->dispatch('report-ready', ['url' => $url]);
        $this->dispatch('close-report-modal');
    }

    // note: detail view is handled via dedicated route; no modal here

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function createJob()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'selectedSources' => 'required|array|min:1',
            'excel' => 'nullable|file|mimes:xlsx,xls,csv|max:51200',
        ]);

        // store uploaded file (optional)
        $filePath = null;
        if ($this->excel) {
            $filePath = Storage::disk('public')->putFile('consultas', $this->excel);
        }

        // create requested query using model if available
        if (class_exists(RequestedQuery::class) && Schema::hasTable('requested_queries')) {
            $requested = RequestedQuery::create([
                'name' => $this->name,
                'sources' => $this->selectedSources,
                'status' => 'pendiente',
            ]);

            $rows = [];
            if ($filePath) {
                $fullPath = Storage::disk('public')->path($filePath);
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                if ($ext === 'csv') {
                    if (($handle = fopen($fullPath, 'r')) !== false) {
                        $headers = [];
                        while (($data = fgetcsv($handle, 0, ',')) !== false) {
                            if (empty($headers)) {
                                $headers = array_map(function ($h) { return mb_strtolower(trim($h)); }, $data);
                                continue;
                            }
                            $rows[] = array_combine($headers, $data);
                        }
                        fclose($handle);
                    }
                } else {
                    // xlsx/xls: require phpoffice/phpspreadsheet
                    if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                        $this->addError('excel', 'Para procesar archivos XLSX/XLS necesita instalar phpoffice/phpspreadsheet');
                        return;
                    }
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $data = $sheet->toArray(null, true, true, true);
                    $headers = [];
                    foreach ($data as $i => $line) {
                        if ($i === 1) {
                            $headers = array_map(function ($h) { return mb_strtolower(trim($h)); }, $line);
                            continue;
                        }
                        $flat = [];
                        foreach ($line as $cell) {
                            $flat[] = $cell;
                        }
                        if (!empty($headers)) $rows[] = array_combine($headers, $flat);
                    }
                }
            }

            $created = 0;
            foreach ($rows as $r) {
                $map = [];
                foreach ($r as $k => $v) {
                    $lk = mb_strtolower($k);
                    if (str_contains($lk, 'nombre')) $map['full_name'] = $v;
                    if (str_contains($lk, 'tipo')) $map['document_type'] = $v;
                    if (str_contains($lk, 'numero') || str_contains($lk, 'num')) $map['document_number'] = $v;
                }
                if (empty($map)) continue;

                // create one detail row per selected source so each detail has a source
                $sourcesToUse = $this->selectedSources ?: ['default'];
                foreach ($sourcesToUse as $src) {
                    DetailQuery::create([
                        'requested_query_id' => $requested->id,
                        'full_name' => $map['full_name'] ?? null,
                        'document_type' => $map['document_type'] ?? null,
                        'document_number' => $map['document_number'] ?? null,
                        'status' => 'pendiente',
                        'source' => $src,
                    ]);
                    $created++;
                }
            }

            // If table has count column, update it
            if (Schema::hasColumn('requested_queries', 'count')) {
                $requested->count = $created;
                $requested->save();
            }
        } else {
            // fallback to legacy table insert if exists
            $table = Schema::hasTable('consulta_jobs') ? 'consulta_jobs' : (Schema::hasTable('jobs') ? 'jobs' : null);
            if ($table) {
                DB::table($table)->insertGetId([
                    'name' => $this->name,
                    'sources' => json_encode($this->selectedSources),
                    'file_path' => $filePath,
                    'count' => 0,
                    'status' => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // reset form
        $this->name = null;
        $this->selectedSources = [];
        $this->excel = null;

        session()->flash('message', 'Trabajo creado correctamente.');
    }

    public function render()
    {
        if (Schema::hasTable('requested_queries')) {
            // include count of related details to show number of records per request
            $jobs = RequestedQuery::withCount('details')->orderBy('created_at', 'desc')->paginate($this->perPage);
        } elseif (Schema::hasTable('consulta_jobs') || Schema::hasTable('jobs')) {
            $table = Schema::hasTable('consulta_jobs') ? 'consulta_jobs' : 'jobs';
            $query = DB::table($table)->select('*');
            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('id', 'like', "%{$this->search}%")
                      ->orWhere('sources', 'like', "%{$this->search}%")
                      ->orWhere('status', 'like', "%{$this->search}%");
                });
            }
            $jobs = $query->orderBy('created_at', 'desc')->paginate($this->perPage);
        } else {
            $sample = collect([
                (object)['id' => 1, 'created_at' => now(), 'count' => 12, 'sources' => ['Google','API'], 'status' => 'pendiente'],
                (object)['id' => 2, 'created_at' => now()->subHours(6), 'count' => 5, 'sources' => ['DB'], 'status' => 'exitoso'],
                (object)['id' => 3, 'created_at' => now()->subDay(), 'count' => 8, 'sources' => ['API','DB'], 'status' => 'fallido'],
            ]);

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $items = $sample->forPage($currentPage, $this->perPage);
            $jobs = new LengthAwarePaginator($items->values(), $sample->count(), $this->perPage, $currentPage, [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ]);
        }

        return view('livewire.consultas.index-consult-component', [
            'jobs' => $jobs,
        ])->layout('layouts.app');
    }
}