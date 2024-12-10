<script setup>
import { ref, onMounted } from 'vue';

const statusMessage = ref('Siap');
const deviceInfo = ref(null);
const isConnecting = ref(false);
const statusType = ref('info');
const device = ref(null);

// Update status function
const updateStatus = (message, type = 'info') => {
    statusMessage.value = message;
    statusType.value = type;
};

// Fungsi untuk menutup koneksi yang ada
const closeExistingConnections = async () => {
    try {
        const devices = await navigator.usb.getDevices();
        await Promise.all(devices.map(d => {
            if (d.opened) {
                return d.close().catch(console.error);
            }
            return Promise.resolve();
        }));
    } catch (error) {
        console.log('Error saat menutup koneksi:', error);
    }
};

// Fungsi untuk koneksi ke printer
const connectDevice = async () => {
    try {
        isConnecting.value = true;
        updateStatus('Mencari printer HPRT...', 'info');

        if (!navigator.usb) {
            throw new Error('Web USB tidak didukung di browser ini');
        }

        // Tutup koneksi yang ada
        await closeExistingConnections();

        // Filter khusus untuk HPRT TP806
        const vendorId = 0x20D1;
        const productId = 0x7008;

        // Minta device
        device.value = await navigator.usb.requestDevice({
            filters: [{
                vendorId: vendorId,
                productId: productId
            }]
        });

        updateStatus('Printer ditemukan, membuka koneksi...', 'info');

        // Delay sebelum membuka koneksi
        await new Promise(resolve => setTimeout(resolve, 1000));

        try {
            if (device.value.opened) {
                await device.value.close();
            }
            await device.value.open();
            console.log('Device opened');

            // Konfigurasi printer
            const configuration = device.value.configurations[0];
            await device.value.selectConfiguration(configuration.configurationValue);
            console.log('Configuration selected');

            // Cari interface yang tepat
            const interface_ = configuration.interfaces[0];
            const alternate = interface_.alternates[0];

            await device.value.claimInterface(interface_.interfaceNumber);
            console.log('Interface claimed');

            updateStatus('Printer berhasil terhubung!', 'success');

            // Update info device
            deviceInfo.value = {
                manufacturer: device.value.manufacturerName?.trim().replace(/\x00/g, '') || 'N/A',
                product: device.value.productName?.trim().replace(/\x00/g, '') || 'N/A',
                serial: device.value.serialNumber?.trim().replace(/\x00/g, '') || 'N/A'
            };

        } catch (error) {
            console.error('Error saat membuka koneksi:', error);
            if (device.value?.opened) {
                await device.value.close().catch(console.error);
            }
            throw new Error('Gagal membuka koneksi ke printer');
        }

    } catch (error) {
        let errorMessage = 'Error tidak diketahui';

        if (error.name === 'SecurityError') {
            errorMessage = 'Akses ditolak. Coba:\n1. Tutup aplikasi lain yang menggunakan printer\n2. Cabut dan colok ulang printer\n3. Restart browser';
        } else if (error.name === 'NotFoundError') {
            errorMessage = 'Printer tidak ditemukan. Pastikan:\n1. Printer terhubung ke USB\n2. Driver terinstall\n3. Printer menyala';
        } else {
            errorMessage = `Error: ${error.message}`;
        }

        updateStatus(errorMessage, 'error');
        console.error('Detail error:', error);
    } finally {
        isConnecting.value = false;
    }
};

// Test print function
const testPrint = async () => {
    if (!device.value || !device.value.opened) {
        updateStatus('Printer tidak terhubung', 'error');
        return;
    }

    try {
        // Command untuk test print (sesuaikan dengan command set printer HPRT)
        const data = new Uint8Array([
            0x1B, 0x40,             // Initialize printer
            0x1B, 0x61, 0x01,       // Center alignment
            0x1B, 0x45, 0x01,       // Bold on
            'TEST PRINT\n'.split('').map(c => c.charCodeAt(0)),
            0x1B, 0x45, 0x00,       // Bold off
            0x0A, 0x0A, 0x0A,       // Line feeds
            0x1D, 0x56, 0x00        // Cut paper
        ].flat());

        // Kirim data ke printer
        await device.value.transferOut(1, data);
        updateStatus('Test print berhasil', 'success');
    } catch (error) {
        console.error('Error saat test print:', error);
        updateStatus('Gagal melakukan test print', 'error');
    }
};

// Monitoring koneksi device
onMounted(() => {
    navigator.usb?.addEventListener('connect', (event) => {
        updateStatus('Printer terdeteksi!', 'info');
    });

    navigator.usb?.addEventListener('disconnect', (event) => {
        updateStatus('Printer terputus', 'info');
        deviceInfo.value = null;
        device.value = null;
    });
});
</script>

<template>
    <div class="printer-connector">
        <div :class="['status', statusType]">
            <p>Status: {{ statusMessage }}</p>
        </div>

        <div class="button-group">
            <button @click="connectDevice" :disabled="isConnecting" class="connect-button">
                {{ isConnecting ? 'Menghubungkan...' : 'Hubungkan Printer' }}
            </button>

            <button @click="testPrint" :disabled="!device?.opened" class="print-button">
                Test Print
            </button>
        </div>

        <div v-if="deviceInfo" class="device-info">
            <h3>Informasi Printer:</h3>
            <pre>
Manufacturer: {{ deviceInfo.manufacturer }}
Product: {{ deviceInfo.product }}
Serial: {{ deviceInfo.serial }}
      </pre>
        </div>
    </div>
</template>

<style scoped>
.printer-connector {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
}

.status {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
    white-space: pre-line;
}

.button-group {
    display: flex;
    gap: 10px;
    margin: 10px 0;
}

.connect-button,
.print-button {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    flex: 1;
}

.connect-button {
    background-color: #4CAF50;
    color: white;
}

.print-button {
    background-color: #2196F3;
    color: white;
}

.connect-button:disabled,
.print-button:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

.info {
    background-color: #d9edf7;
    color: #31708f;
}

.success {
    background-color: #dff0d8;
    color: #3c763d;
}

.error {
    background-color: #f2dede;
    color: #a94442;
}

.device-info {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    background-color: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
}
</style>