<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DetailQuery;
use App\Models\RequestedQuery;
use App\Traits\ExecutesApi;
use Throwable;

class ProcessPendingConsults extends Command
{
    use ExecutesApi;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consults:process-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending consult requests and dispatch jobs for pending details.';

    public function handle()
    {
        $this->info('Scanning for the first pending requested query...');

        while (true) {
            $rq = RequestedQuery::where('status', 'pendiente')->orderBy('id')->first();

            if (!$rq) {
                $this->info('No pending requested queries found.');
                break;
            }

            // If this requested has no pending details, finalize and continue to next
            $pendingCount = $rq->details()->where('status', 'pendiente')->count();
            if ($pendingCount === 0) {
                $rq->status = 'finalizado';
                $rq->save();
                $this->info("Requested {$rq->id} had no pending details; marked finalizado.");
                // continue to next pending requested
                continue;
            }

            // Try to atomically change status to en_ejecucion to avoid races
            $updated = RequestedQuery::where('id', $rq->id)->where('status', 'pendiente')->update(['status' => 'en_ejecucion']);

            if (!$updated) {
                // someone else acquired it, try again
                $this->info("Requested {$rq->id} was acquired by another process, retrying...");
                continue;
            }

            // Process synchronously here (no queues, no dispatch)
            try {
                // Ensure requested is marked en_ejecucion
                $rq->status = 'en_ejecucion';
                $rq->save();

                $details = $rq->details()->where('status', 'pendiente')->get();

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

                    // Execute synchronously via trait (will create/update ResultQuery and set detail status)
                    $this->executeConsulltRunt($payload, $detail);
                }

                $rq->status = 'finalizado';
                $rq->save();
                $this->info("Processed requested {$rq->id} synchronously and marked as finalizado.");
            } catch (Throwable $e) {
                $rq->status = 'error';
                $rq->save();
                $this->error("Failed to process requested {$rq->id} synchronously: {$e->getMessage()}");
            }

            // Only process one requested per run
            break;
        }

        return 0;
    }
}