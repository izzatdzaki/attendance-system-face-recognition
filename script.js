const pageLoadTime = new Date(); // Waktu saat halaman dibuka

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

// Variabel untuk menyimpan lokasi user
let userLocation = null;

// Elements
const video = document.getElementById('video');
const registerBtn = document.getElementById('registerBtn');
const clockInBtn = document.getElementById('clockInBtn');
const clockOutBtn = document.getElementById('clockOutBtn');
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

// Fungsi untuk mendapatkan lokasi user dengan retry mechanism
async function getUserLocation(retryCount = 0) {
    const maxRetries = 3;
    
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation tidak didukung oleh browser ini. Silakan gunakan browser yang mendukung GPS.'));
            return;
        }

        // Opsi yang berbeda untuk setiap retry
        const options = {
            enableHighAccuracy: retryCount === 0, // High accuracy hanya di percobaan pertama
            timeout: retryCount === 0 ? 15000 : 30000, // Timeout lebih lama untuk retry
            maximumAge: retryCount === 0 ? 60000 : 300000 // Cache lebih lama untuk retry
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const location = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                userLocation = location;
                console.log('Lokasi berhasil didapat:', location);
                resolve(location);
            },
            async (error) => {
                console.error('Geolocation error:', error);
                
                let errorMessage = 'Gagal mendapatkan lokasi: ';
                let shouldRetry = false;
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Akses lokasi ditolak. Silakan:\n1. Klik ikon kunci/lokasi di address bar\n2. Pilih "Allow" untuk lokasi\n3. Refresh halaman';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Sinyal GPS tidak tersedia. Pastikan:\n1. GPS/Location Services aktif di perangkat\n2. Anda berada di area dengan sinyal GPS yang baik\n3. Tidak menggunakan VPN yang memblokir lokasi';
                        shouldRetry = retryCount < maxRetries;
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'Timeout mendapatkan lokasi. Mencoba lagi...';
                        shouldRetry = retryCount < maxRetries;
                        break;
                    default:
                        errorMessage += 'Error tidak diketahui. Mencoba lagi...';
                        shouldRetry = retryCount < maxRetries;
                        break;
                }
                
                if (shouldRetry) {
                    console.log(`Retry attempt ${retryCount + 1}/${maxRetries}`);
                    try {
                        const result = await getUserLocation(retryCount + 1);
                        resolve(result);
                    } catch (retryError) {
                        reject(new Error(errorMessage));
                    }
                } else {
                    reject(new Error(errorMessage));
                }
            },
            options
        );
    });
}

// Fungsi untuk meminta izin lokasi saat halaman dimuat
async function requestLocationPermission() {
    try {
        statusDiv.textContent = "Meminta izin akses lokasi...";
        statusDiv.className = "status loading";
        
        // Tampilkan instruksi kepada user
        showPopup('Sistem akan meminta akses lokasi. Silakan klik "Allow/Izinkan" untuk melanjutkan.', true);
        
        await getUserLocation();
        
        statusDiv.textContent = "Lokasi berhasil didapatkan!";
        statusDiv.className = "status success";
        
        console.log('Lokasi user:', userLocation);
        showPopup('Lokasi berhasil didapatkan! Sistem absensi siap digunakan.', true);
    } catch (error) {
        console.error('Error getting location:', error);
        statusDiv.textContent = "Error: " + error.message;
        statusDiv.className = "status error";
        
        // Tampilkan peringatan dengan instruksi detail
        const detailedMessage = error.message + '\n\nSistem absensi memerlukan akses lokasi untuk validasi kehadiran. Tanpa lokasi, absensi tidak dapat dilakukan.';
        showPopup(detailedMessage, false);
        
        // Tambahkan tombol retry
        addRetryLocationButton();
    }
}

