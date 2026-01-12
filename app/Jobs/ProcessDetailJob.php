<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DetailQuery;
use App\Traits\ExecutesApi;

class ProcessDetailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ExecutesApi;

    public int $detailId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $detailId)
    {
        $this->detailId = $detailId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $detail = DetailQuery::find($this->detailId);
        if (!$detail) return;

        // Only process pending details
        if (($detail->status ?? '') !== 'pendiente') return;

        $payload = [];
        $source = $detail->source ?? 'runt_personas';

        if ($source === 'simmit' || $source === 'simit') {
            $payload = ['num_doc' => $detail->document_number];
        } elseif ($source === 'rama_judicial' || $source === 'rama') {
            $payload = [
                'tipo_persona' => 'N',
                'nombre_razon_social' => $detail->full_name,
                'actuaciones_recientes' => true,
            ];
        } else {
            $payload = [
                'type_doc' => $detail->document_type,
                'num_doc' => $detail->document_number,
            ];
        }

        // Delegate to trait which will create/update ResultQuery and update detail status
        $this->executeConsulltRunt($payload, $detail);
    }
}
