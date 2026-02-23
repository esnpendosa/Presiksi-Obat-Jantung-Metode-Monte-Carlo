<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEM PREDIKSI PERMINTAAN OBAT JANTUNG - METODE MONTE CARLO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #1a5fb4;
            --secondary-color: #0d6efd;
            --accent-color: #26a269;
            --light-bg: #f8fafc;
            --dark-text: #2d3748;
            --light-text: #718096;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            background-color: #f9fafb;
            overflow-x: hidden;
        }
        
        /* Sidebar styling */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1a365d 0%, #2d3748 100%);
            position: fixed;
            width: var(--sidebar-width);
            padding-top: 20px;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            background: white;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border: 3px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            padding: 5px;
        }
        
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .hospital-name {
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .hospital-department {
            font-size: 11px;
            opacity: 0.9;
            text-align: center;
            color: #a0aec0;
            line-height: 1.3;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 12px 20px;
            margin: 4px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            font-size: 15px;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background-color: var(--secondary-color);
            color: white;
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        
        .nav-icon {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 16px;
        }
        
        /* Main content styling */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
        
        /* Header styling */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-subtitle {
            font-size: 1rem;
            color: var(--light-text);
            margin-top: 5px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: 600;
        }
        
        /* Card styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            padding: 18px 24px;
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Button styling */
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        
        /* Table styling */
        .table-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f1f5f9;
            border-bottom: 2px solid var(--border-color);
            font-weight: 700;
            color: var(--dark-text);
            padding: 16px;
        }
        
        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            border-top: 1px solid var(--border-color);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.03);
        }
        
        /* Alert styling */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 16px 20px;
            box-shadow: var(--card-shadow);
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* Footer styling */
        .footer {
            margin-top: 40px;
            padding: 20px 0;
            text-align: center;
            color: var(--light-text);
            font-size: 14px;
            border-top: 1px solid var(--border-color);
        }
        
        /* Menu toggle for mobile */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            width: 50px;
            height: 50px;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Stat cards */
        .stat-card {
            border-left: 4px solid var(--secondary-color);
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stat-card .stat-label {
            font-size: 0.9rem;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Badge styling */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        /* Form styling */
        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        /* Logo fallback styling */
        .logo-fallback {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), #63b3ed);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <!-- Mobile menu toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo">
                    <img src="https://tse1.mm.bing.net/th/id/OIP.vgeHBbHKcDf4Hfap1SKD9AHaHa?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3" 
                         alt="Logo RSUD Ibnu Sina" 
                         onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<div class=\'logo-fallback\'><i class=\'fas fa-heartbeat\'></i></div>';">

                </div>
                <div class="hospital-name">RSUD IBNU SINA</div>
                <div class="hospital-department">Depo Farmasi Rawat Jalan<br>Kabupaten Gresik</div>
            </div>
        </div>
        
        <!-- MENU SEDERHANA SESUAI DIAGRAM -->
        <nav class="nav flex-column">
            <!-- MENU DASHBOARD -->
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                <span>Dashboard</span>
            </a>
            
            <!-- MENU DATA SET -->
            <a class="nav-link {{ request()->routeIs('data.*') ? 'active' : '' }}" href="{{ route('data.index') }}">
                <div class="nav-icon"><i class="fas fa-database"></i></div>
                <span>Data Set</span>
            </a>
            
            <!-- MENU SIMULASI (MONTE CARLO) -->
            <a class="nav-link {{ request()->routeIs('monte-carlo.*') ? 'active' : '' }}" href="{{ route('monte-carlo.index') }}">
                <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
                <span>Monte Carlo</span>
            </a>
            
            <!-- MENU HASIL ERROR -->
            <a class="nav-link {{ request()->routeIs('hasil.*') ? 'active' : '' }}" href="{{ route('hasil.index') }}">
                <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
                <span>Hasil Error</span>
            </a>
            
            <!-- MENU TENTANG KAMI -->
            <a class="nav-link {{ request()->is('tentang-kami') ? 'active' : '' }}" href="{{ route('tentang-kami') }}">
                <div class="nav-icon"><i class="fas fa-info-circle"></i></div>
                <span>Tentang Kami</span>
            </a>
        </nav>
        
        <div class="sidebar-footer mt-auto p-3" style="position: absolute; bottom: 0; width: 100%;">
            <div class="text-center text-white-50 small">
                <div>Sistem Prediksi Obat Jantung</div>
                <div>v1.0.0</div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h1>SISTEM PREDIKSI PERMINTAAN OBAT JANTUNG</h1>
                <div class="page-subtitle">Metode Monte Carlo - Depo Farmasi Rawat Jalan RSUD Ibnu Sina</div>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="ms-2 d-none d-md-block">
                    Admin Farmasi
                </div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-3"></i>
                    <div>{{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <!-- Main Content Area -->
        @yield('content')
        
        <!-- Footer -->
        <div class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 text-md-start text-center">
                        &copy; {{ date('Y') }} RSUD Ibnu Sina. Hak cipta dilindungi.
                    </div>
                    <div class="col-md-6 text-md-end text-center">
                        <span class="text-muted">Sistem Prediksi Obat Jantung v1.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.getElementById('mainContent').addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // Handle logo loading error (backup, walau sudah ada onerror inline)
        document.addEventListener('DOMContentLoaded', function() {
            const logoImg = document.querySelector('.logo img');
            if (logoImg) {
                logoImg.addEventListener('error', function() {
                    this.style.display = 'none';
                    this.parentElement.innerHTML = '<div class="logo-fallback"><i class="fas fa-heartbeat"></i></div>';
                });
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
