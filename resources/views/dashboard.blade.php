@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-primary border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-uppercase text-primary fw-bold small mb-1">Clopidogrel 75 Mg</div>
                        <div class="fs-4 fw-bold">{{ $totalData[0]['total'] ?? 0 }}</div>
                        <div class="text-muted">Total Data</div>
                    </div>
                    <div class="ms-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-pills text-primary fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-uppercase text-success fw-bold small mb-1">Candesartan 8 Mg</div>
                        <div class="fs-4 fw-bold">{{ $totalData[1]['total'] ?? 0 }}</div>
                        <div class="text-muted">Total Data</div>
                    </div>
                    <div class="ms-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-heartbeat text-success fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-warning border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-uppercase text-warning fw-bold small mb-1">Isosorbid Dinitrate 5 Mg</div>
                        <div class="fs-4 fw-bold">{{ $totalData[2]['total'] ?? 0 }}</div>
                        <div class="text-muted">Total Data</div>
                    </div>
                    <div class="ms-3">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-heart text-warning fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-info border-4 h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-uppercase text-info fw-bold small mb-1">Nitrokaf Retard 2.5 Mg</div>
                        <div class="fs-4 fw-bold">{{ $totalData[3]['total'] ?? 0 }}</div>
                        <div class="text-muted">Total Data</div>
                    </div>
                    <div class="ms-3">
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-stethoscope text-info fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Metode Monte Carlo & Informasi Obat
                </h5>
            </div>
            <div class="card-body pt-0">
                <div class="mb-4">
                    <h6 class="text-primary mb-3 fw-bold d-flex align-items-center">
                        <i class="fas fa-calculator me-2"></i>
                        Metode Monte Carlo
                    </h6>
                    <div class="card border-light mb-3">
                        <div class="card-body">
                            <p class="mb-0">
                                Metode Monte Carlo adalah teknik simulasi komputer yang menggunakan sampling acak untuk memperkirakan hasil numerik. Dalam konteks prediksi permintaan obat, metode ini menggunakan data historis untuk membangun distribusi probabilitas permintaan, kemudian menggunakan bilangan acak untuk simulasi permintaan masa depan.
                            </p>
                        </div>
                    </div>
                    
                    <h6 class="text-primary mb-3 fw-bold d-flex align-items-center">
                        <i class="fas fa-list-ol me-2"></i>
                        Langkah-langkah Prediksi Permintaan Obat
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">1</span>
                                    Mengumpulkan data historis permintaan obat
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">2</span>
                                    Menghitung frekuensi setiap nilai permintaan
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">3</span>
                                    Menghitung distribusi probabilitas
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">4</span>
                                    Menghitung distribusi probabilitas kumulatif
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">5</span>
                                    Menentukan interval bilangan acak
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">6</span>
                                    Memilih bilangan acak untuk simulasi
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">7</span>
                                    Mengulang proses simulasi berkali-kali
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3">8</span>
                                    Menghitung rata-rata hasil simulasi
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div>
                    <h6 class="text-primary mb-3 fw-bold d-flex align-items-center">
                        <i class="fas fa-heartbeat me-2"></i>
                        Jenis Obat Jantung
                    </h6>
                    
                    <!-- SLIDER/CAROUSEL DENGAN BACKGROUND GAMBAR -->
                    <div class="obat-slider-container">
                        <div class="obat-slider-track" id="obatSliderTrack">
                            <!-- Slide 1: Clopidogrel 75 Mg -->
                            <div class="obat-slide active" data-slide="1">
                                <div class="obat-card">
                                    <div class="obat-image" style="background-image: url('https://drotafarma.com.ve/wp-content/uploads/2024/03/clopidogrel-75-mg-drotafarma--2048x1365.png');"></div>
                                    <div class="obat-content">
                                        <div class="obat-header">
                                            <div class="obat-icon">
                                                <i class="fas fa-pills"></i>
                                            </div>
                                            <div class="obat-name">Clopidogrel</div>
                                            <div class="obat-dosis">75 Mg</div>
                                        </div>
                                        <div class="obat-body">
                                            <div class="obat-description">
                                                <p><strong>Kategori:</strong> Antiplatelet</p>
                                                <p class="mb-0">Obat antiplatelet untuk mencegah pembentukan gumpalan darah pada pasien dengan riwayat serangan jantung atau stroke.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Slide 2: Candesartan 8 Mg -->
                            <div class="obat-slide" data-slide="2">
                                <div class="obat-card">
                                    <div class="obat-image" style="background-image: url('https://coopidrogas.vtexassets.com/arquivos/ids/36831599/candesartan-8-mg-30-tabletas-mkm13999.jpg?v=638413494379730000');"></div>
                                    <div class="obat-content">
                                        <div class="obat-header">
                                            <div class="obat-icon">
                                                <i class="fas fa-heartbeat"></i>
                                            </div>
                                            <div class="obat-name">Candesartan</div>
                                            <div class="obat-dosis">8 Mg</div>
                                        </div>
                                        <div class="obat-body">
                                            <div class="obat-description">
                                                <p><strong>Kategori:</strong> ARB (Angiotensin II Receptor Blocker)</p>
                                                <p class="mb-0">Mengobati tekanan darah tinggi dan gagal jantung dengan memblokir efek hormon angiotensin II.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Slide 3: Isosorbid Dinitrate 5 Mg -->
                            <div class="obat-slide" data-slide="3">
                                <div class="obat-card">
                                    <div class="obat-image" style="background-image: url('https://res-4.cloudinary.com/dk0z4ums3/image/upload/c_scale,h_500,w_500/v1/production/pharmacy/products/1659780885_6231c77089446a1af45d8ec5_isosorbide_5_mg_10_tablet');"></div>
                                    <div class="obat-content">
                                        <div class="obat-header">
                                            <div class="obat-icon">
                                                <i class="fas fa-heart"></i>
                                            </div>
                                            <div class="obat-name">Isosorbid Dinitrate</div>
                                            <div class="obat-dosis">5 Mg</div>
                                        </div>
                                        <div class="obat-body">
                                            <div class="obat-description">
                                                <p><strong>Kategori:</strong> Nitrat</p>
                                                <p class="mb-0">Mencegah nyeri dada (angina) dengan melebarkan pembuluh darah, sehingga meningkatkan aliran darah ke jantung.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Slide 4: Nitrokaf Retard 2.5 Mg -->
                            <div class="obat-slide" data-slide="4">
                                <div class="obat-card">
                                    <div class="obat-image" style="background-image: url('https://d2qjkwm11akmwu.cloudfront.net/products/142621_30-12-2021_11-28-58.png');"></div>
                                    <div class="obat-content">
                                        <div class="obat-header">
                                            <div class="obat-icon">
                                                <i class="fas fa-stethoscope"></i>
                                            </div>
                                            <div class="obat-name">Nitrokaf Retard</div>
                                            <div class="obat-dosis">2.5 Mg</div>
                                        </div>
                                        <div class="obat-body">
                                            <div class="obat-description">
                                                <p><strong>Kategori:</strong> Nitrat Retard</p>
                                                <p class="mb-0">Mengobati dan mencegah nyeri dada (angina) dengan melebarkan pembuluh darah di jantung.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Slider Controls -->
                        <div class="slider-controls">
                            <button class="slider-btn" id="prevBtn" aria-label="Previous slide">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            
                            <div class="slider-counter">
                                <span id="currentSlide">1</span> / <span id="totalSlides">4</span>
                            </div>
                            
                            <div class="slider-dots" id="sliderDots">
                                <!-- Dots will be generated by JavaScript -->
                            </div>
                            
                            <button class="slider-btn" id="nextBtn" aria-label="Next slide">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="row">
            <!-- Pie Chart Card -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="fas fa-chart-pie text-success me-2"></i>
                            Distribusi Data Obat
                        </h5>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex justify-content-center">
                            <div style="width: 240px; height: 240px;">
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        @foreach($pieChartData as $index => $data)
                                        @php
                                            $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="d-inline-block me-2" style="width: 12px; height: 12px; background-color: {{ $colors[$index] }};"></span>
                                                <span class="small">{{ $data['name'] }}</span>
                                            </td>
                                            <td class="text-end fw-bold small">{{ $data['y'] }}</td>
                                        </tr>
                                        @endforeach
                                        <tr class="border-top">
                                            <td class="fw-bold small">Total Data</td>
                                            <td class="text-end fw-bold small">
                                                {{ array_sum(array_column($pieChartData, 'y')) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Log Aktivitas -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="fas fa-history text-warning me-2"></i>
                            Aktivitas Terbaru
                        </h5>
                    </div>
                    <div class="card-body pt-0">
                        <div class="timeline">
                            @forelse($logAktivitas as $log)
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        @if($log->status == 'SUCCESS')
                                        <div class="bg-success rounded-circle p-2">
                                            <i class="fas fa-check text-white"></i>
                                        </div>
                                        @elseif($log->status == 'ERROR')
                                        <div class="bg-danger rounded-circle p-2">
                                            <i class="fas fa-times text-white"></i>
                                        </div>
                                        @else
                                        <div class="bg-warning rounded-circle p-2">
                                            <i class="fas fa-sync text-white"></i>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1 small">{{ $log->proses }}</h6>
                                        <p class="mb-1 text-muted small">{{ Str::limit($log->pesan, 50) }}</p>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-history fa-lg mb-2"></i>
                                <p class="small mb-0">Belum ada aktivitas</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pie Chart
        const ctx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: @json(array_column($pieChartData, 'name')),
                datasets: [{
                    data: @json(array_column($pieChartData, 'y')),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}${value} data (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // SLIDER FUNCTIONALITY
        function initObatSlider() {
            const track = document.getElementById('obatSliderTrack');
            const slides = document.querySelectorAll('.obat-slide');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const currentSlideEl = document.getElementById('currentSlide');
            const totalSlidesEl = document.getElementById('totalSlides');
            const dotsContainer = document.getElementById('sliderDots');
            
            let currentSlide = 0;
            const totalSlides = slides.length;
            
            // Set total slides
            totalSlidesEl.textContent = totalSlides;
            
            // Create dots
            slides.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.className = `slider-dot ${index === 0 ? 'active' : ''}`;
                dot.dataset.index = index;
                dot.addEventListener('click', () => goToSlide(index));
                dotsContainer.appendChild(dot);
            });
            
            // Update slide position
            function updateSlidePosition() {
                track.style.transform = `translateX(-${currentSlide * 100}%)`;
                
                // Update active class on slides
                slides.forEach((slide, index) => {
                    slide.classList.toggle('active', index === currentSlide);
                });
                
                // Update current slide indicator
                currentSlideEl.textContent = currentSlide + 1;
                
                // Update active dot
                document.querySelectorAll('.slider-dot').forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentSlide);
                });
                
                // Update button states
                prevBtn.disabled = currentSlide === 0;
                nextBtn.disabled = currentSlide === totalSlides - 1;
                
                // Add fade animation
                const activeSlide = slides[currentSlide];
                activeSlide.style.animation = 'none';
                setTimeout(() => {
                    activeSlide.style.animation = 'fadeIn 0.5s ease';
                }, 10);
            }
            
            // Go to specific slide
            function goToSlide(index) {
                if (index >= 0 && index < totalSlides) {
                    currentSlide = index;
                    updateSlidePosition();
                }
            }
            
            // Next slide
            function nextSlide() {
                if (currentSlide < totalSlides - 1) {
                    currentSlide++;
                    updateSlidePosition();
                }
            }
            
            // Previous slide
            function prevSlide() {
                if (currentSlide > 0) {
                    currentSlide--;
                    updateSlidePosition();
                }
            }
            
            // Event listeners for buttons
            prevBtn.addEventListener('click', prevSlide);
            nextBtn.addEventListener('click', nextSlide);
            
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') prevSlide();
                if (e.key === 'ArrowRight') nextSlide();
            });
            
            // Swipe functionality for mobile
            let startX = 0;
            let endX = 0;
            
            track.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
            });
            
            track.addEventListener('touchmove', (e) => {
                endX = e.touches[0].clientX;
            });
            
            track.addEventListener('touchend', () => {
                const diff = startX - endX;
                const threshold = 50;
                
                if (Math.abs(diff) > threshold) {
                    if (diff > 0) {
                        // Swipe left
                        nextSlide();
                    } else {
                        // Swipe right
                        prevSlide();
                    }
                }
            });
            
            // Auto slide every 10 seconds
            let autoSlideInterval = setInterval(nextSlide, 10000);
            
            // Pause auto-slide on hover
            track.addEventListener('mouseenter', () => {
                clearInterval(autoSlideInterval);
            });
            
            track.addEventListener('mouseleave', () => {
                autoSlideInterval = setInterval(nextSlide, 10000);
            });
            
            // Initialize
            updateSlidePosition();
        }
        
        // Initialize slider
        initObatSlider();
        
        // Handle image loading errors
        function handleImageErrors() {
            const images = document.querySelectorAll('.obat-image');
            const fallbackColors = [
                'linear-gradient(135deg, #1a5fb4 0%, #3584e4 100%)',
                'linear-gradient(135deg, #26a269 0%, #33d17a 100%)',
                'linear-gradient(135deg, #c64600 0%, #ff7800 100%)',
                'linear-gradient(135deg, #613583 0%, #9141ac 100%)'
            ];
            
            images.forEach((img, index) => {
                // Check if image loaded successfully
                const tempImg = new Image();
                tempImg.src = img.style.backgroundImage.replace('url("', '').replace('")', '');
                
                tempImg.onerror = () => {
                    // Replace with gradient if image fails to load
                    img.style.backgroundImage = 'none';
                    img.style.background = fallbackColors[index % fallbackColors.length];
                };
            });
        }
        
        handleImageErrors();
    });