// Fungsi untuk menambahkan tombol retry lokasi
function addRetryLocationButton() {
    // Hapus tombol retry yang sudah ada
    const existingRetryBtn = document.getElementById('retryLocationBtn');
    const existingManualBtn = document.getElementById('manualLocationBtn');
    if (existingRetryBtn) {
        existingRetryBtn.remove();
    }
    if (existingManualBtn) {
        existingManualBtn.remove();
    }
    
    // Container untuk tombol
    const buttonContainer = document.createElement('div');
    buttonContainer.id = 'locationButtonContainer';
    buttonContainer.style.margin = '10px';
    buttonContainer.style.textAlign = 'center';
    
    // Buat tombol retry
    const retryBtn = document.createElement('button');
    retryBtn.id = 'retryLocationBtn';
    retryBtn.textContent = 'Coba Lagi GPS';
    retryBtn.className = 'btn btn-warning';
    retryBtn.style.margin = '5px';
    
    retryBtn.addEventListener('click', async () => {
        buttonContainer.remove();
        await requestLocationPermission();
    });
    
    // Buat tombol manual location
    const manualBtn = document.createElement('button');
    manualBtn.id = 'manualLocationBtn';
    manualBtn.textContent = 'Input Lokasi Manual';
    manualBtn.className = 'btn btn-info';
    manualBtn.style.margin = '5px';
    
    manualBtn.addEventListener('click', () => {
        buttonContainer.remove();
        showManualLocationModal();
    });
    
    buttonContainer.appendChild(retryBtn);
    buttonContainer.appendChild(manualBtn);
    
    // Tambahkan setelah status div
    statusDiv.parentNode.insertBefore(buttonContainer, statusDiv.nextSibling);
}

// Fungsi untuk menampilkan modal input lokasi manual
function showManualLocationModal() {
    // Buat modal
    const modal = document.createElement('div');
    modal.id = 'manualLocationModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 10px;
        max-width: 400px;
        width: 90%;
    `;
    
    modalContent.innerHTML = `
        <h3>Input Lokasi Manual</h3>
        <p>Masukkan koordinat lokasi Anda:</p>
        <div style="margin: 10px 0;">
            <label>Latitude:</label>
            <input type="number" id="manualLat" step="any" placeholder="-6.200000" style="width: 100%; padding: 5px; margin: 5px 0;">
        </div>
        <div style="margin: 10px 0;">
            <label>Longitude:</label>
            <input type="number" id="manualLng" step="any" placeholder="106.816666" style="width: 100%; padding: 5px; margin: 5px 0;">
        </div>
        <p style="font-size: 12px; color: #666;">
            Tip: Buka Google Maps, klik kanan pada lokasi Anda, dan salin koordinat yang muncul.
        </p>
        <div style="text-align: center; margin-top: 15px;">
            <button id="saveManualLocation" class="btn btn-primary" style="margin: 5px;">Simpan</button>
            <button id="cancelManualLocation" class="btn btn-secondary" style="margin: 5px;">Batal</button>
        </div>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    // Event listeners
    document.getElementById('saveManualLocation').addEventListener('click', () => {
        const lat = parseFloat(document.getElementById('manualLat').value);
        const lng = parseFloat(document.getElementById('manualLng').value);
        
        if (isNaN(lat) || isNaN(lng)) {
            alert('Silakan masukkan koordinat yang valid!');
            return;
        }
        
        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            alert('Koordinat tidak valid! Latitude: -90 to 90, Longitude: -180 to 180');
            return;
        }
        
        // Set lokasi manual
        userLocation = {
            latitude: lat,
            longitude: lng,
            accuracy: 0,
            manual: true
        };
        
        statusDiv.textContent = "Lokasi manual berhasil disimpan!";
        statusDiv.className = "status success";
        
        showPopup('Lokasi manual berhasil disimpan! Sistem absensi siap digunakan.', true);
        
        modal.remove();
    });
    
    document.getElementById('cancelManualLocation').addEventListener('click', () => {
        modal.remove();
        addRetryLocationButton();
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
            addRetryLocationButton();
        }
    });
}

