<aside class="dashboard__sidebar sidebar-menu">
    <div class="dashboard__sidebar-area">
        <div class="dashboard__sidebar-header">
            <a href="{{ route('fleet.dashboard') }}" class="dashboard__sidebar-logo">
                <img class="img-fluid light-show" src="{{ siteLogo() }}">
            </a>
        </div>
        <div class="dashboard__sidebar-inner">
            <ul class="dashboard-nav ps-0">
                <li class="dashboard-nav__items">
                    <a href="{{ route('fleet.dashboard') }}" class="dashboard-nav__link">
                        <span class="dashboard-nav__link-icon"><i class="las la-home"></i></span>
                        <span class="dashboard-nav__link-text">Dashboard</span>
                    </a>
                </li>
                <li class="dashboard-nav__items">
                    <a href="{{ route('fleet.drivers') }}" class="dashboard-nav__link">
                        <span class="dashboard-nav__link-icon"><i class="las la-users"></i></span>
                        <span class="dashboard-nav__link-text">Conductores</span>
                    </a>
                </li>
                <li class="dashboard-nav__items">
                    <a href="{{ route('fleet.fares') }}" class="dashboard-nav__link">
                        <span class="dashboard-nav__link-icon"><i class="las la-dollar-sign"></i></span>
                        <span class="dashboard-nav__link-text">Configurar Tarifas</span>
                    </a>
                </li>
                <li class="dashboard-nav__items">
                    <a href="{{ route('fleet.logout') }}" class="dashboard-nav__link text-danger">
                        <span class="dashboard-nav__link-icon"><i class="las la-sign-out-alt text-danger"></i></span>
                        <span class="dashboard-nav__link-text">Salir</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
