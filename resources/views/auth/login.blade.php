<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
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
    }

    .form-label {
        margin-bottom: .5rem;
        width: 100%;
        text-align: left;
    }
  </style>
</head>
<body>

  <!-- ── MOBILE MENU ── -->
  <div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true" aria-label="Navigation">
    <a href="#">History</a>
    <!-- <a href="#features">Features</a>
    <a href="#pricing">Pricing</a>
    <a href="#testimonials">Reviews</a>
    <a href="#integrations">Integrations</a>
    <a href="#faq">FAQ</a>
    <a href="#" class="mobile-cta btn-primary">Start Free Trial</a> -->
  </div>

  <!-- ── 1. NAV ── -->
  <nav class="nav" id="mainNav" role="navigation" aria-label="Main navigation">
    <div class="nav-inner">
      <a href="/" class="nav-logo">Unity <span>Rose Garden</span></a>
      <ul class="nav-links" role="list">
        <li><a href="#">History</a></li>
        <!-- <li><a href="#features">Features</a></li>
        <li><a href="#pricing">Pricing</a></li>
        <li><a href="#testimonials">Reviews</a></li>
        <li><a href="#faq">FAQ</a></li> -->
      </ul>
      <div class="nav-cta">
        <a href="#" class="btn-ghost">Upcoming</a>
        <!-- <a href="#" class="btn-primary">Start Free Trial</a> -->
      </div>
      <button class="nav-hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- ── 4. FEATURES ── -->
  <section class="features-section" id="features">
    <div class="container">
      <div class="features-header">
        <div class="section-label reveal">Login</div>
        <div class="section-sub reveal reveal-delay-1">
        
            <!-- Session Status (e.g., if you redirect back with a status message) -->
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded">
                    {{ session('status') }}
                </div>
            @endif
          
            <form action="/login" method="POST" class="space-y-4">
            @csrf

            <!-- Phone Number Field -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value="{{ old('phone') }}" 
                    required 
                    autofocus 
                    placeholder="e.g., 01XXXXXXXXX"
                    class="w-full px-3 py-2 border @error('phone') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                @error('phone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full px-3 py-2 border @error('password') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me Checked State -->
            <div class="flex items-center justify-between">
                <label class="flex items-center text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2">Remember me</span>
                </label>
                
                @if (Route::has('password.request'))
                    <a class="text-sm text-blue-600 hover:underline" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div>
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150"
                >
                    Log In
                </button>
            </div>
        </form>
            
      </div>

  </section>

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
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

  <script src="/js/template.js"></script>
</body>
</html>
