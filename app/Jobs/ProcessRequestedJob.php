<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\RequestedQuery;
use App\Traits\ExecutesApi;
use Throwable;

class ProcessRequestedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ExecutesApi;

    public int $requestedId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $requestedId)
    {
        $this->requestedId = $requestedId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $rq = RequestedQuery::find($this->requestedId);
        if (!$rq) return;

        // Only proceed if status is en_ejecucion (set by command) or pendiente
        if (!in_array($rq->status, ['en_ejecucion', 'pendiente'])) {
            return;
        }

        // Ensure it's marked en_ejecucion
        $rq->status = 'en_ejecucion';
        $rq->save();

        $details = $rq->details()->where('status', 'pendiente')->get();

        try {
            foreach ($details as $detail) {
                // prepare payload depending on source
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

                // Execute and persist via trait
                $this->executeConsulltRunt($payload, $detail);
            }

            $rq->status = 'finalizado';
            $rq->save();
        } catch (Throwable $e) {
            $rq->status = 'error';
            $rq->save();
            // optionally log
        }
    }
}