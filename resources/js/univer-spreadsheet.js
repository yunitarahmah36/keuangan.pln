import { createUniver, LocaleType, mergeLocales } from '@univerjs/presets';
import { UniverSheetsCorePreset } from '@univerjs/preset-sheets-core';
import UniverPresetSheetsCoreEnUS from '@univerjs/preset-sheets-core/locales/en-US';

import '@univerjs/preset-sheets-core/lib/index.css';

/**
 * Univer Spreadsheet Integration for Laporan Keuangan PLN
 */

function colLetterToIndex(col) {
    col = String(col).toUpperCase();
    let idx = 0;
    for (let i = 0; i < col.length; i++) {
        idx = idx * 26 + (col.charCodeAt(i) - 64);
    }
    return idx; // 1-based index (A = 1)
}

function indexToColLetter(index) {
    let letter = '';
    while (index > 0) {
        const mod = (index - 1) % 26;
        letter = String.fromCharCode(65 + mod) + letter;
        index = Math.floor((index - mod) / 26);
    }
    return letter;
}

export function initUniverSpreadsheet() {
    const containers = document.querySelectorAll('.univer-spreadsheet-instance:not([data-univer-initialized="true"])');

    containers.forEach((container) => {
        container.dataset.univerInitialized = 'true';

        const laporanId = container.dataset.laporanId;
        const activeSheetQuery = container.dataset.activeSheet || null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                          container.dataset.csrf ||
                          '';

        const loadingOverlay = container.querySelector('.univer-loading-overlay');
        const mountPoint = container.querySelector('.univer-mount-point');

        if (!mountPoint || !laporanId) {
            console.error('Mount point atau Laporan ID Univer tidak ditemukan.');
            return;
        }

        const mountId = mountPoint.id || `univer-mount-${Date.now()}`;
        mountPoint.id = mountId;

        // Fetch seluruh data sheet workbook dari endpoint yang sudah ada
        fetch(`/laporan-keuangan-bulanan/${laporanId}/sheet-data?all=1`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then((res) => {
            if (!res.ok) throw new Error('Gagal memuat data workbook dari server.');
            return res.json();
        })
        .then((response) => {
            if (!response.success || !response.data || !response.data.sheets) {
                throw new Error(response.message || 'Format data workbook tidak valid.');
            }

            const rawSheets = response.data.sheets;
            const fileName = response.data.fileName || 'Laporan Keuangan';

            const sheetOrder = [];
            const sheetsMap = {};
            const initialSnapshots = {};

            rawSheets.forEach((sData, sIndex) => {
                const cleanName = sData.sheet || `Sheet${sIndex + 1}`;
                const sheetId = `sheet_${sIndex}_${cleanName.replace(/[^a-zA-Z0-9_-]/g, '_')}`;
                sheetOrder.push(sheetId);

                const cellData = {};
                (sData.rows || []).forEach((r) => {
                    const rIdx = (r.row || 1) - 1; // 0-indexed row
                    cellData[rIdx] = cellData[rIdx] || {};

                    if (r.cells) {
                        Object.keys(r.cells).forEach((ref) => {
                            const c = r.cells[ref];
                            const cIdx = colLetterToIndex(c.col || 'A') - 1; // 0-indexed col
                            const rawVal = c.val !== null && c.val !== undefined ? c.val : '';
                            const formula = c.formula || null;

                            const isNumeric = rawVal !== '' && !isNaN(Number(rawVal)) && isFinite(Number(rawVal));
                            const parsedVal = isNumeric ? Number(rawVal) : rawVal;

                            const cellObj = {
                                v: parsedVal
                            };

                            if (formula && typeof formula === 'string' && formula.trim().length > 0) {
                                cellObj.f = formula.startsWith('=') ? formula.trim() : '=' + formula.trim();
                            }

                            cellData[rIdx][cIdx] = cellObj;
                        });
                    }
                });

                const rowCount = Math.max(80, (sData.totalRows || 0) + 30);
                const colCount = Math.max(26, (sData.maxColIndex || 10) + 6);

                sheetsMap[sheetId] = {
                    id: sheetId,
                    name: cleanName,
                    rowCount: rowCount,
                    columnCount: colCount,
                    cellData: cellData
                };

                // Clone data sel awal untuk deteksi perubahan
                initialSnapshots[sheetId] = JSON.parse(JSON.stringify(cellData));
            });

            // Sembunyikan loading overlay
            if (loadingOverlay) {
                loadingOverlay.classList.add('hidden');
            }

            // Inisialisasi Univer instance
            const { univer, univerAPI } = createUniver({
                locale: LocaleType.EN_US,
                locales: {
                    [LocaleType.EN_US]: mergeLocales(
                        UniverPresetSheetsCoreEnUS
                    ),
                },
                presets: [
                    UniverSheetsCorePreset({
                        container: mountId,
                    }),
                ],
            });

            // Buat Workbook Univer lengkap dengan semua sheet
            const fWorkbook = univerAPI.createWorkbook({
                id: `wb-${laporanId}`,
                name: fileName,
                sheetOrder: sheetOrder,
                sheets: sheetsMap,
            });

            // Aktifkan sheet yang diminta (jika ada param query sheet)
            if (activeSheetQuery) {
                const targetSheet = fWorkbook.getSheetByName(activeSheetQuery);
                if (targetSheet) {
                    try {
                        targetSheet.activate();
                    } catch (e) {
                        console.warn('Gagal mengaktifkan sheet aktif:', e);
                    }
                }
            }

            // Simpan referensi ke window untuk akses global / debugging jika diperlukan
            window.activeUniverInstance = {
                univer,
                univerAPI,
                fWorkbook,
                laporanId,
                initialSnapshots
            };

            // Setup tombol Simpan Perubahan
            const saveBtn = container.querySelector('.btn-save-univer-spreadsheet') ||
                            document.getElementById('btn-save-spreadsheet');
            const alertBanner = document.getElementById('excel-alert-banner') ||
                                container.querySelector('.univer-alert-banner');
            const alertMessage = document.getElementById('excel-alert-message') ||
                                 container.querySelector('.univer-alert-message');
            const unsavedBadge = document.getElementById('status-bar-unsaved') ||
                                 container.querySelector('.univer-unsaved-badge');

            function showAlert(msg, type = 'success') {
                if (!alertBanner || !alertMessage) return;
                alertBanner.className = `mb-3 p-3 rounded-xl border text-xs font-semibold flex items-center justify-between gap-3 shadow-xs transition-all ${
                    type === 'success'
                        ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                        : 'bg-rose-50 border-rose-300 text-rose-800'
                }`;
                alertMessage.innerHTML = `
                    <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        ${type === 'success' ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>' : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'}
                    </svg>
                    <span>${msg}</span>
                `;
                alertBanner.classList.remove('hidden');
                setTimeout(() => {
                    alertBanner.classList.add('hidden');
                }, 6000);
            }

            function getModifiedCells() {
                const currentSnapshot = fWorkbook.save ? fWorkbook.save() : fWorkbook.getSnapshot();
                const sheetsPayload = [];
                let totalModified = 0;

                if (!currentSnapshot || !currentSnapshot.sheets) {
                    return { sheetsPayload, totalModified };
                }

                Object.keys(currentSnapshot.sheets).forEach((sId) => {
                    const sheetObj = currentSnapshot.sheets[sId];
                    const sheetName = sheetObj.name || sheetsMap[sId]?.name || 'Sheet';
                    const currentCellData = sheetObj.cellData || {};
                    const initialCellData = initialSnapshots[sId] || {};

                    const changedCells = [];

                    // 1. Cek sel yang diedit atau baru di currentCellData
                    Object.keys(currentCellData).forEach((rKey) => {
                        const r = parseInt(rKey, 10);
                        const rowObj = currentCellData[r];
                        if (!rowObj) return;

                        Object.keys(rowObj).forEach((cKey) => {
                            const c = parseInt(cKey, 10);
                            const curCell = rowObj[c];
                            const initCell = (initialCellData[r] && initialCellData[r][c]) ? initialCellData[r][c] : null;

                            const curVal = curCell ? curCell.v : null;
                            const curFormula = curCell ? curCell.f : null;

                            const initVal = initCell ? initCell.v : null;
                            const initFormula = initCell ? initCell.f : null;

                            const isValDiff = String(curVal ?? '') !== String(initVal ?? '');
                            const isFormulaDiff = String(curFormula ?? '') !== String(initFormula ?? '');

                            if (isValDiff || isFormulaDiff) {
                                const colLetter = indexToColLetter(c + 1);
                                const rowNum = r + 1;
                                const ref = `${colLetter}${rowNum}`;

                                changedCells.push({
                                    ref: ref,
                                    value: curVal !== null && curVal !== undefined ? curVal : '',
                                    formula: curFormula || null,
                                });
                            }
                        });
                    });

                    if (changedCells.length > 0) {
                        sheetsPayload.push({
                            sheet: sheetName,
                            cells: changedCells,
                        });
                        totalModified += changedCells.length;
                    }
                });

                return { sheetsPayload, totalModified };
            }

            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    const { sheetsPayload, totalModified } = getModifiedCells();

                    if (totalModified === 0) {
                        showAlert('Tidak ada perubahan sel atau rumus untuk disimpan.', 'success');
                        return;
                    }

                    const iconIdle = saveBtn.querySelector('.save-icon-idle') || document.getElementById('save-icon-idle');
                    const iconLoading = saveBtn.querySelector('.save-icon-loading') || document.getElementById('save-icon-loading');
                    const btnText = saveBtn.querySelector('.save-btn-text') || document.getElementById('btn-save-text');

                    saveBtn.disabled = true;
                    if (iconIdle) iconIdle.classList.add('hidden');
                    if (iconLoading) iconLoading.classList.remove('hidden');
                    if (btnText) btnText.textContent = 'Menyimpan...';

                    fetch(`/laporan-keuangan-bulanan/${laporanId}/save-cells`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            sheets: sheetsPayload,
                        }),
                    })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            showAlert(`Berhasil! ${totalModified} perubahan sel dan rumus telah tersimpan ke dalam file Excel asli.`, 'success');

                            // Update snapshot awal ke kondisi saat ini
                            const updatedSnapshot = fWorkbook.save ? fWorkbook.save() : fWorkbook.getSnapshot();
                            if (updatedSnapshot && updatedSnapshot.sheets) {
                                Object.keys(updatedSnapshot.sheets).forEach((sId) => {
                                    initialSnapshots[sId] = JSON.parse(JSON.stringify(updatedSnapshot.sheets[sId].cellData || {}));
                                });
                            }

                            if (unsavedBadge) {
                                unsavedBadge.textContent = '0 Perubahan';
                                unsavedBadge.className = 'flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px] text-white/80';
                            }
                        } else {
                            showAlert(data.message || 'Gagal menyimpan perubahan ke file Excel.', 'error');
                        }
                    })
                    .catch((err) => {
                        console.error('Error saat menyimpan perubahan:', err);
                        showAlert('Terjadi kesalahan jaringan saat menyimpan file Excel.', 'error');
                    })
                    .finally(() => {
                        saveBtn.disabled = false;
                        if (iconIdle) iconIdle.classList.remove('hidden');
                        if (iconLoading) iconLoading.classList.add('hidden');
                        if (btnText) btnText.textContent = 'Simpan Perubahan';
                    });
                });
            }

            // Listener perubahan perintah untuk update badge unsaved
            try {
                fWorkbook.onCommandExecuted(() => {
                    const { totalModified } = getModifiedCells();
                    if (unsavedBadge) {
                        unsavedBadge.textContent = `${totalModified} Perubahan`;
                        unsavedBadge.className = totalModified > 0
                            ? 'flex items-center gap-1 bg-amber-400 text-[#4B2E21] font-bold px-2 py-0.5 rounded text-[10px]'
                            : 'flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px] text-white/80';
                    }
                });
            } catch (e) {
                // Command listener optional
            }

            // Quick Switcher Kelompok (jika elemen switcher ada)
            const selectKelompok = document.getElementById('select-switch-kelompok');
            if (selectKelompok) {
                selectKelompok.addEventListener('change', function () {
                    const targetGroup = this.value;
                    const kelompokData = window.rawKelompokData || {};
                    const sheetsInGroup = kelompokData[targetGroup] || [];
                    if (sheetsInGroup.length > 0) {
                        const targetSheet = fWorkbook.getSheetByName(sheetsInGroup[0]);
                        if (targetSheet) targetSheet.activate();
                    }
                });
            }

            // Fullscreen toggle jika ada tombol fullscreen
            const fullscreenBtn = document.getElementById('btn-toggle-fullscreen');
            const windowWrapper = document.getElementById('excel-online-window') || container;
            if (fullscreenBtn && windowWrapper) {
                fullscreenBtn.addEventListener('click', () => {
                    if (!document.fullscreenElement) {
                        windowWrapper.requestFullscreen().catch((err) => {
                            console.log('Fullscreen request error:', err);
                        });
                    } else {
                        document.exitFullscreen();
                    }
                });
            }
        })
        .catch((error) => {
            console.error('Inisialisasi Univer Spreadsheet gagal:', error);
            if (loadingOverlay) {
                loadingOverlay.innerHTML = `
                    <div class="flex flex-col items-center justify-center p-8 text-rose-600 gap-2">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span class="font-bold text-sm text-[#4B2E21]">Gagal memuat workbook Excel: ${error.message}</span>
                        <button type="button" onclick="location.reload()" class="mt-2 px-3 py-1.5 bg-[#107C41] text-white rounded-lg text-xs font-bold">Muat Ulang</button>
                    </div>
                `;
            }
        });
    });
}

// Inisialisasi awal dan saat navigasi SPA berganti
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUniverSpreadsheet);
} else {
    initUniverSpreadsheet();
}

document.addEventListener('spa:view-changed', initUniverSpreadsheet);
