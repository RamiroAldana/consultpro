<div>
    {{-- Stop trying to control. --}}
    <div class="mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') ?? '#' }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Trabajo de Consultas</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Trabajo de Consultas</h1>
            <p class="text-muted small mb-0">Listado de trabajos solicitados y su estado</p>
        </div>
    </div>

    <!-- Report modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">Generar informe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Se generará el informe para la solicitud ID: <strong>{{ $reportRequestedId }}</strong></p>
                    <dl class="row">
                        <dt class="col-sm-6">Total consultas</dt>
                        <dd class="col-sm-6">{{ $reportTotals['total'] ?? 0 }}</dd>
                        <dt class="col-sm-6">Exitosas</dt>
                        <dd class="col-sm-6 text-success">{{ $reportTotals['success'] ?? 0 }}</dd>
                        <dt class="col-sm-6">Fallidas</dt>
                        <dd class="col-sm-6 text-danger">{{ $reportTotals['failed'] ?? 0 }}</dd>
                        <dt class="col-sm-6">Pendientes</dt>
                        <dd class="col-sm-6 text-muted">{{ $reportTotals['pending'] ?? 0 }}</dd>
                    </dl>
                    <div class="alert alert-info small">El informe incluirá los datos de consulta, la fuente, el resultado y la imagen incrustada cuando esté disponible.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" wire:click="generateReport" class="btn btn-primary" wire:loading.attr="disabled" wire:target="generateReport">
                        <span wire:loading.remove wire:target="generateReport">Generar</span>
                        <span wire:loading wire:target="generateReport"><span class="spinner-border spinner-border-sm me-1" role="status"></span> Generando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var reportModalEl = document.getElementById('reportModal');
            if (!reportModalEl) return;
            var reportModal = new bootstrap.Modal(reportModalEl);

            // Open modal from Livewire event
            window.addEventListener('open-report-modal', function () {
                reportModal.show();
            });

            // Close modal event
            window.addEventListener('close-report-modal', function () {
                reportModal.hide();
            });

            // When report is ready, download
            window.addEventListener('report-ready', function (e) {
                var url = null;
                try {
                    if (!e || !e.detail) {
                        return;
                    }

                    // Common shapes: { url: '...' } or { payload: { url: '...' } } or [ { url: '...' } ] or simple string
                    if (typeof e.detail === 'string') {
                        url = e.detail;
                    } else if (e.detail.url) {
                        url = e.detail.url;
                    } else if (e.detail.payload && e.detail.payload.url) {
                        url = e.detail.payload.url;
                    } else if (Array.isArray(e.detail) && e.detail[0] && e.detail[0].url) {
                        url = e.detail[0].url;
                    } else {
                        // fallback: try to find first string value that looks like a URL
                        for (var k in e.detail) {
                            if (typeof e.detail[k] === 'string' && (e.detail[k].startsWith('http') || e.detail[k].startsWith('/')) ) {
                                url = e.detail[k];
                                break;
                            }
                        }
                    }
                } catch (err) {
                    console.error('report-ready handler error', err);
                }

                if (url) {
                    window.location.href = url;
                }
            });
        });
    </script>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2 align-items-center">
                <input wire:model.debounce.500ms="search" type="text" class="form-control form-control-sm" placeholder="Buscar ID, fuente..." style="width:240px">
                <select wire:model="perPage" class="form-select form-select-sm" style="width:120px">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>

            <div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newConsultModal">Nueva consulta</button>
            </div>
        </div>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Fecha solicitado</th>
                    <th scope="col">Cantidad de consultas</th>
                    <th scope="col">Fuentes a Consultar</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs ?? [] as $job)
                    <tr>
                        <td>{{ $job->id }}</td>
                        <td>{{ optional($job->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $job->details_count ?? $job->count ?? $job->cantidad ?? '-' }}</td>
                        <td>
                            @if(!empty($job->sources))
                                @foreach($job->sources as $src)
                                    <span class="badge bg-secondary me-1">{{ $this->getSourceName($src) }}</span>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $rawStatus = $job->status ?? $job->estado ?? 'pendiente';
                                $status = strtolower(trim($rawStatus));
                                // Normalize common variants
                                $status = str_replace(['-', '_'], [' ', ' '], $status);

                                $cls = 'bg-light text-dark';
                                $label = ucfirst($status);

                                switch ($status) {
                                    case 'pendiente':
                                        $cls = 'bg-warning text-dark';
                                        $label = 'Pendiente';
                                        break;
                                    case 'en ejecucion':
                                    case 'en ejecución':
                                    case 'en_ejecucion':
                                        $cls = 'bg-info text-white';
                                        $label = 'En ejecución';
                                        break;
                                    case 'finalizado':
                                        $cls = 'bg-success text-white';
                                        $label = 'Finalizado';
                                        break;
                                    case 'error':
                                        $cls = 'bg-danger text-white';
                                        $label = 'Error';
                                        break;
                                    case 'consultado':
                                        $cls = 'bg-primary text-white';
                                        $label = 'Consultado';
                                        break;
                                    case 'fallido':
                                    case 'falló':
                                        $cls = 'bg-danger text-white';
                                        $label = 'Fallido';
                                        break;
                                    case 'exitoso':
                                        $cls = 'bg-success text-white';
                                        $label = 'Exitoso';
                                        break;
                                    case 'finalizado con fallas':
                                    case 'finalizado_con_fallas':
                                        $cls = 'bg-secondary text-white';
                                        $label = 'Finalizado (con fallas)';
                                        break;
                                }
                            @endphp
                            <span class="badge {{ $cls }}">{{ $label }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('consultas.detail', $job->id) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            <button type="button" wire:click="openReportModal({{ $job->id }})" class="btn btn-sm btn-outline-secondary ms-1">Descargar informe</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">No hay trabajos de consultas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    

    <div class="mt-3 d-flex justify-content-between align-items-center">
        <div>
            @if(method_exists($jobs, 'total')) <small class="text-muted">Mostrando {{ $jobs->count() }} de {{ $jobs->total() }} resultados</small>@endif
        </div>
        <div>
            @if(method_exists($jobs, 'links')) {{ $jobs->links() }} @endif
        </div>
    </div>

    <!-- New consult modal -->
    <div class="modal fade" id="newConsultModal" tabindex="-1" aria-labelledby="newConsultModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <form wire:submit.prevent="createJob" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newConsultModalLabel">Nueva consulta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if (session()->has('message'))
                        <div class="alert alert-success">{{ session('message') }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Nombre corto</label>
                        <input wire:model.defer="name" type="text" class="form-control">
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fuentes a consultar</label>
                        @foreach($availableSources as $reference => $name)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="selectedSources" value="{{ $reference }}" id="src_{{ $reference }}">
                                <label class="form-check-label" for="src_{{ $reference }}">{{ $name }}</label>
                            </div>
                        @endforeach
                        @error('selectedSources') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adjuntar Excel (opcional)</label>
                        <input wire:model="excel" type="file" accept=".xlsx,.xls,.csv" class="form-control">
                        @error('excel') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear trabajo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Close modal when a success message appears in the modal (works without Livewire browser events)
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('newConsultModal');
            if (!modalEl) return;
            var observer = new MutationObserver(function (mutationsList) {
                if (modalEl.querySelector('.alert-success')) {
                    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                    observer.disconnect();
                }
            });
            observer.observe(modalEl, { childList: true, subtree: true });

            // no-op: details handled via dedicated route
        });
    </script>
</div>
