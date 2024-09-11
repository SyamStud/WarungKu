// src/composables/useMidtrans.js
import { ref } from 'vue';

export function useMidtrans() {
  const isSnapsLoaded = ref(false);

  const initializeSnap = () => {
    if (isSnapsLoaded.value) return;

    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', import.meta.env.VITE_MIDTRANS_CLIENT_KEY);
    script.onload = () => {
      isSnapsLoaded.value = true;
    };
    document.head.appendChild(script);
  };

  const processPayment = (snapToken) => {
    return new Promise((resolve, reject) => {
      if (!isSnapsLoaded.value) {
        reject(new Error('Snap.js is not loaded'));
        return;
      }

      window.snap.pay(snapToken, {
        onSuccess: (result) => resolve(result),
        onPending: (result) => resolve(result),
        onError: (result) => resolve(result),
        onClose: () => {
          reject(new Error('Payment popup closed'));
        },
      });
    });
  };

  return {
    initializeSnap,
    processPayment,
  };
}