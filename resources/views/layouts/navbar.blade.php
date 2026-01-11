<header class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container-fluid px-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-light me-2 d-md-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="true" aria-label="Toggle sidebar" data-bs-toggle="sidebar">☰</button>
            <a class="navbar-brand brand me-3" href="#">{{ config('app.name', 'ConsulPro') }}</a>
        </div>

        <div class="d-flex align-items-center ms-auto">
            <form class="d-none d-sm-flex input-group me-3">
                <span class="input-group-text bg-white border-end-0"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#0f4c81" class="bi bi-search" viewBox="0 0 16 16"><path d="M11 6a5 5 0 1 1-10 0 5 5 0 0 1 10 0z"/></svg></span>
                <input class="form-control border-start-0" type="search" placeholder="Buscar..." aria-label="Search">
            </form>

            <button class="btn position-relative me-3 p-2 rounded-circle" title="Notificaciones">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#f23e3e" class="bi bi-bell" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 1.985-1.75H6.015A2 2 0 0 0 8 16z"/><path d="M8 1a3 3 0 0 0-3 3v1.528C4.034 6.11 3 7.64 3 9.5V11l-.707.707A1 1 0 0 0 3 13h10a1 1 0 0 0 .707-1.293L13 11V9.5c0-1.86-1.034-3.39-2-3.972V4a3 3 0 0 0-3-3z"/></svg>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill notif-badge">3</span>
            </button>

            @if(Auth::check())
                <div class="dropdown" data-bs-boundary="viewport">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle flex-nowrap mx-2" id="profileDropdown" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                        <img src="{{ Auth::user()->profile_photo_url ?? asset('images/avatar.png') }}" alt="{{ Auth::user()->name }}" width="36" height="36" class="rounded-circle me-2 flex-shrink-0" />
                        <div class="d-none d-sm-block text-end text-truncate" style="max-width:140px;">
                            <div class="fw-semibold text-indigo-900 text-truncate">{{ Auth::user()->name }}</div>
                            <div class="small text-muted text-truncate">{{ Auth::user()->email }}</div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="#">Perfil</a></li>
                        <li><a class="dropdown-item" href="#">Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit">Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Iniciar sesión</a>
            @endif
        </div>
    </div>
</header>