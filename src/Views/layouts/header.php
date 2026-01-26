<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drugmuk - ระบบบริหารคลังเวชภัณฑ์</title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="manifest" href="/manifest.json">
    <?php echo \App\Core\CSRF::metaTag(); ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW Registered!', reg.scope))
                    .catch(err => console.log('SW Registration Failed', err));
            });
        }
    </script>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">Drugmuk</a>
        <div class="navbar-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/">หน้าหลัก</a>
                <a href="/patient/search">ค้นหาผู้ป่วย</a>
                <a href="/chronic/dashboard">ติดตามโรคเรื้อรัง</a>
                <a href="/admin/intelligence"><i class="fas fa-brain"></i> Intelligence</a>
                <a href="/purchasing">แผนจัดซื้อ</a>
                <a href="/inventory">คลังสินค้า</a>
                <a href="/logout">ออกจากระบบ (<?php echo $_SESSION['username']; ?>)</a>
            <?php else: ?>
                <a href="/login">เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
    </nav>
    <main class="container">
