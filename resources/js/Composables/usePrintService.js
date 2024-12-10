import { ref } from 'vue';

export function usePrintService() {
    const printServerUrl = ref(localStorage.getItem('printServerUrl') || null);
    const defaultPrinter = ref(localStorage.getItem('defaultPrinter') || null);

    const getLocalIPs = async () => {
        try {
            // Menggunakan WebRTC untuk mendapatkan IP lokal
            const pc = new RTCPeerConnection({
                iceServers: []
            });
            
            return new Promise((resolve, reject) => {
                const ips = new Set();
                
                pc.createDataChannel('');
                pc.createOffer()
                    .then(offer => pc.setLocalDescription(offer))
                    .catch(reject);
    
                pc.onicecandidate = (event) => {
                    if (!event.candidate) {
                        pc.close();
                        resolve(Array.from(ips));
                        return;
                    }
                    
                    const candidate = event.candidate.candidate;
                    const match = candidate.match(/([0-9]{1,3}(\.[0-9]{1,3}){3})/);
                    if (match) {
                        const ip = match[1];
                        if (ip.startsWith('192.168.') || ip.startsWith('10.') || ip.startsWith('172.')) {
                            ips.add(ip);
                        }
                    }
                };
    
                // Timeout setelah 5 detik
                setTimeout(() => {
                    pc.close();
                    resolve(Array.from(ips));
                }, 5000);
            });
        } catch (error) {
            console.error('Error getting local IPs:', error);
            return [];
        }
    };
    
    const setupPrintServer = async (url = null) => {
        if (!url) {
            try {
                const localIPs = await getLocalIPs();
                
                // Coba setiap IP yang ditemukan
                for (const ip of localIPs) {
                    const testUrl = `http://${ip}:5000`;
                    try {
                        const response = await fetch(`${testUrl}/`);
                        const data = await response.json();
    
                        if (data.status === 'online') {
                            printServerUrl.value = testUrl;
                            localStorage.setItem('printServerUrl', testUrl);
                            console.log(`Print server ditemukan di: ${testUrl}`);
                            return true;
                        }
                    } catch (error) {
                        console.log(`Tidak dapat terhubung ke ${testUrl}`);
                        continue;
                    }
                }
    
                // Jika tidak ada IP yang berhasil, gunakan IP dari localStorage atau minta input
                url = localStorage.getItem('printServerUrl') || prompt('Print server tidak ditemukan otomatis. Masukkan alamat Print Server:', 'http://192.168.1.x:5000');
                
                if (!url) return false;
            } catch (error) {
                console.error('Error dalam pencarian otomatis:', error);
                url = prompt('Gagal mencari otomatis. Masukkan alamat Print Server:', 'http://192.168.1.x:5000');
                if (!url) return false;
            }
        }
    
        try {
            // Test koneksi final
            const response = await fetch(`${url}/`);
            const data = await response.json();
    
            if (data.status === 'online') {
                printServerUrl.value = url;
                localStorage.setItem('printServerUrl', url);
                return true;
            }
            throw new Error('Print server tidak merespon dengan benar');
    
        } catch (error) {
            alert(`Gagal terhubung ke print server: ${error.message}`);
            return false;
        }
    };

    // Setup print server URL
    // const setupPrintServer = async (url = null) => {
    //     if (!url) {
    //         url = prompt('Masukkan alamat Print Server:', printServerUrl.value || 'http://192.168.1.x:5000');
    //     }

    //     if (!url) return false;

    //     try {
    //         // Test koneksi
    //         const response = await fetch(`${url}/`);
    //         const data = await response.json();

    //         if (data.status === 'online') {
    //             printServerUrl.value = url;
    //             localStorage.setItem('printServerUrl', url);
    //             return true;
    //         }
    //         throw new Error('Print server tidak merespon dengan benar');

    //     } catch (error) {
    //         alert(`Gagal terhubung ke print server: ${error.message}`);
    //         return false;
    //     }
    // };

    // Ambil daftar printer
    const getPrinters = async () => {
        if (!printServerUrl.value) {
            throw new Error('Print server belum dikonfigurasi');
        }

        const response = await fetch(`${printServerUrl.value}/printers`);
        const data = await response.json();
        return data.printers;
    };

    // Set default printer
    const setDefaultPrinter = async (printerName) => {
        defaultPrinter.value = printerName;
        localStorage.setItem('defaultPrinter', printerName);
    };

    // Kirim print job
    const print = async (content, printerName = null) => {
        if (!printServerUrl.value) {
            throw new Error('Print server belum dikonfigurasi');
        }

        const printer = printerName || defaultPrinter.value;
        if (!printer) {
            throw new Error('Printer belum dipilih');
        }

        try {
            const response = await axios.post(`${printServerUrl.value}/print`, {
                printer_name: printer,
                content: content,
            }, {
                headers: {
                    'Content-Type': 'application/json',
                },
            });
        } catch (error) {
            console.error('Print error:', error);
            throw error;
        }
    };

    return {
        printServerUrl,
        defaultPrinter,
        setupPrintServer,
        getPrinters,
        setDefaultPrinter,
        print,
    };
}
