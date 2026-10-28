<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('vtu.airtime') }}" class="btn {{ (request()->routeIs('vtu.airtime') || (request()->routeIs('vtu.index') && !request()->routeIs('vtu.history'))) ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3 fw-bold">
            <i class="fa-solid fa-mobile-screen me-1"></i> Airtime
        </a>
        <a href="{{ route('vtu.data') }}" class="btn {{ request()->routeIs('vtu.data') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3 fw-bold">
            <i class="fa-solid fa-wifi me-1"></i> Mobile Data
        </a>
        <a href="{{ route('vtu.cable') }}" class="btn {{ request()->routeIs('vtu.cable') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3 fw-bold">
            <i class="fa-solid fa-tv me-1"></i> Cable TV
        </a>
        <a href="{{ route('vtu.electricity') }}" class="btn {{ request()->routeIs('vtu.electricity') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3 fw-bold">
            <i class="fa-solid fa-lightbulb me-1"></i> Electricity
        </a>
    </div>
    <div>
        <a href="{{ route('vtu.history') }}" class="btn {{ request()->routeIs('vtu.history') ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill btn-sm px-3 fw-bold shadow-sm">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> VTU Order History
        </a>
    </div>
</div>
