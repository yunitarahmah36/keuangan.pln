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

function excelRefToRange(ref) {
    if (!ref || typeof ref !== 'string') return null;
    const parts = ref.split(':');
    if (parts.length !== 2) return null;
    const startMatch = parts[0].trim().toUpperCase().match(/^([A-Z]+)(\d+)$/);
    const endMatch = parts[1].trim().toUpperCase().match(/^([A-Z]+)(\d+)$/);
    if (!startMatch || !endMatch) return null;
    const startRow = parseInt(startMatch[2], 10) - 1;
    const startCol = colLetterToIndex(startMatch[1]) - 1;
    const endRow = parseInt(endMatch[2], 10) - 1;
    const endCol = colLetterToIndex(endMatch[1]) - 1;
    return {
        startRow: Math.min(startRow, endRow),
        endRow: Math.max(startRow, endRow),
        startColumn: Math.min(startCol, endCol),
        endColumn: Math.max(startCol, endCol),
    };
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
            const sheetViewMetadata = {};

            window.sheetTabColors = window.sheetTabColors || {};

            rawSheets.forEach((sData, sIndex) => {
                const cleanName = sData.sheet || `Sheet${sIndex + 1}`;
                const sheetId = `sheet_${sIndex}_${cleanName.replace(/[^a-zA-Z0-9_-]/g, '_')}`;
                sheetOrder.push(sheetId);

                if (sData.tabColor) {
                    window.sheetTabColors[cleanName] = sData.tabColor;
                }

                // 1. Ekstrak lebar kolom kustom & hidden columns dari file Excel
                const columnData = {};
                if (sData.cols && typeof sData.cols === 'object') {
                    Object.keys(sData.cols).forEach((colNumStr) => {
                        const colNum = parseInt(colNumStr, 10);
                        const colIdx = colNum - 1; // 0-indexed
                        const colVal = sData.cols[colNumStr];
                        const wVal = typeof colVal === 'object' && colVal.w !== undefined ? parseFloat(colVal.w) : parseFloat(colVal);
                        const isHidden = typeof colVal === 'object' ? Boolean(colVal.hidden) : false;
                        let px;
                        if (wVal < 2) {
                            px = 16; // Kolom spasi/indentasi sempit khas laporan keuangan PLN
                        } else {
                            px = Math.round(wVal * 8.5) + 6;
                        }
                        const colEntry = { w: Math.max(16, px) };
                        if (isHidden) {
                            colEntry.hd = 1;
                        }
                        columnData[colIdx] = colEntry;
                    });
                }

                // 2. Ekstrak tinggi baris kustom & hidden rows dari file Excel
                const rowData = {};
                if (sData.rowHeights && typeof sData.rowHeights === 'object') {
                    Object.keys(sData.rowHeights).forEach((rNumStr) => {
                        const rNum = parseInt(rNumStr, 10);
                        const rIdx = rNum - 1;
                        const ht = parseFloat(sData.rowHeights[rNumStr]);
                        if (ht > 0) {
                            rowData[rIdx] = { ...(rowData[rIdx] || {}), h: Math.round(ht * 1.33) };
                        }
                    });
                }
                if (Array.isArray(sData.hiddenRows)) {
                    sData.hiddenRows.forEach((rNum) => {
                        const rIdx = rNum - 1;
                        rowData[rIdx] = { ...(rowData[rIdx] || {}), hd: 1 };
                    });
                }

                // 3. Ekstrak sel gabungan (merged cells)
                const mergeData = [];
                if (Array.isArray(sData.merges)) {
                    sData.merges.forEach((m) => {
                        const ref = typeof m === 'string' ? m : (m && m.ref ? m.ref : '');
                        const range = excelRefToRange(ref);
                        if (range) {
                            mergeData.push(range);
                        }
                    });
                }

                // 4. Bangun data sel beserta format visual, warna, dan peletakannya
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

                            const isString = c.type === 's' || c.type === 'inlineStr';
                            const isNumeric = !isString && rawVal !== '' && rawVal !== null && !isNaN(Number(rawVal)) && isFinite(Number(rawVal));
                            const parsedVal = isNumeric ? Number(rawVal) : rawVal;

                            const cellObj = {
                                v: parsedVal
                            };

                            if (formula && typeof formula === 'string' && formula.trim().length > 0) {
                                cellObj.f = formula.startsWith('=') ? formula.trim() : '=' + formula.trim();
                            }

                            // Terapkan style asli dari Excel tanpa ditimpa style otomatis
                            let style = c.style ? { ...c.style } : {};

                            // Fallback alignment hanya jika sel numerik dan Excel tidak menentukan alignment horizontal
                            if (isNumeric && style.ht === undefined) {
                                style.ht = 3; // Rata kanan untuk angka
                            }

                            // Hyperlinks
                            if (c.hyperlink) {
                                cellObj.custom = { ...(cellObj.custom || {}), hyperlink: c.hyperlink };
                                if (!style.cl) {
                                    style.cl = { rgb: '#0563C1' }; // Standard Excel hyperlink blue
                                }
                                if (!style.ul) {
                                    style.ul = { s: 1 }; // Underline
                                }
                            }

                            if (Object.keys(style).length > 0) {
                                cellObj.s = style;
                            }

                            cellData[rIdx][cIdx] = cellObj;
                        });
                    }
                });

                const rowCount = Math.max(80, (sData.totalRows || 0) + 30);
                const colCount = Math.max(26, (sData.maxColIndex || 10) + 6);

                const sheetDef = {
                    id: sheetId,
                    name: cleanName,
                    rowCount: rowCount,
                    columnCount: colCount,
                    cellData: cellData,
                    columnData: columnData,
                    rowData: rowData,
                    mergeData: mergeData,
                    showGridlines: sData.showGridlines !== undefined ? sData.showGridlines : 1,
                    zoomRatio: sData.zoomRatio || 1,
                    defaultColumnWidth: 95,
                    defaultRowHeight: 24,
                };

                // Terapkan Freeze Panes asli dari Excel untuk semua sheet yang memilikinya
                // Catatan krusial: Di Univer, batas awal panel beku (startRow & startColumn) WAJIB sama dengan ySplit & xSplit
                // agar baris & kolom header tabel selalu terkunci rapat di (0,0) dan tidak terdorong/hilang.
                if (sData.freeze && (sData.freeze.xSplit > 0 || sData.freeze.ySplit > 0)) {
                    sheetDef.freeze = {
                        xSplit: sData.freeze.xSplit || 0,
                        ySplit: sData.freeze.ySplit || 0,
                        startRow: sData.freeze.ySplit || 0,
                        startColumn: sData.freeze.xSplit || 0,
                    };
                }

                // Catat posisi scroll awal dan sel aktif dari file Excel
                sheetViewMetadata[cleanName] = {
                    scrollRow: sData.freeze?.scrollRow !== undefined ? sData.freeze.scrollRow : sData.scrollRow,
                    scrollColumn: sData.freeze?.scrollColumn !== undefined ? sData.freeze.scrollColumn : sData.scrollColumn,
                    activeCell: sData.activeCell || null,
                    activeRow: sData.activeRow !== undefined ? sData.activeRow : null,
                    activeCol: sData.activeCol !== undefined ? sData.activeCol : null,
                };
                if (cleanName !== sData.sheet && sData.sheet) {
                    sheetViewMetadata[sData.sheet] = sheetViewMetadata[cleanName];
                }

                // Warna Tab Asli Excel
                if (sData.tabColor) {
                    sheetDef.tabColor = sData.tabColor;
                }

                // Status Sheet Tersembunyi (Hidden Sheets)
                if (sData.hidden) {
                    sheetDef.hidden = 1;
                }

                // Simpan metadata asli lainnya (AutoFilter, Data Validation)
                if (sData.autoFilter || (sData.dataValidations && sData.dataValidations.length > 0)) {
                    sheetDef.custom = {
                        autoFilter: sData.autoFilter || null,
                        dataValidations: sData.dataValidations || [],
                    };
                }

                sheetsMap[sheetId] = sheetDef;

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
                        footer: false,
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

            // Elemen Navigasi, Status Bar & Alert
            const parentCard = container.closest('#excel-online-window') || container.closest('.bg-white') || document;
            const alertBanner = document.getElementById('excel-alert-banner') ||
                                container.querySelector('.univer-alert-banner');
            const alertMessage = document.getElementById('excel-alert-message') ||
                                 container.querySelector('.univer-alert-message');
            const unsavedBadge = parentCard.querySelector('#status-bar-unsaved') ||
                                 document.getElementById('status-bar-unsaved') ||
                                 container.querySelector('.univer-unsaved-badge');

            // Elemen Indikator Autosave
            const autosaveContainer = parentCard.querySelector('.autosave-status-container') ||
                                      document.getElementById('autosave-status-container');
            const autosaveSaved = parentCard.querySelector('.autosave-badge-saved') ||
                                  document.getElementById('autosave-badge-saved');
            const autosaveSaving = parentCard.querySelector('.autosave-badge-saving') ||
                                   document.getElementById('autosave-badge-saving');
            const autosaveError = parentCard.querySelector('.autosave-badge-error') ||
                                  document.getElementById('autosave-badge-error');
            const retryBtn = parentCard.querySelector('.btn-retry-autosave') ||
                             document.getElementById('btn-retry-autosave');

            // Setup Navigasi Tab Sheet Berdasarkan Kelompok yang Dipilih
            const tabsList = parentCard.querySelector('#group-sheet-tabs-list') ||
                             document.getElementById('group-sheet-tabs-list');
            const groupBadge = parentCard.querySelector('#group-tabs-kelompok-badge') ||
                               document.getElementById('group-tabs-kelompok-badge');
            const countLabel = parentCard.querySelector('#group-tabs-count-label') ||
                               document.getElementById('group-tabs-count-label');
            const headerKelompokLabel = document.getElementById('header-kelompok-label');
            const selectKelompok = document.getElementById('select-switch-kelompok');
            const statusBarSheet = parentCard.querySelector('#status-bar-sheet-name') ||
                                   document.getElementById('status-bar-sheet-name');

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

            // ========================================================
            // FUNGSI CEK SEL YANG BERUBAH (DIFF SNAPSHOTS)
            // ========================================================
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
                    const checkedKeys = new Set();

                    // 1. Cek sel yang diedit, ditambah, atau bernilai baru
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
                                checkedKeys.add(`${r}_${c}`);

                                changedCells.push({
                                    ref: ref,
                                    value: curVal !== null && curVal !== undefined ? curVal : '',
                                    formula: curFormula || null,
                                });
                            }
                        });
                    });

                    // 2. Cek sel yang dihapus nilainya sehingga hilang dari currentCellData
                    Object.keys(initialCellData).forEach((rKey) => {
                        const r = parseInt(rKey, 10);
                        const initRow = initialCellData[r];
                        if (!initRow) return;

                        Object.keys(initRow).forEach((cKey) => {
                            const c = parseInt(cKey, 10);
                            if (checkedKeys.has(`${r}_${c}`)) return;

                            const initCell = initRow[c];
                            const curCell = (currentCellData[r] && currentCellData[r][c]) ? currentCellData[r][c] : null;

                            if (!curCell && (initCell?.v !== null && initCell?.v !== undefined && initCell?.v !== '')) {
                                const colLetter = indexToColLetter(c + 1);
                                const rowNum = r + 1;
                                const ref = `${colLetter}${rowNum}`;

                                changedCells.push({
                                    ref: ref,
                                    value: '',
                                    formula: null,
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

            // ========================================================
            // AUTOSAVE STATUS & ENGINE
            // ========================================================
            let isSaving = false;
            let pendingSaveRequested = false;
            let autosaveTimer = null;
            let isReadyForAutosave = false;

            function setAutosaveStatus(state) {
                if (autosaveSaved) autosaveSaved.classList.add('hidden');
                if (autosaveSaving) autosaveSaving.classList.add('hidden');
                if (autosaveError) autosaveError.classList.add('hidden');

                if (state === 'saved') {
                    if (autosaveSaved) autosaveSaved.classList.remove('hidden');
                    if (unsavedBadge) {
                        unsavedBadge.textContent = 'Tersimpan';
                        unsavedBadge.className = 'univer-unsaved-badge flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded text-[10px] text-white/90';
                    }
                } else if (state === 'saving') {
                    if (autosaveSaving) autosaveSaving.classList.remove('hidden');
                    if (unsavedBadge) {
                        unsavedBadge.textContent = 'Menyimpan...';
                        unsavedBadge.className = 'univer-unsaved-badge flex items-center gap-1 bg-amber-400 text-[#4B2E21] font-bold px-2 py-0.5 rounded text-[10px]';
                    }
                } else if (state === 'error') {
                    if (autosaveError) autosaveError.classList.remove('hidden');
                    if (unsavedBadge) {
                        unsavedBadge.textContent = 'Gagal menyimpan';
                        unsavedBadge.className = 'univer-unsaved-badge flex items-center gap-1 bg-rose-500 text-white font-bold px-2 py-0.5 rounded text-[10px]';
                    }
                }
            }

            function executeSave(options = {}) {
                if (isSaving) {
                    pendingSaveRequested = true;
                    return Promise.resolve();
                }

                const { sheetsPayload, totalModified } = getModifiedCells();

                if (totalModified === 0) {
                    setAutosaveStatus('saved');
                    return Promise.resolve();
                }

                isSaving = true;
                setAutosaveStatus('saving');

                return fetch(`/laporan-keuangan-bulanan/${laporanId}/save-cells`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        sheets: sheetsPayload,
                    }),
                    keepalive: true,
                })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        // Perbarui snapshot awal dengan snapshot terkini yang telah tersimpan
                        const updatedSnapshot = fWorkbook.save ? fWorkbook.save() : fWorkbook.getSnapshot();
                        if (updatedSnapshot && updatedSnapshot.sheets) {
                            Object.keys(updatedSnapshot.sheets).forEach((sId) => {
                                initialSnapshots[sId] = JSON.parse(JSON.stringify(updatedSnapshot.sheets[sId].cellData || {}));
                            });
                        }

                        isSaving = false;

                        // Jika ada perubahan baru yang masuk selama proses penyimpanan
                        const remaining = getModifiedCells();
                        if (pendingSaveRequested || remaining.totalModified > 0) {
                            pendingSaveRequested = false;
                            scheduleAutosave(300);
                        } else {
                            setAutosaveStatus('saved');
                        }
                    } else {
                        isSaving = false;
                        setAutosaveStatus('error');
                        showAlert(data.message || 'Gagal menyimpan perubahan ke server.', 'error');
                    }
                })
                .catch((err) => {
                    console.error('Autosave error:', err);
                    isSaving = false;
                    setAutosaveStatus('error');
                    showAlert('Gagal menyimpan perubahan spreadsheet (koneksi terganggu). Klik "Coba Lagi" untuk menyimpan ulang.', 'error');
                });
            }

            function scheduleAutosave(delay = 1200) {
                if (!isReadyForAutosave) return;

                const { totalModified } = getModifiedCells();
                if (totalModified === 0 && !isSaving) {
                    setAutosaveStatus('saved');
                    return;
                }

                if (!isSaving) {
                    setAutosaveStatus('saving');
                }

                if (autosaveTimer) {
                    clearTimeout(autosaveTimer);
                }

                autosaveTimer = setTimeout(() => {
                    executeSave();
                }, delay);
            }

            function flushAutosave() {
                if (autosaveTimer) {
                    clearTimeout(autosaveTimer);
                    autosaveTimer = null;
                }
                const { totalModified } = getModifiedCells();
                if (totalModified > 0) {
                    executeSave({ immediate: true });
                }
            }

            // Tombol Coba Lagi saat autosave gagal
            if (retryBtn) {
                retryBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    executeSave({ immediate: true });
                });
            }

            // Aktifkan autosave setelah workbook siap
            setTimeout(() => {
                isReadyForAutosave = true;
                setAutosaveStatus('saved');
            }, 600);

            // Listener perubahan sel di Univer
            try {
                fWorkbook.onCommandExecuted(() => {
                    if (isReadyForAutosave) {
                        scheduleAutosave(1200);
                    }
                });
            } catch (e) {
                console.warn('onCommandExecuted listener setup:', e);
            }

            // Listener focusout / keydown pada canvas editor sel
            mountPoint.addEventListener('focusout', () => {
                if (isReadyForAutosave) {
                    scheduleAutosave(350);
                }
            }, true);

            mountPoint.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    if (isReadyForAutosave) {
                        scheduleAutosave(400);
                    }
                }
            }, true);

            // ========================================================
            // FUNGSI TAMPILAN TAB SHEET & INDIKATOR TAB AKTIF
            // ========================================================


