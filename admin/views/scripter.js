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
        loadAttendanceData();
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

// Daftarkan wajah baru ke database
registerBtn.addEventListener('click', async () => {
    try {
        statusDiv.textContent = "Mendeteksi wajah...";
        statusDiv.className = "status loading";
        
        const detection = await faceapi.detectSingleFace(
            video, 
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks().withFaceDescriptor();
        
        if (!detection) {
            throw new Error("Wajah tidak terdeteksi!");
        }
        
        const name = prompt("Masukkan nama user:");
        if (!name) return;
        
        // Convert descriptor ke array untuk disimpan di database
        const descriptorArray = Array.from(detection.descriptor);
        
        const response = await callAPI('register', {
            name: name,
            face_descriptor: JSON.stringify(descriptorArray)
        });
        
        if (response.success) {
            showPopup(`Berhasil Melakukan Absensi: ${name}`);
            statusDiv.textContent = `${name} berhasil didaftarkan!`;
            statusDiv.className = "status success";
            window.location.href = 'admin.php';
        } else {
            throw new Error(response.message || "Gagal menyimpan data");
        }
    } catch (error) {
        statusDiv.textContent = `Error: ${error.message}`;
        statusDiv.className = "status error";
    }
});

// Ambil absensi
attendanceBtn.addEventListener('click', async () => {
    try {
        statusDiv.textContent = "Mengenali wajah...";
        statusDiv.className = "status loading";
        
        const detection = await faceapi.detectSingleFace(
            video, 
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks().withFaceDescriptor();
        
        if (!detection) {
            throw new Error("Wajah tidak terdeteksi!");
        }
        
        // Convert descriptor ke array
        const descriptorArray = Array.from(detection.descriptor);
        
        const response = await callAPI('recognize', {
            face_descriptor: JSON.stringify(descriptorArray)
        });
        
        if (response.success) {
            if (response.data.recognized) {
                showPopup(`Berhasil Melakukan Absensi: ${response.data.user_name}`);

                statusDiv.textContent = `Absen berhasil: ${response.data.user_name}`;
                statusDiv.className = "status success";
            } else {
                statusDiv.textContent = "Wajah tidak dikenali!";
                statusDiv.className = "status error";
            }
            loadAttendanceData();
        } else {
            throw new Error(response.message || "Proses pengenalan gagal");
        }
    } catch (error) {
        statusDiv.textContent = `Error: ${error.message}`;
        statusDiv.className = "status error";
    }
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

// Muat data absensi dari database
async function loadAttendanceData() {
    try {
        const response = await callAPI('get_attendance');
        
        if (response.success) {
            attendanceTable.innerHTML = '';
            
            if (response.data.length > 0) {
                response.data.forEach(record => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${record.name}</td>
                        <td>${new Date(record.attendance_time).toLocaleString()}</td>
                    `;
                    attendanceTable.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="2" style="text-align:center;">Belum ada data absensi</td>';
                attendanceTable.appendChild(row);
            }
        }
    } catch (error) {
        console.error('Gagal memuat data:', error);
    }
}

// Jalankan aplikasi
loadModels();