// Muat model face-api.js
async function loadModels() {
    try {
        statusDiv.textContent = "Memuat model AI...";
        statusDiv.className = "status loading";
        
        await faceapi.nets.tinyFaceDetector.loadFromUri('models');
        await faceapi.nets.ssdMobilenetv1.loadFromUri('models');
        await faceapi.nets.faceLandmark68Net.loadFromUri('models');
        await faceapi.nets.faceRecognitionNet.loadFromUri('models');
        
        statusDiv.className = "status success";
        statusDiv.textContent = "Sistem siap digunakan!";
        
        registerBtn.disabled = false;
        clockInBtn.disabled = false;
        clockOutBtn.disabled = false;
        
        startCamera();
        loadAttendanceData();
        
        // Minta izin lokasi
        await requestLocationPermission();
    } catch (error) {
        statusDiv.className = "status error";
        statusDiv.textContent = `Gagal memuat model: ${error.message}`;
        console.error(error);
    }
}

// Buat canvas untuk overlay deteksi
const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');
canvas.style.position = 'absolute';
canvas.style.top = '0';
canvas.style.left = '0';
canvas.style.pointerEvents = 'none';
video.parentElement.style.position = 'relative';
video.parentElement.appendChild(canvas);

// Mobile camera helper functions
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

function isIOSDevice() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent);
}

// Check camera permissions for mobile
async function checkCameraPermissions() {
    if (!navigator.permissions) {
        return 'unknown';
    }
    
    try {
        const permission = await navigator.permissions.query({ name: 'camera' });
        return permission.state;
    } catch (error) {
        console.log('Permission API not supported');
        return 'unknown';
    }
}

// Mobile-specific camera initialization
async function initializeMobileCamera() {
    const isMobile = isMobileDevice();
    const isIOS = isIOSDevice();
    
    if (isMobile) {
        // Add mobile-specific video attributes
        video.setAttribute('playsinline', 'true');
        video.setAttribute('webkit-playsinline', 'true');
        
        if (isIOS) {
            // iOS specific fixes
            video.muted = true;
            video.setAttribute('muted', 'true');
        }
        
        // Check permissions first
        const permissionState = await checkCameraPermissions();
        if (permissionState === 'denied') {
            statusDiv.className = "status error";
            statusDiv.textContent = "Akses kamera ditolak. Silakan izinkan akses kamera di pengaturan browser dan refresh halaman.";
            return false;
        }
    }
    
    return true;
}

// Mulai kamera
async function startCamera() {
    try {
        // Check if mediaDevices is supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Camera API not supported');
        }

        // Initialize mobile camera settings
        const canProceed = await initializeMobileCamera();
        if (!canProceed) {
            return;
        }

        // Mobile-friendly camera constraints
        const isMobile = isMobileDevice();
        const isIOS = isIOSDevice();
        
        let constraints;
        if (isMobile) {
            // Mobile constraints - more flexible
            constraints = {
                video: {
                    facingMode: 'user', // Front camera for selfie
                    width: { ideal: 640, max: 1280, min: 320 },
                    height: { ideal: 480, max: 720, min: 240 }
                },
                audio: false
            };
            
            // iOS specific constraints
            if (isIOS) {
                constraints.video.frameRate = { ideal: 15, max: 30 };
            }
        } else {
            // Desktop constraints
            constraints = {
                video: { 
                    width: { ideal: 720, max: 1280 }, 
                    height: { ideal: 560, max: 720 }
                }, 
                audio: false 
            };
        }

        statusDiv.textContent = "Meminta izin kamera...";
        statusDiv.className = "status loading";

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = stream;
        
        video.addEventListener('loadedmetadata', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.style.width = video.offsetWidth + 'px';
            canvas.style.height = video.offsetHeight + 'px';
        });
        
        statusDiv.textContent = "Kamera siap";
        statusDiv.className = "status success";
        
        // Mulai deteksi real-time untuk debugging
        startRealTimeDetection();
    } catch (error) {
        console.error('Camera error:', error);
        statusDiv.className = "status error";
        
        // Better error messages for mobile
        if (error.name === 'NotAllowedError') {
            statusDiv.textContent = "Izin kamera ditolak! Silakan izinkan akses kamera di pengaturan browser.";
        } else if (error.name === 'NotFoundError') {
            statusDiv.textContent = "Kamera tidak ditemukan! Pastikan perangkat memiliki kamera.";
        } else if (error.name === 'NotSupportedError') {
            statusDiv.textContent = "Browser tidak mendukung akses kamera!";
        } else if (error.name === 'NotReadableError') {
            statusDiv.textContent = "Kamera sedang digunakan aplikasi lain!";
        } else {
            statusDiv.textContent = "Error kamera: " + error.message;
        }
        
        // Show retry button for mobile
        if (isMobileDevice()) {
            setTimeout(() => {
                statusDiv.innerHTML += '<br><button onclick="startCamera()" style="margin-top: 10px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px;">Coba Lagi</button>';
            }, 1000);
        }
    }
}

