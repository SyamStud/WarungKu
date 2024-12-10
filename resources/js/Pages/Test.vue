<template>
    <div class="p-4 max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold mb-2">USB Printer Controller</h1>
            <p class="text-gray-600">Koneksikan dan kontrol printer HPRT via USB</p>
        </div>

        <!-- Status Card -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-6">
            <div class="flex items-center justify-between mb-4">
                <span class="font-medium">Status Printer:</span>
                <span :class="[
                    'px-3 py-1 rounded-full text-sm',
                    isConnected ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]">
                    {{ isConnected ? 'Terhubung' : 'Tidak Terhubung' }}
                </span>
            </div>

            <div v-if="deviceInfo" class="text-sm text-gray-600">
                <p>Manufacturer: {{ deviceInfo.manufacturerName || 'N/A' }}</p>
                <p>Product: {{ deviceInfo.productName || 'N/A' }}</p>
                <p>Serial: {{ deviceInfo.serialNumber || 'N/A' }}</p>
            </div>
        </div>

        <!-- Control Buttons -->
        <div class="space-y-4">
            <button @click="connectPrinter" :disabled="isConnecting || isConnected"
                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="isConnecting">Menghubungkan...</span>
                <span v-else>Hubungkan Printer</span>
            </button>

            <button @click="disconnectPrinter" :disabled="!isConnected"
                class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Putus Koneksi
            </button>

            <button @click="testPrint" :disabled="!isConnected"
                class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Test Print
            </button>
        </div>

        <!-- Error Alert -->
        <div v-if="error" class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"> {{ error }}</span>
            <button @click="error = ''" class="absolute top-0 right-0 px-4 py-3">
                <span class="text-2xl">&times;</span>
            </button>
        </div>

        <!-- Debug Log -->
        <div v-if="showDebugLog" class="mt-6">
            <h3 class="font-medium mb-2">Debug Log:</h3>
            <div class="bg-gray-100 p-4 rounded-lg text-sm font-mono h-40 overflow-y-auto">
                <div v-for="(log, index) in debugLogs" :key="index">
                    {{ log }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// State management
const isConnected = ref(false)
const isConnecting = ref(false)
const error = ref('')
const device = ref(null)
const deviceInfo = ref(null)
const debugLogs = ref([])
const showDebugLog = ref(true) // Set false untuk production

// USB Device filters
const PRINTER_FILTERS = {
    filters: [{
        vendorId: 0x20D1,
        productId: 0x7008
    }]
}

// Utility functions
const addDebugLog = (message) => {
    const timestamp = new Date().toLocaleTimeString()
    debugLogs.value.unshift(`[${timestamp}] ${message}`)
    if (debugLogs.value.length > 100) debugLogs.value.pop()
}

const handleError = (err) => {
    error.value = err.message
    addDebugLog(`Error: ${err.message}`)
    isConnecting.value = false
}

// Check Web USB Support
const checkWebUSBSupport = () => {
    if (!navigator.usb) {
        throw new Error('Browser tidak mendukung Web USB API. Gunakan Chrome/Edge versi terbaru.')
    }
}

// Connect to printer
const connectPrinter = async () => {
    try {
        isConnecting.value = true
        error.value = ''

        checkWebUSBSupport()
        addDebugLog('Requesting USB device...')

        // Request device
        device.value = await navigator.usb.requestDevice(PRINTER_FILTERS)
        addDebugLog('Device selected, opening connection...')

        // Open connection
        await device.value.open()

        // Select configuration
        if (device.value.configuration === null) {
            await device.value.selectConfiguration(1)
        }

        // Claim interface
        const interface_number = 0
        if (!device.value.configuration.interfaces[interface_number].claimed) {
            await device.value.claimInterface(interface_number)
        }

        // Store device info
        deviceInfo.value = {
            manufacturerName: device.value.manufacturerName,
            productName: device.value.productName,
            serialNumber: device.value.serialNumber
        }

        isConnected.value = true
        addDebugLog('Printer connected successfully')

    } catch (err) {
        handleError(err)
    } finally {
        isConnecting.value = false
    }
}

// Disconnect printer
const disconnectPrinter = async () => {
    try {
        if (device.value) {
            await device.value.close()
            device.value = null
            deviceInfo.value = null
            isConnected.value = false
            addDebugLog('Printer disconnected')
        }
    } catch (err) {
        handleError(err)
    }
}

// Test print
const testPrint = async () => {
    if (!device.value || !isConnected.value) {
        handleError(new Error('Printer tidak terhubung'))
        return
    }

    try {
        const data = 'Test Print\n\nPrinter berhasil terhubung!\n\n\n'
        const encoder = new TextEncoder()
        const dataBuffer = encoder.encode(data)

        addDebugLog('Sending test print data...')
        await device.value.transferOut(1, dataBuffer)
        addDebugLog('Test print completed')

    } catch (err) {
        handleError(err)
    }
}

// Lifecycle hooks
onMounted(() => {
    addDebugLog('Component mounted')
    // Check for previously authorized devices
    navigator.usb?.getDevices()
        .then(devices => {
            if (devices.length > 0) {
                device.value = devices[0]
                addDebugLog('Found previously authorized device')
            }
        })
        .catch(err => addDebugLog(`Error checking devices: ${err.message}`))
})

onUnmounted(async () => {
    if (device.value && isConnected.value) {
        await disconnectPrinter()
    }
})
</script>

<style scoped>
/* Additional styles can be added here if needed */
</style>