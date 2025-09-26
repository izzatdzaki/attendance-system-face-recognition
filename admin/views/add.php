<style>
    .container {
    max-width: 500px;
    margin: 0 auto;
    padding: 10px;
}
.logo-container {
    text-align: center; /* Mengatur logo agar berada di tengah */
    margin: 20px 0; /* Menambahkan margin atas dan bawah */
}
.logo {
    max-width: 10%; /* Memastikan logo responsif */
    height: auto; /* Mempertahankan rasio aspek */
}
.video-container {
    position: relative;
    width: 90%;
    max-width: 360px;
    margin: 20px auto;
}

#video {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .container {
        padding: 15px;
        max-width: 100%;
    }
    
    .video-container {
        width: 95%;
        max-width: 100%;
        margin: 15px auto;
    }
    
    #video {
        border-radius: 12px;
        max-height: 70vh;
        object-fit: cover;
    }
    
    .controls {
        flex-direction: column;
        gap: 10px;
        padding: 0 10px;
    }
    
    button {
        width: 100%;
        padding: 15px 20px;
        font-size: 18px;
        border-radius: 8px;
    }
    
    .status {
        margin: 10px 0;
        padding: 15px;
        font-size: 16px;
    }
    
    #registrationDialog {
        width: 90%;
        max-width: 400px;
        padding: 20px;
        margin: 20px;
    }
    
    .dialog-content input {
        padding: 15px;
        font-size: 16px;
        border-radius: 8px;
    }
    
    .dialog-content button {
        padding: 15px;
        font-size: 16px;
        border-radius: 8px;
    }
}

.controls {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin: 20px 0;
}

button {
    padding: 10px 20px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s;
}

button:hover {
    background: #2980b9;
}

.status {
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    margin: 15px 0;
    font-weight: bold;
}