// Deteksi real-time untuk debugging visual
async function startRealTimeDetection() {
    setInterval(async () => {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            const detection = await faceapi.detectSingleFace(
                video, 
                new faceapi.TinyFaceDetectorOptions({
                    inputSize: 416,
                    scoreThreshold: 0.3
                })
            );
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            if (detection) {
                // Gambar kotak deteksi
                const box = detection.box;
                ctx.strokeStyle = '#00ff00';
                ctx.lineWidth = 2;
                ctx.strokeRect(box.x, box.y, box.width, box.height);
                
                // Tambahkan teks confidence
                ctx.fillStyle = '#00ff00';
                ctx.font = '16px Arial';
                ctx.fillText(`Wajah Terdeteksi (${(detection.score * 100).toFixed(1)}%)`, box.x, box.y - 10);
            }
        }
    }, 100); // Update setiap 100ms
}

// Fungsi untuk deteksi wajah dengan fallback
async function detectFaceWithFallback(videoElement) {
    // Coba dengan TinyFaceDetector (lebih cepat)
    let detection = await faceapi.detectSingleFace(
        videoElement, 
        new faceapi.TinyFaceDetectorOptions({
            inputSize: 416,
            scoreThreshold: 0.3
        })
    ).withFaceLandmarks().withFaceDescriptor();
    
    // Jika gagal, coba dengan SSD MobileNet (lebih akurat)
    if (!detection) {
        detection = await faceapi.detectSingleFace(
            videoElement, 
            new faceapi.SsdMobilenetv1Options({
                minConfidence: 0.3
            })
        ).withFaceLandmarks().withFaceDescriptor();
    }
    
    return detection;
}

// Daftarkan wajah baru ke database
registerBtn.addEventListener('click', async () => {
    try {
        statusDiv.textContent = "Mendeteksi wajah...";
        statusDiv.className = "status loading";
        
        const detection = await detectFaceWithFallback(video);
        
        if (!detection) {
            throw new Error("Wajah tidak terdeteksi! Pastikan wajah Anda terlihat jelas di kamera dan pencahayaan cukup.");
        }
        
        const name = prompt("Masukkan nama user:");
        if (!name) return;
        
        const jabatan = prompt("Masukkan jabatan:");
        if (!jabatan) return;
        
        const nip = prompt("Masukkan NIP:");
        if (!nip) return;
        
        // Convert descriptor ke array untuk disimpan di database
        const descriptorArray = Array.from(detection.descriptor);
        
        const response = await callAPI('register', {
            name: name,
            jabatan: jabatan,
            NIP: nip,
            face_descriptor: JSON.stringify(descriptorArray)
        });
        
        if (response.success) {
            statusDiv.textContent = `${name} berhasil didaftarkan!`;
            statusDiv.className = "status success";
            loadAttendanceData();
        } else {
            throw new Error(response.message || "Gagal menyimpan data");
        }
    } catch (error) {
        statusDiv.textContent = `Error: ${error.message}`;
        statusDiv.className = "status error";
    }
});

