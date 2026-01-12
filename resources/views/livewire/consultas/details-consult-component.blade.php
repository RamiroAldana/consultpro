<div>
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <h5 class="mb-0">Detalle de consulta {{ $requested->name ?? '-' }}</h5>
            <small class="text-muted">ID: {{ $requested->id ?? '-' }} • Solicitado: {{ optional($requested->created_at)->format('Y-m-d H:i') ?? '-' }}</small>
        </div>
        <div class="text-end">
            @php
                $rawStatus = $requested->status ?? 'pendiente';
                $rs = strtolower(trim($rawStatus));
                $rs = str_replace(['-', '_'], [' ', ' '], $rs);
                $rcls = 'bg-light text-dark';
                $rlabel = ucfirst($rs);
                switch ($rs) {
                    case 'pendiente':
                        $rcls = 'bg-warning text-dark'; $rlabel = 'Pendiente'; break;
                    case 'en ejecucion':
                    case 'en ejecución':
                    case 'en_ejecucion':
                        $rcls = 'bg-info text-white'; $rlabel = 'En ejecución'; break;
                    case 'finalizado':
                        $rcls = 'bg-success text-white'; $rlabel = 'Finalizado'; break;
                    case 'error':
                        $rcls = 'bg-danger text-white'; $rlabel = 'Error'; break;
                    case 'consultado':
                        $rcls = 'bg-primary text-white'; $rlabel = 'Consultado'; break;
                    case 'fallido':
                    case 'falló':
                        $rcls = 'bg-danger text-white'; $rlabel = 'Fallido'; break;
                    case 'exitoso':
                        $rcls = 'bg-success text-white'; $rlabel = 'Exitoso'; break;
                    case 'finalizado con fallas':
                    case 'finalizado_con_fallas':
                        $rcls = 'bg-secondary text-white'; $rlabel = 'Finalizado (con fallas)'; break;
                }
            @endphp
            <span class="badge {{ $rcls }}">{{ $rlabel }}</span>
        </div>
    </div>

    <div class="mb-3">
        <strong>Fuentes:</strong>
        @if(!empty($requested->sources))
            @foreach($requested->sources as $s)
                <span class="badge bg-light text-dark me-1">{{ $this->formatSourceName($s) }}</span>
            @endforeach
        @else
            -
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fuente</th>
                    <th>Tipo</th>
                    <th>Número</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details as $d)
                    <tr>
                        <td>{{ $d->full_name }}</td>
                        <td><span class="badge bg-light text-dark">{{ $this->formatSourceName($d->source ?? '') }}</span></td>
                        <td>{{ $d->document_type }}</td>
                        <td>{{ $d->document_number }}</td>
                        <td>
                            @php
                                $dRaw = $d->status ?? 'pendiente';
                                $ds = strtolower(trim($dRaw));
                                $ds = str_replace(['-', '_'], [' ', ' '], $ds);
                                $dcls = 'bg-light text-dark';
                                $dlabel = ucfirst($ds);
                                switch ($ds) {
                                    case 'pendiente':
                                        $dcls = 'bg-warning text-dark'; $dlabel = 'Pendiente'; break;
                                    case 'en ejecucion':
                                    case 'en ejecución':
                                    case 'en_ejecucion':
                                        $dcls = 'bg-info text-white'; $dlabel = 'En ejecución'; break;
                                    case 'finalizado':
                                        $dcls = 'bg-success text-white'; $dlabel = 'Finalizado'; break;
                                    case 'error':
                                        $dcls = 'bg-danger text-white'; $dlabel = 'Error'; break;
                                    case 'consultado':
                                        $dcls = 'bg-primary text-white'; $dlabel = 'Consultado'; break;
                                    case 'fallido':
                                    case 'falló':
                                        $dcls = 'bg-danger text-white'; $dlabel = 'Fallido'; break;
                                    case 'exitoso':
                                        $dcls = 'bg-success text-white'; $dlabel = 'Exitoso'; break;
                                    case 'finalizado con fallas':
                                    case 'finalizado_con_fallas':
                                        $dcls = 'bg-secondary text-white'; $dlabel = 'Finalizado (con fallas)'; break;
                                }
                            @endphp
                            <span class="badge {{ $dcls }}">{{ $dlabel }}</span>
                        </td>
                        <td class="text-end">
                            {{-- Use Livewire to load the detail into component and open modal --}}
                            <button type="button" wire:click="consultDetail({{ $d->id }})" class="btn btn-sm btn-outline-success">Ejecutar Consulta</button>
                            <button wire:click="showResult({{ $d->id }})" class="btn btn-sm btn-outline-primary ms-1">Ver resultado</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No hay registros para consultar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>


    @include('livewire.consultas.partials.modal_new_consult')
    @include('livewire.consultas.partials.modal_view_result')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof bootstrap === 'undefined') return;

            window.addEventListener('open-exec-modal', function () {
                var modalEl = document.getElementById('executeModal');
                if (!modalEl) return;
                var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.show();
            });

            window.addEventListener('close-exec-modal', function () {
                var modalEl = document.getElementById('executeModal');
                if (!modalEl) return;
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            });

            window.addEventListener('open-result-modal', function () {
                var modalEl = document.getElementById('viewResultModal');
                if (!modalEl) return;
                var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.show();
            });

            window.addEventListener('close-result-modal', function () {
                var modalEl = document.getElementById('viewResultModal');
                if (!modalEl) return;
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            });
        });
    </script>

</div>
