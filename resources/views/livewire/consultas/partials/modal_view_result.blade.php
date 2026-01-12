<!-- Modal para ver resultado de consulta -->
<div class="modal fade" id="viewResultModal" tabindex="-1" aria-labelledby="viewResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                @php
                    $jsonHeader = $selectedResult->response_json ?? null;
                    $imageHeader = $selectedResult->image_path ?? ($jsonHeader['screenshots']['resultado'] ?? null);
                @endphp
                <div>
                    <h5 class="modal-title" id="viewResultModalLabel">Resultado de la consulta</h5>
                    <small class="text-muted">{{ $selectedResult->created_at ?? '' }}</small>
                </div>
                <div class="ms-auto d-flex gap-2">
                    @if(!empty($imageHeader))
                        <a href="{{ (str_starts_with($imageHeader,'/') ? url($imageHeader) : $imageHeader) }}" target="_blank" class="btn btn-outline-secondary btn-sm" download>
                            <i class="fas fa-download"></i>&nbsp; Descargar imagen
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
            <div class="modal-body">
                @if(empty($selectedResult))
                    <div class="alert alert-warning">No hay resultado disponible para este registro.</div>
                @else
                    @php $json = $selectedResult->response_json ?? null; @endphp

                    <ul class="nav nav-tabs mb-3" id="resultTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Resumen</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Detalles</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="raw-tab" data-bs-toggle="tab" data-bs-target="#raw" type="button" role="tab">JSON</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="row">
                                <div class="col-md-5 text-center mb-3">
                                    @php
                                        $imageUrl = $selectedResult->image_path ?? ($json['screenshots']['resultado'] ?? null);
                                        if(!empty($imageUrl) && str_starts_with($imageUrl, '/')) {
                                            $imageUrl = url($imageUrl);
                                        }
                                    @endphp
                                    @if(!empty($imageUrl))
                                        <img src="{{ $imageUrl }}" alt="Resultado" class="img-fluid rounded shadow" style="max-height:360px; object-fit:contain;">
                                    @else
                                        <div class="border rounded d-flex align-items-center justify-content-center" style="height:260px">
                                            <span class="text-muted">Sin imagen disponible</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-7">
                                    @if((isset($json['status']) && $json['status'] === 'sin_registro_o_no_activa') || (isset($json['data']['cantidad']) && $json['data']['cantidad'] === 0))
                                        <h6 class="mb-2">Sin registro / No activa</h6>
                                        <p class="mb-2"><strong>Mensaje:</strong> {{ $json['mensaje'] ?? ($json['detalle'] ?? '-') }}</p>
                                        @if(!empty($json['detalle']))
                                            <p class="mb-0"><strong>Detalle:</strong> {{ $json['detalle'] }}</p>
                                        @endif

                                    @elseif(!empty($json['data']['datos_persona']) || !empty($json['data']['licencias']))
                                        <h6 class="mb-2">RUNT - Datos</h6>
                                        <dl class="row">
                                            @foreach($json['data']['datos_persona'] ?? [] as $k => $v)
                                                <dt class="col-sm-5">{{ ucwords(str_replace('_',' ',$k)) }}</dt>
                                                <dd class="col-sm-7">{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}</dd>
                                            @endforeach
                                        </dl>
                                        @if(!empty($json['data']['licencias']))
                                            <p class="mb-0"><strong>Licencias:</strong> {{ count($json['data']['licencias']) }}</p>
                                        @endif

                                    @elseif(!empty($json['data']['comparendos']) || !empty($json['data']['detalle_comparendos']) || isset($json['data']['total']))
                                        <h6 class="mb-2">SIMIT - Resumen</h6>
                                        <dl class="row">
                                            <dt class="col-sm-5">Comparendos</dt>
                                            <dd class="col-sm-7">{{ $json['data']['comparendos'] ?? '-' }}</dd>
                                            <dt class="col-sm-5">Multas</dt>
                                            <dd class="col-sm-7">{{ $json['data']['multas'] ?? '-' }}</dd>
                                            <dt class="col-sm-5">Total</dt>
                                            <dd class="col-sm-7">{{ $json['data']['total'] ?? '-' }}</dd>
                                            <dt class="col-sm-5">Mensaje</dt>
                                            <dd class="col-sm-7">{{ $json['data']['mensaje'] ?? ($json['mensaje'] ?? '-') }}</dd>
                                        </dl>

                                    @elseif(!empty($json['data']['procesos']) || isset($json['data']['cantidad_procesos']))
                                        <h6 class="mb-2">Rama Judicial - Resumen</h6>
                                        <dl class="row">
                                            <dt class="col-sm-5">Nombre / Razón social</dt>
                                            <dd class="col-sm-7">{{ $json['data']['nombre_razon_social'] ?? '-' }}</dd>
                                            <dt class="col-sm-5">Cantidad procesos</dt>
                                            <dd class="col-sm-7">{{ $json['data']['cantidad_procesos'] ?? '-' }}</dd>
                                            <dt class="col-sm-5">Actuaciones recientes</dt>
                                            <dd class="col-sm-7">{{ !empty($json['data']['actuaciones_recientes']) ? 'Sí' : 'No' }}</dd>
                                        </dl>

                                    @else
                                        <h6 class="mb-2">Resumen</h6>
                                        <p class="text-muted small">No hay estructura conocida; puedes revisar el JSON crudo en la pestaña "JSON".</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="details" role="tabpanel">
                            <div class="mb-3">
                                @if(!empty($json['data']['datos_persona']))
                                    <h6>Datos de la persona</h6>
                                    <div class="table-responsive mb-3" style="max-height:260px; overflow:auto">
                                        <table class="table table-sm table-bordered mb-0">
                                            <tbody>
                                                @foreach($json['data']['datos_persona'] as $k => $v)
                                                    <tr>
                                                        <th style="width:40%">{{ ucwords(str_replace('_',' ',$k)) }}</th>
                                                        <td>{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(!empty($json['data']['licencias']))
                                    <h6>Licencias</h6>
                                    <div class="table-responsive mb-3" style="max-height:260px; overflow:auto">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Numero</th>
                                                    <th>Estado</th>
                                                    <th>Fecha expedicion</th>
                                                    <th>Organismo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($json['data']['licencias'] as $lic)
                                                    <tr>
                                                        <td>{{ $lic['numero_licencia'] ?? ($lic['numero'] ?? '-') }}</td>
                                                        <td>{{ $lic['estado'] ?? '-' }}</td>
                                                        <td>{{ $lic['fecha_expedicion'] ?? '-' }}</td>
                                                        <td>{{ $lic['organismo_transito'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(!empty($json['data']['detalle_comparendos']))
                                    <h6>Detalle de comparendos</h6>
                                    @php $rows = $json['data']['detalle_comparendos']; $first = $rows[0] ?? []; @endphp
                                    <div class="table-responsive mb-3" style="max-height:320px; overflow:auto">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    @foreach(array_keys((array)$first) as $col)
                                                        <th>{{ ucwords(str_replace('_',' ',$col)) }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($rows as $r)
                                                    <tr>
                                                        @foreach((array)$r as $val)
                                                            <td>{{ is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(!empty($json['data']['procesos']))
                                    <h6>Procesos</h6>
                                    @php $rows = $json['data']['procesos']; $first = $rows[0] ?? []; @endphp
                                    <div class="table-responsive mb-3" style="max-height:380px; overflow:auto">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    @foreach(array_keys((array)$first) as $col)
                                                        <th>{{ ucwords(str_replace('_',' ',$col)) }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($rows as $r)
                                                    <tr>
                                                        @foreach((array)$r as $val)
                                                            <td>{{ is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="tab-pane fade" id="raw" role="tabpanel">
                            <pre class="small bg-light p-2" style="max-height:520px; overflow:auto">{{ json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
