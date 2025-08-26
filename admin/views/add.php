<style>
    .container {
    max-width: 500px;
    margin: 0 auto;
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
    width: 70%;
    max-width: 360px;
    margin: 20px auto;
}

#video {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
        <video id="video" autoplay muted></video>
    </div>

    <div class="controls">
        <button id="registerBtn" disabled>Daftarkan User</button>
        <button id="attendanceBtn" style="display: none;" disabled>Ambil Absensi</button>
    </div>
    
    <div id="notificationPopup" class="notification-popup">
        <span id="popupMessage">Berhasil Mendaftarkan User</span>
    </div>

    <div id="registrationDialog" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000;">
    <div class="dialog-content">
        <h3>Daftarkan User</h3>
        <label for="userName">Nama:</label>
        <input type="text" id="userName" placeholder="Masukkan nama" required>
        <label for="userNIDN">NIDN:</label>
        <input type="text" id="userNIDN" placeholder="Masukkan NIDN" required>
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
    // Konfigurasi Database
    const API_BASE_URL = 'http://localhost/face/api.php'; // Sesuaikan dengan endpoint PHP Anda

    // Elements
    const video = document.getElementById('video');
    const registerBtn = document.getElementById('registerBtn');
    const attendanceBtn = document.getElementById('attendanceBtn');
    const statusDiv = document.querySelector('.status');
    const attendanceTable = document.querySelector('#attendanceTable tbody');

    // Fungsi untuk memanggil API
    async function callAPI(action, data = {}) {
        try {
            const response = await fetch(API_BASE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action, ...data })
            });
            
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Koneksi ke server gagal' };
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

    // Mulai kamera
    async function startVideo() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { width: 720, height: 560 }, 
                audio: false 
            });
            video.srcObject = stream;
        } catch (error) {
            statusDiv.className = "status error";
            statusDiv.textContent = "Izin kamera ditolak!";
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
    const NIDN = document.getElementById('userNIDN').value;
    const jabatan = document.getElementById('userJabatan').value;

    if (!name || !jabatan || !NIDN) {
        alert("Nama, NIDN dan jabatan harus diisi!");
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
            NIDN : NIDN,
            jabatan: jabatan,
            face_descriptor: JSON.stringify(descriptorArray)
        });
        
        if (response.success) {
            statusDiv.textContent = `${name} berhasil didaftarkan!`;
            statusDiv.className = "status success";
            showPopup(`${name} berhasil didaftarkan`);
            
            // Reset dialog
            document.getElementById('userName').value = '';
            document.getElementById('userNIDN').value = '';
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

  // Jalankan aplikasi
    loadModels();
</script>

