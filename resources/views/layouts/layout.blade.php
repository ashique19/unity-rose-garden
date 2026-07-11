<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  
  <title>Unity Rose Garden</title>
  <!--
  TemplateMo 622 Clearwave
  https://templatemo.com/tm-622-clearwave
  Free for personal and commercial use
  -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/css/template.css" />
  <style>
    th, td{
      font-size: 0.65em;
      vertical-align: middle;
    }

    .form-label {
        margin-bottom: .5rem;
        width: 100%;
        text-align: left;
    }
  </style>
</head>
<body>

  <!-- ── MOBILE DRAWER ── -->
  <div class="mobile-drawer" id="mobileMenu" aria-hidden="true">
    <button type="button" class="mobile-drawer-backdrop" id="mobileDrawerBackdrop" aria-label="Close menu"></button>
    <aside class="mobile-drawer-panel" role="dialog" aria-modal="true" aria-label="Navigation">
      <div class="mobile-drawer-scroll">
        <a href="{{ route('home') }}" class="mobile-menu-home">Home</a>

        @if(auth()->user())
          @if(auth()->user()->hasAnyRole(['admin', 'treasurer']))
            <div class="mobile-menu-section">
              <div class="mobile-menu-label">Accounts</div>
              <a href="{{ route('admin.dashboard') }}">Dashboard</a>
              <a href="{{ route('admin.collections.index') }}">Collections</a>
              <a href="{{ route('admin.ledger.index') }}">Ledger</a>
            </div>
          @endif
          @if(auth()->user()->hasAnyRole(['admin', 'secretary']))
            <div class="mobile-menu-section">
              <div class="mobile-menu-label">Billing</div>
              <a href="{{ route('admin.generate.index') }}">Generate month</a>
              <a href="{{ route('admin.gas-readings.index') }}">Gas readings</a>
              <a href="{{ route('admin.water.index') }}">Water</a>
              <a href="{{ route('admin.other-charges.index') }}">Other charges</a>
              <a href="{{ route('admin.flat-bill-type-settings.index') }}">Bill type settings</a>
              <a href="{{ route('charge-templates.index') }}">Charge templates</a>
            </div>
          @endif
          @if(auth()->user()->isAdmin())
            <div class="mobile-menu-section">
              <div class="mobile-menu-label">Admin</div>
              <a href="{{ route('admin.flats.index') }}">Flats</a>
              <a href="{{ route('admin.users.index') }}">Users</a>
              <a href="{{ route('admin.audit.index') }}">Audit log</a>
              <a href="{{ route('bill-history') }}">Legacy history</a>
            </div>
          @endif
        @endif
      </div>

      @if(auth()->user())
        <div class="mobile-drawer-footer">
          <a href="{{ route('logout') }}" class="mobile-cta btn-primary">{{ auth()->user()->name }} · Logout</a>
        </div>
      @endif
    </aside>
  </div>

  <!-- ── 1. NAV ── -->
  <nav class="site-nav" id="mainNav" role="navigation" aria-label="Main navigation">
    <div class="nav-inner">
      <a href="{{ route('home') }}" class="nav-logo">Unity <span>Rose Garden</span></a>
      <ul class="nav-links" role="list">
        @if(auth()->user())
          @if(auth()->user()->hasAnyRole(['admin', 'treasurer']))
            <li class="nav-group">
              <button type="button" class="nav-group-toggle" aria-expanded="false" aria-haspopup="true">Accounts</button>
              <ul class="nav-submenu" role="list">
                <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li><a href="{{ route('admin.collections.index') }}">Collections</a></li>
                <li><a href="{{ route('admin.ledger.index') }}">Ledger</a></li>
              </ul>
            </li>
          @endif
          @if(auth()->user()->hasAnyRole(['admin', 'secretary']))
            <li class="nav-group">
              <button type="button" class="nav-group-toggle" aria-expanded="false" aria-haspopup="true">Billing</button>
              <ul class="nav-submenu" role="list">
                <li><a href="{{ route('admin.generate.index') }}">Generate month</a></li>
                <li><a href="{{ route('admin.gas-readings.index') }}">Gas readings</a></li>
                <li><a href="{{ route('admin.water.index') }}">Water</a></li>
                <li><a href="{{ route('admin.other-charges.index') }}">Other charges</a></li>
                <li><a href="{{ route('admin.flat-bill-type-settings.index') }}">Bill type settings</a></li>
                <li><a href="{{ route('charge-templates.index') }}">Charge templates</a></li>
              </ul>
            </li>
          @endif
          @if(auth()->user()->isAdmin())
            <li class="nav-group">
              <button type="button" class="nav-group-toggle" aria-expanded="false" aria-haspopup="true">Admin</button>
              <ul class="nav-submenu" role="list">
                <li><a href="{{ route('admin.flats.index') }}">Flats</a></li>
                <li><a href="{{ route('admin.users.index') }}">Users</a></li>
                <li><a href="{{ route('admin.audit.index') }}">Audit log</a></li>
              </ul>
            </li>
          @endif
        @endif
      </ul>
      <div class="nav-cta">
        @if(auth()->user())
          <a href="{{ route('logout') }}" class="btn-primary">{{ auth()->user()->name }} · Logout</a>
        @endif
      </div>
      <button class="nav-hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false" type="button">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <main class="site-main">
    @yield('content')
  </main>


  <!-- ── 12. FOOTER ── -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="#" class="nav-logo">Unity<span style="color:var(--accent-light)">Rose Garden</span></a>
          <p class="footer-brand-desc">The calm, peacefull and organized residence.</p>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="footer-copy">
          © 2026 Unity Rose Garden Association. Developed by <em>Crazy4 of Unity Rose Garden</em>
        </div>
        <!-- <div class="footer-legal">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Service</a>
          <a href="#">Cookie Policy</a>
        </div> -->
      </div>
    </div>
  </footer>

  <script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <!-- Put this right inside the <head> tags of resources/views/layouts/layout.blade.php -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="/js/template.js"></script>
  
  @yield('js')
</body>
</html>
