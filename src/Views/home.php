<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drugmuk - ระบบบริหารคลังเวชภัณฑ์ยาออนไลน์</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        nav {
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-size: 1.75rem;
            font-weight: 700;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 2rem;
        }

        .nav-button {
            padding: 0.75rem 2rem;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Hero Section */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .hero-content {
            max-width: 1200px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text {
            color: white;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            font-weight: 400;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .hero-description {
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            line-height: 1.8;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            animation: fadeInUp 0.8s ease 0.6s both;
        }

        .btn-primary {
            padding: 1rem 2.5rem;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            padding: 1rem 2.5rem;
            background: transparent;
            color: white;
            border: 2px solid white;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        /* Features Card */
        .features-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeInRight 0.8s ease;
        }

        .features-title {
            color: white;
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 1.1rem;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .feature-text {
            flex: 1;
        }

        .feature-label {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .feature-desc {
            font-size: 0.95rem;
            opacity: 0.85;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Footer */
        footer {
            padding: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            background: rgba(0, 0, 0, 0.1);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive */
        @media (max-width: 968px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.25rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
            }
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .features-card {
            animation: fadeInRight 0.8s ease, float 6s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <a href="/" class="logo">
            <span class="logo-icon">💊</span>
            <span>Drugmuk</span>
        </a>
        <a href="/login" class="nav-button">เข้าสู่ระบบ</a>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <!-- Left Side - Text Content -->
            <div class="hero-text">
                <h1 class="hero-title">
                    ระบบบริหารคลัง<br>
                    เวชภัณฑ์ยาออนไลน์
                </h1>
                <p class="hero-subtitle">
                    Pharmaceutical Inventory Management System
                </p>
                <p class="hero-description">
                    ระบบบริหารจัดการคลังเวชภัณฑ์ยาแบบครบวงจร พร้อม ABC/VEN Analysis, 
                    FEFO System และ Auto Requisition ช่วยให้การจัดการยามีประสิทธิภาพสูงสุด
                </p>

                <div class="cta-buttons">
                    <a href="/login" class="btn-primary">
                        <span>🔐</span>
                        <span>เข้าสู่ระบบ</span>
                    </a>
                    <a href="#features" class="btn-secondary">
                        <span>📋</span>
                        <span>ดูฟีเจอร์</span>
                    </a>
                </div>

                <!-- Stats -->
                <div class="stats">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">ความแม่นยำ</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">50%</div>
                        <div class="stat-label">ลดเวลาทำงาน</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">พร้อมใช้งาน</div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Features Card -->
            <div class="features-card">
                <h2 class="features-title">
                    <span>✨</span>
                    <span>จุดเด่นของระบบ</span>
                </h2>

                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">
                        <div class="feature-label">แผนซื้อ 3 ปี + ABC/VEN</div>
                        <div class="feature-desc">คำนวณอัตโนมัติจากข้อมูลย้อนหลัง</div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🤔</div>
                    <div class="feature-text">
                        <div class="feature-label">Decision Support</div>
                        <div class="feature-desc">ช่วยตัดสินใจ "ซื้ออะไร?"</div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📦</div>
                    <div class="feature-text">
                        <div class="feature-label">FEFO System</div>
                        <div class="feature-desc">First Expire, First Out</div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🧮</div>
                    <div class="feature-text">
                        <div class="feature-label">Auto Requisition</div>
                        <div class="feature-desc">คำนวณปริมาณเบิกอัตโนมัติ</div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">💊</div>
                    <div class="feature-text">
                        <div class="feature-label">ตัดจ่ายผู้ป่วย</div>
                        <div class="feature-desc">บันทึกและติดตามการจ่ายยา</div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">📄</div>
                    <div class="feature-text">
                        <div class="feature-label">Contract Management</div>
                        <div class="feature-desc">บริหารสัญญาและแจ้งเตือน</div>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🔗</div>
                    <div class="feature-text">
                        <div class="feature-label">JHCIS Integration</div>
                        <div class="feature-desc">เชื่อมต่อกับระบบ JHCIS</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>© 2025 Drugmuk System. All rights reserved. | Developed with ❤️ by Drugmuk Team for SSJ Mukdahan</p>
    </footer>
</body>
</html>