.loading { background: #f39c12; color: white; }
.success { background: #2ecc71; color: white; }
.error { background: #e74c3c; color: white; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.notification-popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #2ecc71;
    color: white;
    padding: 20px 40px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    z-index: 1000;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, -60%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}
#registrationDialog {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.dialog-content {
    display: flex;
    flex-direction: column;
}

.dialog-content label {
    margin-top: 10px;
}

.dialog-content input {
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.dialog-content button {
    margin-top: 15px;
    padding: 10px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.dialog-content button:hover {
    background: #2980b9;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<div class="container">
    <div class="status loading">Memulai sistem...</div>
    
    <div class="video-container">
        <video id="video" autoplay muted playsinline webkit-playsinline></video>
    </div>

    <div class="controls">
        <button id="registerBtn" disabled>Daftarkan User</button>
        <button id="attendanceBtn" style="display: none;" disabled>Ambil Absensi</button>
        <button id="debugBtn" style="background: #e74c3c; margin-top: 10px;">Test Koneksi</button>
    </div>
    
    <div id="notificationPopup" class="notification-popup">
        <span id="popupMessage">Berhasil Mendaftarkan User</span>
    </div>

    <div id="registrationDialog" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000;">
    <div class="dialog-content">
        <h3>Daftarkan User</h3>
        <label for="userName">Nama:</label>
        <input type="text" id="userName" placeholder="Masukkan nama" required>
        <label for="userNIP">NIP:</label>
        <input type="text" id="userNIP" placeholder="Masukkan NIP" required>
        <label for="userJabatan">Jabatan:</label>
        <input type="text" id="userJabatan" placeholder="Masukkan jabatan" required>
        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <button id="submitRegistration" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Daftarkan</button>
            <button id="cancelRegistration" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300">Batal</button>
        </div>
    </div>
</div>
</div>
<script>
    // Konfigurasi Database - Dynamic URL untuk mobile compatibility
    const getApiUrl = () => {
        const host = window.location.hostname;
        const port = window.location.port;
        const protocol = window.location.protocol;
        
        // Jika menggunakan port 8000 (development server)
        if (port === '8000') {
            return `${protocol}//${host}:${port}/api.php`;
        }
        
        // Jika menggunakan server normal (port 80/443)
        return `${protocol}//${host}/absensi/api.php`;
    };
    
    const API_BASE_URL = getApiUrl();

    // Elements
    const video = document.getElementById('video');
    const registerBtn = document.getElementById('registerBtn');
    const attendanceBtn = document.getElementById('attendanceBtn');
    const statusDiv = document.querySelector('.status');
    const attendanceTable = document.querySelector('#attendanceTable tbody');

    // Fungsi untuk memanggil API dengan error handling yang lebih baik
    async function callAPI(action, data = {}) {
        try {
            console.log('Calling API:', API_BASE_URL, 'Action:', action);
            
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action, ...data })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('API Response:', result);
            return result;
            
        } catch (error) {
            console.error('API Error:', error);
            
            // Error handling yang lebih spesifik
            let errorMessage = 'Koneksi ke server gagal';
            
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                errorMessage = 'Tidak dapat terhubung ke server. Pastikan koneksi internet aktif.';
            } else if (error.message.includes('HTTP 404')) {
                errorMessage = 'API endpoint tidak ditemukan. Periksa konfigurasi server.';
            } else if (error.message.includes('HTTP 500')) {
                errorMessage = 'Server mengalami error internal. Coba lagi nanti.';
            } else if (error.message.includes('NetworkError')) {
                errorMessage = 'Error jaringan. Periksa koneksi internet Anda.';
            }
            
            return { success: false, message: errorMessage, debug: error.message };
        }
    }

    // Muat model face-api.js
    async function loadModels() {
        try {
            statusDiv.textContent = "Memuat model AI...";
            statusDiv.className = "status loading";
            
            await faceapi.nets.tinyFaceDetector.loadFromUri('models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('models');
            await faceapi.nets.faceRecognitionNet.loadFromUri('models');
            
            statusDiv.className = "status success";
            statusDiv.textContent = "Sistem siap digunakan!";
            
            registerBtn.disabled = false;
            attendanceBtn.disabled = false;
            
            startVideo();
        } catch (error) {
            statusDiv.className = "status error";
            statusDiv.textContent = `Gagal memuat model: ${error.message}`;
            console.error(error);
        }
    }

    // Fungsi untuk mendeteksi perangkat mobile
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
               (navigator.maxTouchPoints && navigator.maxTouchPoints > 2 && /MacIntel/.test(navigator.platform));
    }

    // Fungsi untuk mendeteksi iOS
    function isIOSDevice() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) || 
               (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    // Mulai kamera
    async function startVideo() {
        try {
            // Cek dukungan browser
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error("Browser tidak mendukung akses kamera");
            }

            const isMobile = isMobileDevice();
            const isIOS = isIOSDevice();
            
            // Konfigurasi kamera berdasarkan perangkat
            let constraints = {
                audio: false,
                video: {
                    facingMode: isMobile ? 'user' : 'user', // Front camera untuk mobile
                    width: isMobile ? { ideal: 640, max: 1280 } : { ideal: 720 },
                    height: isMobile ? { ideal: 480, max: 720 } : { ideal: 560 }
                }
            };

            // Pengaturan khusus iOS
            if (isIOS) {
                constraints.video.frameRate = { ideal: 30, max: 30 };
            }

            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            
            // Pengaturan video untuk mobile
            if (isMobile) {
                video.setAttribute('playsinline', 'true');
                video.setAttribute('webkit-playsinline', 'true');
                video.muted = true;
            }
            
        } catch (error) {
            statusDiv.className = "status error";
            let errorMessage = "Gagal mengakses kamera: ";
            
            switch(error.name) {
                case 'NotAllowedError':
                    errorMessage += "Izin kamera ditolak. Silakan izinkan akses kamera.";
                    break;
                case 'NotFoundError':
                    errorMessage += "Kamera tidak ditemukan.";
                    break;
                case 'NotSupportedError':
                    errorMessage += "Browser tidak mendukung akses kamera.";
                    break;
                default:
                    errorMessage += error.message;
            }
            
            statusDiv.textContent = errorMessage;
            
            // Tambahkan tombol retry untuk mobile
            if (isMobileDevice()) {
                const retryBtn = document.createElement('button');
                retryBtn.textContent = 'Coba Lagi';
                retryBtn.onclick = () => {
                    retryBtn.remove();
                    startVideo();
                };
                statusDiv.appendChild(retryBtn);
            }
        }
    }
// Variabel global untuk menyimpan face descriptor
let currentFaceDescriptor = null;

