// utils/useSpeak.js
import useTerbilang from './useTerbilang';

function useSpeak(input) {
    if ('speechSynthesis' in window) {
        // Cancel any ongoing speech
        window.speechSynthesis.cancel();

        let speech;
        if (typeof input === 'number') {
            const terbilang = useTerbilang(input);
            speech = terbilang;
        } else {
            speech = input;
        }

        const utterance = new SpeechSynthesisUtterance(speech);
        utterance.lang = 'id-ID';
        utterance.rate = 1.0; // Tambahkan rate yang normal
        utterance.pitch = 1.0; // Tambahkan pitch yang normal
        utterance.volume = 1.0; // Pastikan volume maksimal

        // Tambahkan error handling
        utterance.onerror = (event) => {
            console.error('Speech synthesis error:', event.error);
        };

        // Workaround untuk Chrome
        setTimeout(() => {
            window.speechSynthesis.speak(utterance);
        }, 100);

        // Pastikan speech synthesis tidak tertunda
        const timeoutId = setTimeout(() => {
            if (window.speechSynthesis.pending) {
                window.speechSynthesis.resume();
            }
        }, 1000);

        // Cleanup timeout
        utterance.onend = () => {
            clearTimeout(timeoutId);
        };

    } else {
        console.error('Text-to-speech not supported in this browser');
    }
}

// Tambahkan ini untuk mengatasi bug Chrome yang kadang "stuck"
if ('speechSynthesis' in window) {
    const synth = window.speechSynthesis;
    let utteranceQueue = [];
    
    const processQueue = () => {
        if (utteranceQueue.length > 0 && !synth.speaking) {
            const utterance = utteranceQueue.shift();
            synth.speak(utterance);
        }
    };

    setInterval(() => {
        if (synth.speaking) {
            synth.pause();
            synth.resume();
        }
        processQueue();
    }, 5000);
}

export default useSpeak;