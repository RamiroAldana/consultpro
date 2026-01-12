<nav id="sidebarMenu" class="collapse show d-md-block sidebar bg-white border-end">
    <div class="position-sticky pt-3">
        <div class="d-flex align-items-center gap-3 px-3 mb-3">
            <div>
                <div class="brand">{{ config('app.name', 'ConsulPro') }}</div>
                <div class="text-muted small">Panel de administración</div>
            </div>
            <button class="btn btn-sm btn-outline-secondary ms-auto d-md-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-label="Cerrar menú">×</button>
        </div>

        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center py-2 rounded" href="{{ route('dashboard') ?? '#' }}">
                    <span class="me-2 text-primary"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-grid-3x3-gap" viewBox="0 0 16 16"><path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm6 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V2zM1 8a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V8zm6 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V8z"/></svg></span>
                    Dashboard
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link d-flex align-items-center py-2 rounded" href="/consultas_index">
                    <span class="me-2 text-danger"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16"><path d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/></svg></span>
                    Consultas
                </a>
            </li>
        </ul>

        <div class="px-3 mt-4 small text-muted">© {{ date('Y') }} {{ config('app.name') }}</div>
    </div>
</nav>