// Fungsi umum untuk absensi
async function performAttendance(action, actionText) {
    try {
        statusDiv.textContent = "Memvalidasi lokasi...";
        statusDiv.className = "status loading";
        
        // Dapatkan lokasi terbaru
        let currentLocation = userLocation;
        if (!currentLocation) {
            try {
                currentLocation = await getUserLocation();
            } catch (locationError) {
                throw new Error("Gagal mendapatkan lokasi: " + locationError.message);
            }
        }
        
        statusDiv.textContent = "Mengenali wajah...";
        statusDiv.className = "status loading";
        
        const detection = await detectFaceWithFallback(video);
        
        if (!detection) {
            throw new Error("Wajah tidak terdeteksi! Pastikan wajah Anda terlihat jelas di kamera dan pencahayaan cukup.");
        }
        
        // Convert descriptor ke array
        const descriptorArray = Array.from(detection.descriptor);
        
        statusDiv.textContent = "Memproses absensi...";
        
        const response = await callAPI(action, {
            face_descriptor: JSON.stringify(descriptorArray),
            latitude: currentLocation.latitude,
            longitude: currentLocation.longitude
        });
        
        if (response.success) {
            if (response.data.recognized) {
                const locationInfo = response.data.location ? ` di ${response.data.location}` : '';
                const distanceInfo = response.data.distance ? ` (jarak: ${response.data.distance})` : '';
                
                showPopup(`Berhasil ${actionText}: ${response.data.user_name}${locationInfo}${distanceInfo}`);
                statusDiv.textContent = `${actionText} berhasil: ${response.data.user_name}${locationInfo}`;
                statusDiv.className = "status success";
            } else {
                statusDiv.textContent = "Wajah tidak dikenali!";
                statusDiv.className = "status error";
            }
        } else {
            statusDiv.textContent = response.message || `Gagal melakukan ${actionText.toLowerCase()}!`;
            statusDiv.className = "status error";
            
            // Tampilkan popup untuk error lokasi
            showPopup(response.message || `Gagal melakukan ${actionText.toLowerCase()}!`, false);
        }
        
        loadAttendanceData();
    } catch (error) {
        statusDiv.textContent = error.message;
        statusDiv.className = "status error";
    }
}

// Jam Datang
clockInBtn.addEventListener('click', async () => {
    await performAttendance('clock_in', 'Jam Datang');
});