</script>

<style>
    /* Custom styling untuk dashboard */
    .card {
        border: 1px solid rgba(0,0,0,.125);
        transition: transform 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
    
    .card-title {
        color: #2c3e50;
        font-weight: 600;
    }
    
    .border-start {
        border-left-width: 4px !important;
    }
    
    .list-group-item {
        border: none;
        padding: 0.75rem 0;
        background: transparent;
    }
    
    .badge.bg-primary.rounded-circle {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    
    /* Styling untuk konten slider */
    .obat-description p {
        margin-bottom: 0.5rem;
        line-height: 1.5;
    }
    
    .obat-description p strong {
        color: #fff;
    }
    
    /* STYLING SLIDER BARU */
    .obat-slider-container {
        position: relative;
        overflow: hidden;
        margin: 30px 0;
        border-radius: 16px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .obat-slider-track {
        display: flex;
        transition: transform 0.5s ease-in-out;
    }
    
    .obat-slide {
        flex: 0 0 100%;
        min-width: 100%;
        box-sizing: border-box;
        padding: 5px;
    }
    
    .obat-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        height: 280px;
    }
    
    .obat-image {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-size: cover;
        background-position: center;
        filter: brightness(0.8);
        transition: transform 0.5s ease;
    }
    
    .obat-card:hover .obat-image {
        transform: scale(1.05);
    }
    
    .obat-content {
        position: relative;
        z-index: 2;
        height: 100%;
        display: flex;
        flex-direction: column;
        background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.7) 100%);
        color: white;
    }
    
    .obat-header {
        padding: 20px 20px 10px;
        text-align: center;
        flex-shrink: 0;
    }
    
    .obat-icon {
        font-size: 2rem;
        margin-bottom: 10px;
        color: white;
    }
    
    .obat-name {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .obat-dosis {
        font-size: 1rem;
        opacity: 0.95;
        font-weight: 600;
    }
    
    .obat-body {
        padding: 10px 20px 20px;
        flex-grow: 1;
        display: flex;
        align-items: center;
    }
    
    .obat-description {
        font-size: 0.9rem;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 0;
    }
    
    /* Slider controls */
    .slider-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 15px;
        gap: 15px;
    }
    
    .slider-btn {
        background-color: var(--secondary-color);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
    }
    
    .slider-btn:hover {
        background-color: #0b5ed7;
        transform: scale(1.1);
    }
    
    .slider-btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .slider-dots {
        display: flex;
        gap: 8px;
    }
    
    .slider-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #ddd;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .slider-dot.active {
        background-color: var(--secondary-color);
        transform: scale(1.2);
    }
    
    .slider-counter {
        font-weight: 600;
        color: var(--primary-color);
        font-size: 0.9rem;
        min-width: 60px;
        text-align: center;
    }
    
    /* Animation for slide change */
    @keyframes fadeIn {
        from { opacity: 0.8; }
        to { opacity: 1; }
    }
    
    .obat-slide.active {
        animation: fadeIn 0.5s ease;
    }
    
    /* Timeline styling */
    .timeline-item:not(:last-child) {
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .card-body .row > [class*="col-"] {
            margin-bottom: 1rem;
        }
        
        .card-body .row > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
        
        .obat-card {
            height: 250px;
        }
        
        .obat-header {
            padding: 15px 15px 5px;
        }
        
        .obat-body {
            padding: 5px 15px 15px;
        }
        
        .obat-name {
            font-size: 1.2rem;
        }
        
        .obat-dosis {
            font-size: 0.9rem;
        }
        
        .obat-icon {
            font-size: 1.8rem;
        }
        
        .slider-btn {
            width: 35px;
            height: 35px;
        }
        
        .slider-counter {
            min-width: 50px;
            font-size: 0.8rem;
        }
    }
</style>
@endsection