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
                        <td>{{ $job->count ?? $job->cantidad ?? '-' }}</td>
                        <td>
                            @if(!empty($job->sources))
                                @foreach($job->sources as $src)
                                    <span class="badge bg-secondary me-1">{{ $src }}</span>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $status = strtolower($job->status ?? $job->estado ?? 'pendiente');
                                switch($status){
                                    case 'pendiente': $cls = 'bg-warning text-dark'; break;
                                    case 'consultado': $cls = 'bg-primary text-white'; break;
                                    case 'fallido': $cls = 'bg-danger text-white'; break;
                                    case 'exitoso': $cls = 'bg-success text-white'; break;
                                    case 'finalizado con fallas': $cls = 'bg-secondary text-white'; break;
                                    default: $cls = 'bg-light text-dark';
                                }
                            @endphp
                            <span class="badge {{ $cls }}">{{ ucfirst($status) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-outline-primary">Ver</a>
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
                        @foreach($availableSources as $src)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="selectedSources" value="{{ $src }}" id="src_{{ \Illuminate\Support\Str::slug($src) }}">
                                <label class="form-check-label" for="src_{{ \Illuminate\Support\Str::slug($src) }}">{{ $src }}</label>
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
        });
    </script>
</div>
