
import { createUniver, LocaleType, mergeLocales } from '@univerjs/presets';
import { UniverSheetsCorePreset } from '@univerjs/preset-sheets-core';
import UniverPresetSheetsCoreEnUS from '@univerjs/preset-sheets-core/locales/en-US';

import '@univerjs/preset-sheets-core/lib/index.css';

const container = document.getElementById('spreadsheet-test');

if (!container) {
    throw new Error('Container spreadsheet-test tidak ditemukan');
}

const { univer, univerAPI } = createUniver({
    locale: LocaleType.EN_US,
    locales: {
        [LocaleType.EN_US]: mergeLocales(
            UniverPresetSheetsCoreEnUS
        ),
    },
    presets: [
        UniverSheetsCorePreset({
            container: 'spreadsheet-test',
        }),
    ],
});

univerAPI.createWorkbook({
    id: 'wb-test',
    name: 'Uji Spreadsheet Multi-Sheet',
    sheetOrder: ['sheet-1', 'sheet-2'],
    sheets: {
        'sheet-1': {
            id: 'sheet-1',
            name: 'NERACA',
            rowCount: 50,
            columnCount: 26,
            cellData: {
                0: {
                    0: { v: 'Komponen' },
                    1: { v: 'Nilai 1' },
                    2: { v: 'Nilai 2' },
                    3: { v: 'Total' }
                },
                1: {
                    0: { v: 'Kas dan Setara Kas' },
                    1: { v: 1500000000 },
                    2: { v: 2500000000 },
                    3: { v: 4000000000, f: '=SUM(B2:C2)' }
                },
                2: {
                    0: { v: 'Piutang Usaha' },
                    1: { v: 500000000 },
                    2: { v: 300000000 },
                    3: { v: 800000000, f: '=B3+C3' }
                }
            }
        },
        'sheet-2': {
            id: 'sheet-2',
            name: 'INCOME',
            rowCount: 50,
            columnCount: 26,
            cellData: {
                0: {
                    0: { v: 'Uraian' },
                    1: { v: 'Pendapatan' }
                },
                1: {
                    0: { v: 'Penjualan Tenaga Listrik' },
                    1: { v: 12000000000 }
                }
            }
        }
    }
});

console.log('Workbook Univer multi-sheet berhasil dibuat', univer);
