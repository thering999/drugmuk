<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="patient-search-container">
    <div class="search-hero glass-effect">
        <h1><i class="fas fa-user-injured"></i> ค้นหาข้อมูลผู้ป่วย JHCIS</h1>
        <p>ระบุ HN, ชื่อ-นามสกุล หรือ CID เพื่อดูประวัติการรักษาแบบละเอียด</p>
        
        <div class="search-box-wrapper">
            <input type="text" id="patient-search-input" placeholder="ค้นหาโดย HN / ชื่อ / CID..." autocomplete="off">
            <button id="search-trigger"><i class="fas fa-search"></i> ค้นหา</button>
            <div id="search-loading" class="spinner" style="display:none;"></div>
        </div>
    </div>

    <div id="search-results-container" class="results-grid">
        <!-- Results will be injected here -->
        <div class="initial-state">
            <i class="fas fa-search-plus"></i>
            <p>กรุณาพิมพ์ข้อความเพื่อเริ่มการค้นหา</p>
        </div>
    </div>
</div>

<style>
.patient-search-container {
    padding: 40px 0;
    max-width: 1000px;
    margin: 0 auto;
}

.search-hero {
    text-align: center;
    padding: 50px 30px;
    margin-bottom: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.search-hero h1 {
    font-size: 32px;
    margin-bottom: 10px;
}

.search-hero p {
    opacity: 0.9;
    margin-bottom: 30px;
}

.search-box-wrapper {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    gap: 10px;
}

#patient-search-input {
    flex: 1;
    padding: 15px 25px;
    border-radius: 30px;
    border: none;
    font-size: 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    outline: none;
}

#search-trigger {
    padding: 0 30px;
    border-radius: 30px;
    border: none;
    background: #48bb78;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

#search-trigger:hover {
    background: #38a169;
    transform: scale(1.05);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.patient-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid #edf2f7;
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.patient-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-color: #4299e1;
}

.results-avatar {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: #ebf8ff;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #4299e1;
    font-size: 24px;
    font-weight: bold;
}

.results-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.results-meta {
    font-size: 13px;
    color: #718096;
}

.initial-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px;
    color: #a0aec0;
}

.initial-state i {
    font-size: 64px;
    margin-bottom: 20px;
}

.spinner {
    position: absolute;
    right: 120px;
    top: 15px;
    width: 24px;
    height: 24px;
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patient-search-input');
    const resultsContainer = document.getElementById('search-results-container');
    const loadingSpinner = document.getElementById('search-loading');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            if (query.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="initial-state">
                        <i class="fas fa-search-plus"></i>
                        <p>กรุณาพิมพ์ข้อความเพื่อเริ่มการค้นหา</p>
                    </div>
                `;
            }
            return;
        }

        debounceTimer = setTimeout(() => {
            performSearch(query);
        }, 500);
    });

    function performSearch(query) {
        loadingSpinner.style.display = 'block';
        
        fetch('/api/patient/search?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                loadingSpinner.style.display = 'none';
                if (data.success) {
                    renderResults(data.patients);
                }
            })
            .catch(err => {
                loadingSpinner.style.display = 'none';
                console.error(err);
            });
    }

    function renderResults(patients) {
        if (patients.length === 0) {
            resultsContainer.innerHTML = `
                <div class="initial-state">
                    <i class="fas fa-user-slash"></i>
                    <p>ไม่พบข้อมูลผู้ป่วยที่ตรงตามเงื่อนไข</p>
                </div>
            `;
            return;
        }

        resultsContainer.innerHTML = patients.map(p => `
            <a href="/patient/${p.hn}" class="patient-card">
                <div class="results-avatar">
                    ${p.fname.charAt(0)}
                </div>
                <div class="results-info">
                    <h3>${p.full_name}</h3>
                    <div class="results-meta">
                        HN: <strong>${p.hn}</strong> | อายุ: <strong>${p.age}</strong> ปี
                    </div>
                    <div class="results-meta">
                        CID: ${p.cid}
                    </div>
                </div>
            </a>
        `).join('');
    }
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