// Jam Pulang
clockOutBtn.addEventListener('click', async () => {
    await performAttendance('clock_out', 'Jam Pulang');
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
            attendanceTable.innerHTML = ''; // Kosongkan tabel sebelum menambahkan data
            
            if (response.data.length > 0) {
                response.data.forEach(record => {
                    const row = document.createElement('tr');
                    
                    // Format tanggal
                    const tanggal = record.tanggal_absen ? 
                        new Date(record.tanggal_absen).toLocaleDateString('id-ID') : '-';
                    
                    // Format jam datang
                    const jamDatang = record.jam_datang ? 
                        new Date(`${record.tanggal_absen} ${record.jam_datang}`).toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '-';
                    
                    // Format jam pulang
                    const jamPulang = record.jam_pulang ? 
                        new Date(`${record.tanggal_absen} ${record.jam_pulang}`).toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '-';
                    
                    // Format jam lembur mulai
                    const jamLemburMulai = record.jam_lembur_mulai ? 
                        new Date(`${record.tanggal_absen} ${record.jam_lembur_mulai}`).toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '-';
                    
                    // Format jam lembur selesai
                    const jamLemburSelesai = record.jam_lembur_selesai ? 
                        new Date(`${record.tanggal_absen} ${record.jam_lembur_selesai}`).toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '-';
                    
                    // Status dengan warna
                    let statusText = record.status_absen || 'Belum Lengkap';
                    let statusColor = '';
                    
                    if (statusText === 'lengkap') {
                        statusColor = 'color: green; font-weight: bold;';
                        statusText = 'Lengkap';
                    } else if (statusText === 'datang') {
                        statusColor = 'color: orange; font-weight: bold;';
                        statusText = 'Datang';
                    } else {
                        statusColor = 'color: gray;';
                    }
                    
                    // Status lembur dengan warna
                    let statusLemburText = record.status_lembur || '-';
                    let statusLemburColor = '';
                    
                    if (statusLemburText === 'lembur_selesai') {
                        statusLemburColor = 'color: green; font-weight: bold;';
                        statusLemburText = 'Selesai';
                    } else if (statusLemburText === 'lembur_mulai') {
                        statusLemburColor = 'color: orange; font-weight: bold;';
                        statusLemburText = 'Sedang Lembur';
                    } else {
                        statusLemburColor = 'color: gray;';
                        statusLemburText = '-';
                    }
                    
                    row.innerHTML = `
                        <td data-label="Nama">${record.name}</td>
                        <td data-label="NIP">${record.NIP}</td>
                        <td data-label="Jabatan">${record.jabatan}</td>
                        <td data-label="Tanggal">${tanggal}</td>
                        <td data-label="Jam Datang">${jamDatang}</td>
                        <td data-label="Jam Pulang">${jamPulang}</td>
                        <td data-label="Status" style="${statusColor}">${statusText}</td>
                        <td data-label="Lembur Mulai">${jamLemburMulai}</td>
                        <td data-label="Lembur Selesai">${jamLemburSelesai}</td>
                        <td data-label="Status Lembur" style="${statusLemburColor}">${statusLemburText}</td>
                    `;
                    attendanceTable.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="10" style="text-align:center;">Tidak ada data absensi</td>';
                attendanceTable.appendChild(row);
            }
        }
    } catch (error) {
        console.error('Gagal memuat data:', error);
    }
}




// Fungsi debug untuk test koneksi
async function testConnection() {
    const statusDiv = document.querySelector('.status');
    statusDiv.textContent = 'Testing koneksi...';
    statusDiv.className = 'status loading';
    
    try {
        console.log('=== DEBUG INFO ===');
        console.log('API URL:', API_BASE_URL);
        console.log('Host:', window.location.host);
        console.log('Port:', window.location.port);
        console.log('Protocol:', window.location.protocol);
        console.log('User Agent:', navigator.userAgent);
        
        const result = await callAPI('test_connection');
        
        if (result.success) {
            statusDiv.textContent = 'Koneksi berhasil! ✅';
            statusDiv.className = 'status success';
            
            Swal.fire({
                icon: 'success',
                title: 'Koneksi Berhasil!',
                html: `
                    <p><strong>Server Time:</strong> ${result.server_time}</p>
                    <p><strong>API URL:</strong> ${API_BASE_URL}</p>
                    <p><strong>Host:</strong> ${window.location.host}</p>
                `,
                confirmButtonText: 'OK'
            });
        } else {
            throw new Error(result.message);
        }
        
        console.log('Test result:', result);
        
    } catch (error) {
        console.error('Test connection failed:', error);
        statusDiv.textContent = 'Test koneksi gagal ❌';
        statusDiv.className = 'status error';
        
        Swal.fire({
            icon: 'error',
            title: 'Test Koneksi Gagal',
            html: `
                <p><strong>Error:</strong> ${error.message}</p>
                <p><strong>API URL:</strong> ${API_BASE_URL}</p>
                <p><strong>Host:</strong> ${window.location.host}</p>
                <p><strong>Debug:</strong> ${error.debug || 'N/A'}</p>
            `,
            confirmButtonText: 'OK'
        });
    }
}

// Event listener untuk tombol debug
document.addEventListener('DOMContentLoaded', function() {
    const debugBtn = document.getElementById('debugBtn');
    if (debugBtn) {
        debugBtn.addEventListener('click', testConnection);
    }
});

// Jalankan aplikasi
loadModels();
