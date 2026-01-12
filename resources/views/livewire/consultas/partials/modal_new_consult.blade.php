<!-- Ejecutar consulta modal partial -->
<div class="modal fade" id="executeModal" tabindex="-1" aria-labelledby="executeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="executeModalLabel">Ejecutar consulta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Se procederá a consultar la información en la fuente seleccionada para el registro.</p>

                <div class="mb-3">
                    <small class="text-muted">Registro:</small>
                    <div><strong>{{ $execDetail->full_name ?? '-' }}</strong></div>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Fuente seleccionada:</small>
                    <div><span class="badge bg-light text-dark">{{ $this->formatSourceName($execDetail->source ?? '') }}</span></div>
                </div>

                <div class="alert alert-info small mb-0">Al pulsar <strong>Ejecutar consulta</strong> el sistema iniciará la petición a la fuente. Esto puede tardar varios segundos.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-primary me-2" id="execute_confirm_btn" wire:click="executeApi" wire:loading.remove wire:target="executeApi">Ejecutar consulta</button>

                    <button type="button" class="btn btn-primary" disabled wire:loading wire:target="executeApi">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Ejecutando...
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
