<?php

namespace App\Traits;

use App\Models\DetailQuery;
use App\Models\ResultQuery;
use Illuminate\Support\Facades\Http;

use Exception;

trait ExecutesApi
{
    /**
     * Start execution for a given detail: create a ResultQuery placeholder
     * and update the detail status.
     *
     * @param DetailQuery $detail
     * @return ResultQuery
     */
    public function executeConsulltRunt(array $dataConsult, DetailQuery $detail = null)
    {
        $json = null;
        $result = null;

        // If we have a detail, create a placeholder result marking execution started
        if ($detail) {
            $result = ResultQuery::create([
                'detail_query_id' => $detail->id,
                'source' => $detail->source ?? 'runt_personas',
                'status_response' => 'en_ejecucion',
                'response_json' => null,
                'image_path' => null,
            ]);
        }

        $maxWait = 200; // seconds total to wait for completion
        $interval = 3; // seconds between retries
        $deadline = time() + $maxWait;

        // base URL for internal consult APIs — configurable via .env
        $apiBase = rtrim(env('CONSULTS_API_BASE', 'http://75.119.150.202:8080'), '/');

        try {
            // choose endpoint and payload depending on source
            $source = $detail->source ?? ($dataConsult['source'] ?? 'runt_personas');

            if ($source === 'simmit' || $source === 'simit') {
                // SIMIT expects only num_doc
                $url = $apiBase . '/simit/consult_plate';
                $payload = ['num_doc' => $dataConsult['num_doc'] ?? ($dataConsult['document_number'] ?? null)];
            } elseif ($source === 'rama_judicial' || $source === 'rama') {
                // Rama Judicial expects tipo_persona, nombre_razon_social, actuaciones_recientes
                $url = $apiBase . '/ramajudicial/consult_rj';
                $payload = [
                    'tipo_persona' => $dataConsult['tipo_persona'] ?? 'N',
                    'nombre_razon_social' => $dataConsult['nombre_razon_social'] ?? ($detail->full_name ?? null),
                    'actuaciones_recientes' => $dataConsult['actuaciones_recientes'] ?? true,
                ];
            } else {
                // default to RUNT persons
                $url = $apiBase . '/runt/consult_person';
                $payload = [
                    'type_doc' => $dataConsult['type_doc'] ?? ($dataConsult['document_type'] ?? null),
                    'num_doc' => $dataConsult['num_doc'] ?? ($dataConsult['document_number'] ?? null),
                ];
            }

            // initial request
            $response = Http::timeout($maxWait)->post($url, $payload);
            $json = $response->json();

            // if no explicit success, poll until we get success or timeout
            while (empty($json['success']) && time() < $deadline) {
                // short sleep before retrying
                sleep($interval);
                $response = Http::timeout($maxWait)->post($url, $payload);
                $json = $response->json();
                if (!empty($json['success'])) break;
            }
        } catch (Exception $e) {
            $json = ['error' => true, 'mensaje' => 'Request failed: ' . $e->getMessage()];
        }

        if ($detail) {
            $status = (!empty($json['success'])) ? 'ok' : 'error';

            $imagePath = null;
            // screenshots may be at top level or under data.screenshots depending on API
            $img = null;
            if (!empty($json['screenshots']['resultado'])) {
                $img = $json['screenshots']['resultado'];
            } elseif (!empty($json['data']['screenshots']['resultado'])) {
                $img = $json['data']['screenshots']['resultado'];
            }

            if (!empty($img)) {
                if (str_starts_with($img, '/')) {
                    $imagePath = $apiBase . $img;
                } else {
                    $imagePath = $img;
                }
            }

            // update existing result placeholder
            if ($result) {
                $result->update([
                    'status_response' => $status,
                    'response_json' => $json,
                    'image_path' => $imagePath,
                ]);
            } else {
                $result = ResultQuery::create([
                    'detail_query_id' => $detail->id,
                    'source' => $detail->source ?? 'runt_personas',
                    'status_response' => $status,
                    'response_json' => $json,
                    'image_path' => $imagePath,
                ]);
            }

            $detail->status = $status === 'ok' ? 'consultado' : 'fallido';
            $detail->save();

            return $result;
        }

        return $json;
    }
}