function updateActiveTabVisual(sheetName) {
    if (!tabsList) return;

    const tabs = tabsList.querySelectorAll('.univer-custom-tab-item');

    tabs.forEach((tab) => {
        const isActive = tab.dataset.sheetName === sheetName;

        tab.classList.toggle('active', isActive);

        tab.style.backgroundColor = isActive ? '#107C41' : '#ffffff';
        tab.style.color = isActive ? '#ffffff' : '#374151';
        tab.style.borderColor = isActive ? '#107C41' : '#D4D4D4';
        tab.style.borderWidth = '1px';
        tab.style.fontWeight = isActive ? '700' : '500';
        tab.style.boxShadow = isActive
            ? '0 2px 5px rgba(16, 124, 65, 0.25)'
            : '0 1px 2px rgba(0, 0, 0, 0.04)';

        const svg = tab.querySelector('.tab-icon') || tab.querySelector('svg');
        if (svg) {
            svg.style.color = isActive ? '#ffffff' : '#8E7467';
        }

        const dot = tab.querySelector('.tab-color-dot');
        if (dot) {
            dot.style.boxShadow = isActive ? '0 0 0 1.5px rgba(255, 255, 255, 0.9)' : '0 0 0 1px rgba(0, 0, 0, 0.15)';
        }
    });

    if (statusBarSheet) {
        statusBarSheet.textContent = sheetName;
    }
}



            function renderTabsForGroup(groupName, sheetToSelect = null) {
                const kelompokData = window.rawKelompokData || {};
                const sheetsInGroup = kelompokData[groupName] || [];

                if (groupBadge) groupBadge.textContent = groupName;
                if (headerKelompokLabel) headerKelompokLabel.textContent = groupName;
                if (countLabel) countLabel.textContent = `${sheetsInGroup.length} Sheet`;
                if (selectKelompok && selectKelompok.value !== groupName) {
                    selectKelompok.value = groupName;
                }

                if (!tabsList) return;
                tabsList.innerHTML = '';

                // Tentukan sheet mana yang aktif dalam kelompok ini
                const activeTarget = sheetToSelect && sheetsInGroup.includes(sheetToSelect)
                    ? sheetToSelect
                    : (sheetsInGroup[0] || null);


sheetsInGroup.forEach((sName) => {
    const isActive = sName === activeTarget;
    const tabColor = (window.sheetTabColors && window.sheetTabColors[sName]) || null;
    const btn = document.createElement('button');

    btn.type = 'button';
    btn.dataset.sheetName = sName;
    btn.className = `univer-custom-tab-item px-3 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1.5 cursor-pointer border whitespace-nowrap shadow-xs ${
        isActive
            ? 'active bg-[#107C41] text-white border-[#107C41] font-bold shadow-xs'
            : 'bg-white text-[#374151] hover:bg-[#FAF8F4] hover:text-[#107C41] border-[#D4D4D4] hover:border-[#107C41]/40 font-medium'
    }`;

    let colorDot = '';
    if (tabColor) {
        colorDot = `<span class="tab-color-dot w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: ${tabColor}; ${isActive ? 'box-shadow: 0 0 0 1.5px rgba(255,255,255,0.9);' : 'box-shadow: 0 0 0 1px rgba(0,0,0,0.15);'}" title="Warna tab: ${tabColor}"></span>`;
    }

    btn.innerHTML = `
        ${colorDot}
        <svg class="tab-icon w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" style="color: ${isActive ? '#ffffff' : '#8E7467'};">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8" fill="${isActive ? '#0E6835' : '#FAF8F4'}"></polyline>
        </svg>
        <span class="tab-title truncate max-w-[180px] leading-tight"></span>
    `;

    const titleSpan = btn.querySelector('.tab-title');
    if (titleSpan) {
        titleSpan.textContent = sName;
    }

    function applySheetInitialView(targetWs, sheetName) {
        const meta = sheetViewMetadata[sheetName];
        if (!meta) return;

        setTimeout(() => {
            try {
                // Terapkan sel aktif jika ada (misal X75)
                if (meta.activeCell && typeof targetWs.getRange === 'function') {
                    const range = targetWs.getRange(meta.activeCell);
                    if (range && typeof targetWs.setActiveRange === 'function') {
                        targetWs.setActiveRange(range);
                    }
                } else if (meta.activeRow !== null && meta.activeCol !== null && typeof targetWs.getRange === 'function') {
                    const range = targetWs.getRange(meta.activeRow, meta.activeCol, 1, 1);
                    if (range && typeof targetWs.setActiveRange === 'function') {
                        targetWs.setActiveRange(range);
                    }
                }

                // Terapkan scroll jika file Excel menyimpan posisi scroll (misal M56)
                if (typeof targetWs.scrollToCell === 'function') {
                    if (meta.scrollRow !== undefined && meta.scrollColumn !== undefined && (meta.scrollRow > 0 || meta.scrollColumn > 0)) {
                        targetWs.scrollToCell(meta.scrollRow, meta.scrollColumn);
                    }
                }
            } catch (e) {
                console.debug('Error applying initial view for sheet:', sheetName, e);
            }
        }, 100);
    }

    btn.addEventListener('click', () => {
        const targetWs = fWorkbook.getSheetByName(sName);

        if (!targetWs) {
            console.error('Sheet tidak ditemukan:', sName);
            return;
        }

        flushAutosave();

        try {
            targetWs.activate();
            applySheetInitialView(targetWs, sName);
            updateActiveTabVisual(sName);
        } catch (error) {
            console.error('Gagal mengganti sheet:', error);
        }
    });

    tabsList.appendChild(btn);
});


                // Aktifkan sheet awal kelompok di Univer
                if (activeTarget) {
                    const targetWs = fWorkbook.getSheetByName(activeTarget);
                    if (targetWs) {
                        try {
                            targetWs.activate();
                            applySheetInitialView(targetWs, activeTarget);
                        } catch (e) {
                            console.warn('Gagal aktivasi sheet awal kelompok:', e);
                        }
                    }
                    updateActiveTabVisual(activeTarget);
                }
            }

            // Inisialisasi navigasi kelompok awal
            const initialKelompok = container.dataset.activeKelompok ||
                                    (selectKelompok ? selectKelompok.value : null) ||
                                    (window.rawKelompokData ? Object.keys(window.rawKelompokData)[0] : 'Laporan Utama');
            renderTabsForGroup(initialKelompok, activeSheetQuery);

            // Listener pergantian kelompok via dropdown
            if (selectKelompok) {
                selectKelompok.addEventListener('change', function () {
                    // Simpan perubahan sheet sebelum berganti kelompok
                    flushAutosave();
                    const targetGroup = this.value;
                    renderTabsForGroup(targetGroup, null);
                });
            }

            // Sinkronisasi otomatis jika sheet aktif berganti di dalam Univer
            try {
                if (fWorkbook.activeSheet$ && typeof fWorkbook.activeSheet$.subscribe === 'function') {
                    fWorkbook.activeSheet$.subscribe((sheet) => {
                        if (sheet) {
                            const name = sheet.getName ? sheet.getName() : sheet.name;
                            if (name) updateActiveTabVisual(name);
                        }
                    });
                }
            } catch (e) {
                // Subscription optional
            }

            // Cegah kehilangan data saat refresh atau meninggalkan halaman
            const beforeUnloadHandler = (e) => {
                const { sheetsPayload, totalModified } = getModifiedCells();
                if (totalModified > 0 || isSaving) {
                    // Gunakan sendBeacon / keepalive fetch untuk simpan darurat
                    if (totalModified > 0 && navigator.sendBeacon) {
                        const blob = new Blob([JSON.stringify({ sheets: sheetsPayload })], { type: 'application/json' });
                        navigator.sendBeacon(`/laporan-keuangan-bulanan/${laporanId}/save-cells?_token=${encodeURIComponent(csrfToken)}`, blob);
                    }
                    e.preventDefault();
                    e.returnValue = 'Perubahan spreadsheet sedang disimpan. Anda yakin ingin meninggalkan halaman?';
                    return e.returnValue;
                }
            };
            window.addEventListener('beforeunload', beforeUnloadHandler);

            // Simpan saat klik navigasi tautan (SPA link)
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link) {
                    flushAutosave();
                }
            }, true);

            // Scroll button panah tab kiri / kanan
            const btnScrollTabsLeft = document.getElementById('btn-scroll-group-tabs-left');
            const btnScrollTabsRight = document.getElementById('btn-scroll-group-tabs-right');
            if (btnScrollTabsLeft && tabsList) {
                btnScrollTabsLeft.addEventListener('click', () => {
                    tabsList.scrollBy({ left: -140, behavior: 'smooth' });
                });
            }
            if (btnScrollTabsRight && tabsList) {
                btnScrollTabsRight.addEventListener('click', () => {
                    tabsList.scrollBy({ left: 140, behavior: 'smooth' });
                });
            }

           
            // Fullscreen toggle untuk Univer Spreadsheet (HANYA SPREADSHEET TANPA SIDEBAR)
            const fullscreenBtns = document.querySelectorAll('#btn-toggle-fullscreen, #btn-toggle-fullscreen-window, .btn-toggle-fullscreen');
            const windowWrapper = document.getElementById('excel-online-window') || container;

            if (fullscreenBtns.length > 0 && windowWrapper) {
                // Patch aman untuk unmount portal React/Radix saat node dipindah ke windowWrapper
                if (!document.body._univerFsPatched) {
                    document.body._univerFsPatched = true;
                    const origRemoveChild = document.body.removeChild;
                    document.body.removeChild = function (child) {
                        if (child && child.parentNode && child.parentNode !== this) {
                            return child.parentNode.removeChild(child);
                        }
                        return origRemoveChild.call(this, child);
                    };

                    const origContains = document.body.contains;
                    document.body.contains = function (node) {
                        return origContains.call(this, node) || (windowWrapper && windowWrapper.contains(node));
                    };
                }

                function isUniverPortal(node) {
                    if (!node || node.nodeType !== 1) return false;
                    return (
                        node.hasAttribute('data-radix-popper-content-wrapper') ||
                        node.hasAttribute('data-radix-portal') ||
                        node.hasAttribute('data-slot') ||
                        node.hasAttribute('dir') ||
                        node.classList.contains('univer-portal') ||
                        node.querySelector?.('[data-u-comp], [data-slot], [data-radix-popper-content-wrapper], .univer-portal') !== null
                    );
                }

                function syncPortalsToFullscreen(isFs) {
                    if (isFs) {
                        // Pindahkan portal Univer yang ada di body ke dalam windowWrapper agar tampil di fullscreen top-layer
                        Array.from(document.body.children).forEach((child) => {
                            if (child !== windowWrapper && !windowWrapper.contains(child) && isUniverPortal(child)) {
                                child.dataset.univerFsPortal = 'true';
                                windowWrapper.appendChild(child);
                            }
                        });
                    } else {
                        // Kembalikan portal ke document.body saat keluar dari fullscreen
                        windowWrapper.querySelectorAll('[data-univer-fs-portal="true"]').forEach((el) => {
                            delete el.dataset.univerFsPortal;
                            document.body.appendChild(el);
                        });
                    }
                }

                // Pantau popup/dropdown baru yang dibuka saat sedang dalam mode fullscreen
                const portalObserver = new MutationObserver((mutations) => {
                    const isFs = document.fullscreenElement === windowWrapper || windowWrapper.classList.contains('excel-window-fullscreen');
                    if (!isFs) return;

                    for (const m of mutations) {
                        for (const node of m.addedNodes) {
                            if (node.nodeType === 1 && isUniverPortal(node)) {
                                node.dataset.univerFsPortal = 'true';
                                windowWrapper.appendChild(node);
                            }
                        }
                    }
                });
                portalObserver.observe(document.body, { childList: true });

                function updateFullscreenIcons(isFullscreen) {
                    document.querySelectorAll('.fullscreen-icon-expand').forEach(el => el.classList.toggle('hidden', isFullscreen));
                    document.querySelectorAll('.fullscreen-icon-compress').forEach(el => el.classList.toggle('hidden', !isFullscreen));
                    fullscreenBtns.forEach(btn => {
                        btn.title = isFullscreen ? 'Kecilkan Tampilan (Keluar Fullscreen)' : 'Perbesar Tampilan (Fullscreen)';
                    });
                }

                const toggleFullscreen = async () => {
                    try {
                        const isFs = document.fullscreenElement === windowWrapper || windowWrapper.classList.contains('excel-window-fullscreen');
                        if (isFs) {
                            if (document.fullscreenElement) {
                                await document.exitFullscreen();
                            } else {
                                windowWrapper.classList.remove('excel-window-fullscreen');
                                syncPortalsToFullscreen(false);
                                updateFullscreenIcons(false);
                            }
                        } else {
                            if (windowWrapper.requestFullscreen) {
                                await windowWrapper.requestFullscreen();
                            } else {
                                windowWrapper.classList.add('excel-window-fullscreen');
                                syncPortalsToFullscreen(true);
                                updateFullscreenIcons(true);
                            }
                        }
                    } catch (err) {
                        console.warn('Fullscreen native request denied, using CSS window fullscreen:', err);
                        const isNowMax = !windowWrapper.classList.contains('excel-window-fullscreen');
                        windowWrapper.classList.toggle('excel-window-fullscreen', isNowMax);
                        syncPortalsToFullscreen(isNowMax);
                        updateFullscreenIcons(isNowMax);
                        requestAnimationFrame(() => {
                            window.dispatchEvent(new Event('resize'));
                        });
                    }
                };

                fullscreenBtns.forEach((btn) => {
                    if (!btn.dataset.bound) {
                        btn.dataset.bound = 'true';
                        btn.addEventListener('click', toggleFullscreen);
                    }
                });

                document.addEventListener('fullscreenchange', () => {
                    const isFullscreen = document.fullscreenElement === windowWrapper;

                    windowWrapper.classList.toggle('excel-window-fullscreen', isFullscreen);
                    syncPortalsToFullscreen(isFullscreen);
                    updateFullscreenIcons(isFullscreen);

                    requestAnimationFrame(() => {
                        window.dispatchEvent(new Event('resize'));
                    });
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


