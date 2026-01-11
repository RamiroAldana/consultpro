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

class IndexConsultComponent extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $name;
    public $selectedSources = [];
    public $excel;

    public $availableSources = [
        'RUNT Personas',
        'Simmit',
        'Rama Judicial',
    ];

    protected $updatesQueryString = ['search', 'perPage'];

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
                DetailQuery::create([
                    'requested_query_id' => $requested->id,
                    'full_name' => $map['full_name'] ?? null,
                    'document_type' => $map['document_type'] ?? null,
                    'document_number' => $map['document_number'] ?? null,
                    'status' => 'pendiente',
                ]);
                $created++;
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
            $jobs = RequestedQuery::orderBy('created_at', 'desc')->paginate($this->perPage);
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