// Modifikasi fungsi registerBtn click handler
registerBtn.addEventListener('click', async () => {
    try {
        statusDiv.textContent = "Mendeteksi wajah...";
        statusDiv.className = "status loading";
        
        // Deteksi wajah dan dapatkan descriptor
        const detection = await faceapi.detectSingleFace(
            video, 
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks().withFaceDescriptor();
        
        if (!detection) {
            throw new Error("Wajah tidak terdeteksi!");
        }
        
        // Simpan descriptor untuk digunakan nanti
        currentFaceDescriptor = detection.descriptor;
        
        // Tampilkan dialog registrasi
        document.getElementById('registrationDialog').style.display = 'block';
        
        // Update status
        statusDiv.textContent = "Wajah terdeteksi, silakan isi form";
        statusDiv.className = "status success";
        
    } catch (error) {
        statusDiv.textContent = `Error: ${error.message}`;
        statusDiv.className = "status error";
    }
});

// Handle form submission
document.getElementById('submitRegistration').addEventListener('click', async () => {
    const name = document.getElementById('userName').value;
    const NIP = document.getElementById('userNIP').value;
    const jabatan = document.getElementById('userJabatan').value;

    if (!name || !jabatan || !NIP) {
        alert("Nama, NIP dan jabatan harus diisi!");
        return;
    }

    try {
        statusDiv.textContent = "Menyimpan data...";
        statusDiv.className = "status loading";
        
        // Pastikan kita memiliki descriptor wajah
        if (!currentFaceDescriptor) {
            throw new Error("Descriptor wajah tidak tersedia!");
        }
        
        // Convert descriptor ke array untuk disimpan di database
        const descriptorArray = Array.from(currentFaceDescriptor);
        
        const response = await callAPI('register', {
            name: name,
            NIP : NIP,
            jabatan: jabatan,
            face_descriptor: JSON.stringify(descriptorArray)
        });
        
        if (response.success) {
            statusDiv.textContent = `${name} berhasil didaftarkan!`;
            statusDiv.className = "status success";
            showPopup(`${name} berhasil didaftarkan`);
            
            // Reset dialog
            document.getElementById('userName').value = '';
            document.getElementById('userNIP').value = '';
            document.getElementById('userJabatan').value = '';
            document.getElementById('registrationDialog').style.display = 'none';
            currentFaceDescriptor = null;
        } else {
            throw new Error(response.message || "Gagal menyimpan data");
        }
    } catch (error) {
        statusDiv.textContent = `Error: ${error.message}`;
        statusDiv.className = "status error";
    }
});

// Handle cancel button
document.getElementById('cancelRegistration').addEventListener('click', () => {
    document.getElementById('registrationDialog').style.display = 'none';
    currentFaceDescriptor = null;
    statusDiv.textContent = "Sistem siap digunakan";
    statusDiv.className = "status success";
});

function showPopup(message, isSuccess = true) {
    const popup = document.getElementById('notificationPopup');
    const messageEl = document.getElementById('popupMessage');
    
    // Set pesan dan warna berdasarkan status
    messageEl.textContent = message;
    popup.style.background = isSuccess ? '#2ecc71' : '#e74c3c';
    
    // Tampilkan popup
    popup.style.display = 'block';
    
    // Sembunyikan setelah 3 detik
    setTimeout(() => {
        popup.style.display = 'none';
    }, 3000);
}

// Debug function untuk test koneksi
async function testConnection() {
    const debugBtn = document.getElementById('debugBtn');
    debugBtn.textContent = 'Testing...';
    debugBtn.disabled = true;
    
    try {
        statusDiv.textContent = `Testing koneksi ke: ${API_BASE_URL}`;
        statusDiv.className = "status loading";
        
        const result = await callAPI('test_connection');
        
        if (result.success) {
            statusDiv.textContent = "✅ Koneksi berhasil!";
            statusDiv.className = "status success";
            showPopup("Koneksi ke server berhasil!", true);
        } else {
            statusDiv.textContent = `❌ Koneksi gagal: ${result.message}`;
            statusDiv.className = "status error";
            showPopup(`Koneksi gagal: ${result.message}`, false);
        }
        
        // Show debug info
        console.log('Debug Info:', {
            url: API_BASE_URL,
            host: window.location.hostname,
            port: window.location.port,
            protocol: window.location.protocol,
            userAgent: navigator.userAgent,
            result: result
        });
        
    } catch (error) {
        statusDiv.textContent = `❌ Error: ${error.message}`;
        statusDiv.className = "status error";
        showPopup(`Error: ${error.message}`, false);
    }
    
    debugBtn.textContent = 'Test Koneksi';
    debugBtn.disabled = false;
}

// Event listener untuk debug button
document.getElementById('debugBtn').addEventListener('click', testConnection);

  // Jalankan aplikasi
    loadModels();
</script>

