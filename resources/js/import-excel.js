
importExcel();

function importExcel() {
    const form = document.getElementById('form-import-laporan');

    if (!form || form.dataset.importInitialized === 'true') {
        return;
    }

    form.dataset.importInitialized = 'true';

    const fileInput = document.getElementById('excel-file-input');
    const dropzone = document.getElementById('excel-dropzone');
    const btnUpload = document.getElementById('btn-trigger-upload');
    const btnReselect = document.getElementById('btn-reselect-file');
    const btnCancel = document.getElementById('btn-cancel-file');
    const btnSubmit = document.getElementById('btn-submit-import');
    const btnSubmitText = document.getElementById('btn-submit-import-text');
    const defaultContent = document.getElementById('dropzone-default-content');
    const selectedContent = document.getElementById('dropzone-selected-content');
    const fileName = document.getElementById('selected-file-name');
    const fileSize = document.getElementById('selected-file-size');
    const notification = document.getElementById('import-notification');
    const historyBody = document.getElementById('import-history-tbody');
    const historyCount = document.getElementById('import-history-count-badge');

    if (
        !fileInput || !dropzone || !btnUpload || !btnReselect ||
        !btnCancel || !btnSubmit || !btnSubmitText ||
        !defaultContent || !selectedContent || !fileName ||
        !fileSize || !notification || !historyBody || !historyCount
    ) {
        console.error('Elemen form import Excel tidak lengkap.');
        delete form.dataset.importInitialized;
        return;
    }

    let selectedFile = null;
    let isImporting = false;

    function showNotification(message, success = false) {
        const alert = document.getElementById('import-notification');

        if (!alert) {
            console.error('Elemen import-notification tidak ditemukan.');
            return;
        }

        const iconSvg = success
            ? `<svg class="w-5 h-5 text-emerald-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                   <polyline points="22 4 12 14.01 9 11.01"></polyline>
               </svg>`
            : `<svg class="w-5 h-5 text-rose-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <circle cx="12" cy="12" r="10"></circle>
                   <line x1="12" y1="8" x2="12" y2="12"></line>
                   <line x1="12" y1="16" x2="12.01" y2="16"></line>
               </svg>`;

        alert.className = [
            'mb-6',
            'p-4',
            'rounded-2xl',
            'border',
            'text-sm',
            'font-semibold',
            'flex',
            'items-center',
            'gap-3',
            'transition-all',
            success
                ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border-rose-200 text-rose-800'
        ].join(' ');

        alert.innerHTML = `${iconSvg}<span>${message}</span>`;
        alert.classList.remove('hidden');
    }

    function formatSize(bytes) {
        return bytes < 1024 * 1024
            ? `${(bytes / 1024).toFixed(1)} KB`
            : `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    }

    function resetFile() {
        selectedFile = null;
        fileInput.value = '';

        defaultContent.classList.remove('hidden');
        selectedContent.classList.add('hidden');
        selectedContent.classList.remove('flex');

        if (!isImporting) {
            btnSubmit.disabled = false;
            btnSubmitText.textContent = 'Import File Sekarang';
        }
    }

    function selectFile(file) {
        if (!file || isImporting) return;

        if (!file.name.toLowerCase().endsWith('.xlsx')) {
            showNotification('Pilih file dengan format .xlsx.');
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            showNotification('Ukuran file maksimal 50 MB.');
            return;
        }

        selectedFile = file;
        fileName.textContent = file.name;
        fileSize.textContent = formatSize(file.size);

        defaultContent.classList.add('hidden');
        selectedContent.classList.remove('hidden');
        selectedContent.classList.add('flex');

        notification.classList.add('hidden');
    }

    btnUpload.addEventListener('click', () => {
        if (!isImporting) fileInput.click();
    });

    btnReselect.addEventListener('click', () => {
        if (!isImporting) fileInput.click();
    });

    btnCancel.addEventListener('click', resetFile);

    fileInput.addEventListener('change', () => {
        selectFile(fileInput.files[0]);
    });

    dropzone.addEventListener('dragover', event => {
        event.preventDefault();

        if (!isImporting) {
            dropzone.classList.add('border-[#4B2E21]');
        }
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-[#4B2E21]');
    });

    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        dropzone.classList.remove('border-[#4B2E21]');

        if (isImporting) return;

        const file = event.dataTransfer.files[0];

        if (file) {
            // Sinkronkan file hasil drag-and-drop dengan input form.
            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;

            selectFile(file);
        }
    });

    dropzone.addEventListener('click', event => {
        if (event.target.closest('button, select, input')) return;

        if (!isImporting) {
            fileInput.click();
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (isImporting) return;

        if (!selectedFile) {
            showNotification('Pilih file Excel terlebih dahulu.');
            return;
        }

        const bulan = form.querySelector('[name="bulan"]')?.value;
        const tahun = form.querySelector('[name="tahun"]')?.value;

        if (!bulan || !tahun) {
            showNotification('Pilih bulan dan tahun laporan terlebih dahulu.');
            return;
        }

        isImporting = true;
        btnSubmit.disabled = true;
        btnSubmitText.textContent = 'Membaca workbook...';
        const alert = document.getElementById('import-notification');

if (alert) {
    alert.classList.add('hidden');
}

        const formData = new FormData(form);
        formData.set('file', selectedFile);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Import Excel gagal.'
                );
            }

            const data = result.data;

            showNotification(
                `${result.message} Sheet terdeteksi: ${data.jumlah_sheet}.`,
                true
            );

            try {
                await refreshHistory();
            } catch (historyError) {
                console.error('Gagal memperbarui riwayat:', historyError);
            }

            form.dispatchEvent(
                new CustomEvent('workbook:imported', {
                    detail: data
                })
            );

            resetFile();

        } catch (error) {
            console.error('Import Excel gagal:', error);

            showNotification(
                error.message || 'Terjadi kesalahan saat mengimpor Excel.'
            );

            try {
                await refreshHistory();
            } catch (historyError) {
                // Ignore history refresh error
            }
        } finally {
            isImporting = false;
            btnSubmit.disabled = false;
            btnSubmitText.textContent = 'Import File Sekarang';
        }
    });

    async function refreshHistory() {
        const response = await fetch('/api/import-history', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('Gagal mengambil riwayat import.');
        }

        const result = await response.json();

        if (!result.success || !Array.isArray(result.data)) {
            throw new Error('Format data riwayat tidak valid.');
        }

        historyBody.replaceChildren();

        if (result.data.length === 0) {
            const row = document.createElement('tr');
            row.id = 'empty-history-row';
            const cell = document.createElement('td');

            cell.colSpan = 6;
            cell.className = 'py-12 px-6 text-center text-[#8E7467]';
            cell.innerHTML = `
                <div class="flex flex-col items-center justify-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#FFF8E1] border border-[#FFE082] flex items-center justify-center text-[#4B2E21] mb-3 shadow-xs">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <p class="font-bold text-[#4B2E21] text-base mb-1">Belum Ada Riwayat Import</p>
                    <p class="text-xs sm:text-sm text-[#8E7467] max-w-sm">
                        Belum ada riwayat import data
                    </p>
                </div>
            `;

            row.appendChild(cell);
            historyBody.appendChild(row);
        } else {
            result.data.forEach((item, index) => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-[#FFFDF5] transition-colors';
                if (item.id) {
                    row.dataset.historyId = item.id;
                }

                // 1. No
                const cellNo = document.createElement('td');
                cellNo.className = 'py-3.5 px-4 sm:px-5 text-center font-semibold text-[#8E7467] history-row-number';
                cellNo.textContent = index + 1;
                row.appendChild(cellNo);

                // 2. Periode
                const cellPeriode = document.createElement('td');
                cellPeriode.className = 'py-3.5 px-4 sm:px-5 font-semibold text-[#4B2E21]';
                cellPeriode.textContent = item.periode ?? '-';
                row.appendChild(cellPeriode);

                // 3. Nama File
                const cellFile = document.createElement('td');
                cellFile.className = 'py-3.5 px-4 sm:px-5 font-medium text-[#6B4D3F]';
                const fileWrapper = document.createElement('div');
                fileWrapper.className = 'flex items-center gap-2';
                fileWrapper.innerHTML = `
                    <svg class="w-4 h-4 text-[#4B2E21] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                `;
                const fileSpan = document.createElement('span');
                fileSpan.className = 'truncate max-w-[200px] sm:max-w-[280px]';
                fileSpan.title = item.nama_file ?? '';
                fileSpan.textContent = item.nama_file ?? '-';
                fileWrapper.appendChild(fileSpan);
                cellFile.appendChild(fileWrapper);
                row.appendChild(cellFile);

                // 4. Tanggal Import
                const cellTanggal = document.createElement('td');
                cellTanggal.className = 'py-3.5 px-4 sm:px-5 text-[#8E7467]';
                cellTanggal.textContent = item.waktu_import ?? '-';
                row.appendChild(cellTanggal);

                // 5. Status (Badge Berhasil Hijau)
                const cellStatus = document.createElement('td');
                cellStatus.className = 'py-3.5 px-4 sm:px-5 text-center';

                const st = (item.status || '').toLowerCase();
                const statusBadge = document.createElement('span');

                if (st === 'berhasil') {
                    statusBadge.className = 'badge-status-berhasil';
                    statusBadge.textContent = 'Berhasil';
                } else if (st === 'gagal') {
                    statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-rose-600 text-white shadow-xs';
                    statusBadge.textContent = 'Gagal';
                } else {
                    statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-[#FFD54F] text-[#4B2E21] border border-[#FFE082] shadow-xs';
                    statusBadge.textContent = item.status ?? '-';
                }

                cellStatus.appendChild(statusBadge);
                row.appendChild(cellStatus);

                // 6. Aksi
                const actionCell = document.createElement('td');
                actionCell.className = 'py-3.5 px-4 sm:px-5 text-center';

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn-action-lihat';
                button.textContent = 'Lihat';

                button.addEventListener('click', () => {
                    const targetId = item.laporan_keuangan_id || item.id;
                    if (!targetId) {
                        alert('ID laporan keuangan tidak ditemukan.');
                        return;
                    }

                    window.location.href = `/laporan-keuangan-bulanan/${targetId}/detail`;
                });

                actionCell.appendChild(button);
                row.appendChild(actionCell);
                historyBody.appendChild(row);
            });
        }

        historyCount.textContent =
            `${result.data.length} Data Riwayat`;
    }
}

// Inisialisasi awal dan dukungan navigasi SPA.
function initImportExcel() {
    importExcel();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImportExcel);
} else {
    initImportExcel();
}

document.addEventListener('spa:view-changed', initImportExcel);
