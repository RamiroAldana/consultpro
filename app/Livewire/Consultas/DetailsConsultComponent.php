<?php

namespace App\Livewire\Consultas;

use Livewire\Component;
use App\Models\RequestedQuery;
use App\Models\DetailQuery;
use App\Models\ResultQuery;
use App\Traits\ExecutesApi;

class DetailsConsultComponent extends Component
{
    use ExecutesApi;
    public $requestedId;
    protected $listeners = [
        'deleteDetail' => 'deleteDetail',
        'reactivateDetail' => 'reactivateDetail',
    ];
    public $requested;
    public $details;
    public $selectedResult = null;
    public $execDetail = null; // detail selected for execution
    public $message = null;
    public $showResultModal = false;
    public $showExecuteModal = false;

    public $availableSources = [
        'runt_personas' => 'RUNT Personas',
        'simmit' => 'SIMMIT',
        'rama_judicial' => 'Rama Judicial',
    ];

    public function mount($requestedId = null)
    {
        $this->requestedId = $requestedId;
        $this->loadData();
    }

    public function updatedRequestedId()
    {
        $this->loadData();
    }

    public function loadData()
    {
        // Cargar la solicitud con sus detalles y resultados para obtener
        // el nombre, las fuentes y el estado general
        $this->requested = null;
        $this->details = collect();
        if (!$this->requestedId) return;

        $requested = RequestedQuery::with('details.results')->find($this->requestedId);
        if ($requested) {
            $this->requested = $requested;
            $this->details = $requested->details;
        }
 
    }

    public function formatSourceName($ref)
    {
        return $this->availableSources[$ref] ?? $ref;
    }

    public function render()
    {
        return view('livewire.consultas.details-consult-component')->layout('layouts.app');
    }


    public function consultDetail($id)
    {
        $detail = DetailQuery::with('results')->find($id);
        if (!$detail) {
            $this->addError('exec', 'Registro no encontrado.');
            return;
        }
        $this->execDetail = $detail;

        // Trigger browser event to open modal (modal content is rendered from $execDetail)
        $this->dispatch('open-exec-modal');
    }

    public function showResult($detailId)
    {
        $result = ResultQuery::where('detail_query_id', $detailId)->orderBy('created_at', 'desc')->first();
        $this->selectedResult = $result;
        $this->dispatch('open-result-modal');
    }

    public function executeApi()
    {
        if (empty($this->execDetail) || empty($this->execDetail->id)) {
            $this->addError('exec', 'No hay registro seleccionado.');
            return;
        }

        $detail = DetailQuery::find($this->execDetail->id);
        if (!$detail) {
            $this->addError('exec', 'Registro no encontrado.');
            return;
        }

        $payload = [
            'type_doc' => $detail->document_type,
            'num_doc' => $detail->document_number,
        ];
       

        $this->executeConsulltRunt($payload, $detail);

        // refresh data and close modal
        $this->loadData();
        $this->dispatch('close-exec-modal');
        session()->flash('message', 'Consulta iniciada.');
    }

    public function deleteDetail($id)
    {
        $detail = DetailQuery::find($id);
        if (!$detail) {
            session()->flash('error', 'Registro no encontrado.');
            return;
        }

        // Remove related results first
        ResultQuery::where('detail_query_id', $detail->id)->delete();
        $detail->delete();

        $this->loadData();
        session()->flash('message', 'Registro eliminado correctamente.');
    }

    public function reactivateDetail($id)
    {
        $detail = DetailQuery::find($id);
        if (!$detail) {
            session()->flash('error', 'Registro no encontrado.');
            return;
        }

        $detail->status = 'pendiente';
        $detail->save();

        // If the parent RequestedQuery is 'finalizado', change it to 'pendiente'
        $requested = $detail->requestedQuery;
        if ($requested && $requested->status === 'finalizado') {
            $requested->status = 'pendiente';
            $requested->save();
        }

        $this->loadData();
        session()->flash('message', 'Registro marcado como pendiente para reconsulta.');
    }
}