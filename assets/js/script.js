document.addEventListener('DOMContentLoaded', () => {
    const tglMulai = document.getElementById('tgl_mulai');
    const tglSelesai = document.getElementById('tgl_selesai');
    const totalHariInput = document.getElementById('total_hari');
    const jenisSelect = document.getElementById('jenis_id');
    const fileGroup = document.getElementById('file_bukti_group');
    const fileInput = document.getElementById('file_bukti');

    // Sembunyikan/Tampilkan Field Upload Berdasarkan Jenis Pengajuan (Sakit = ID 3)
    if (jenisSelect) {
        jenisSelect.addEventListener('change', (e) => {
            if (e.target.value === '3') { 
                fileGroup.style.display = 'block';
                fileInput.setAttribute('required', 'required');
            } else {
                fileGroup.style.display = 'none';
                fileInput.removeAttribute('required');
            }
        });
    }

    // Hitung Hari Kerja Secara Realtime Menggunakan Fetch API
    function calculateDays() {
        if (tglMulai.value && tglSelesai.value) {
            fetch(`../api/hitung_hari.php?tgl_mulai=${tglMulai.value}&tgl_selesai=${tglSelesai.value}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        totalHariInput.value = data.total_hari;
                    } else {
                        alert(data.message);
                        tglSelesai.value = '';
                        totalHariInput.value = '';
                    }
                })
                .catch(err => console.error('Error:', err));
        }
    }

    if (tglMulai && tglSelesai) {
        tglMulai.addEventListener('change', calculateDays);
        tglSelesai.addEventListener('change', calculateDays);
    }
});