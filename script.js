const pageLoadTime = new Date(); // Waktu saat halaman dibuka
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
        loadAttendanceData();
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
            attendanceTable.innerHTML = ''; // Kosongkan tabel sebelum menambahkan data
            
            // Filter data absensi berdasarkan waktu halaman dibuka
            const filteredData = response.data.filter(record => {
                const attendanceTime = new Date(record.attendance_time);
                return attendanceTime > pageLoadTime; // Hanya ambil yang lebih baru dari waktu halaman dibuka
            });
            
            if (filteredData.length > 0) {
                filteredData.forEach(record => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${record.name}</td>
                        <td>${record.NIDN}</td>
                        <td>${record.jabatan}</td>
                        <td>${new Date(record.attendance_time).toLocaleString()}</td>
                    `;
                    attendanceTable.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="4" style="text-align:center;">Tidak ada data absensi</td>';
                attendanceTable.appendChild(row);
            }
        }
    } catch (error) {
        console.error('Gagal memuat data:', error);
    }
}




// Jalankan aplikasi
loadModels();
