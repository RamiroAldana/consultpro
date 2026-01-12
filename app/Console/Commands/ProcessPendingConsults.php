<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DetailQuery;
use App\Models\RequestedQuery;
use App\Jobs\ProcessDetailJob;
use App\Jobs\ProcessRequestedJob;
use Throwable;

class ProcessPendingConsults extends Command
{
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

            // Dispatch a job to process all its details sequentially
            try {
                ProcessRequestedJob::dispatch($rq->id);
                $this->info("Dispatched ProcessRequestedJob for requested {$rq->id} and marked as en_ejecucion.");
            } catch (Throwable $e) {
                // revert status so it can be retried
                $rq->status = 'pendiente';
                $rq->save();
                $this->error("Failed to dispatch ProcessRequestedJob for {$rq->id}: {$e->getMessage()}");
            }

            // Only dispatch one requested per run
            break;
        }

        return 0;
    }
}