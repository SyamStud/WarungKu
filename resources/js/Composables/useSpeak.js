// utils/useSpeak.js

import useTerbilang from './useTerbilang';

function useSpeak(input) {
    if ('speechSynthesis' in window) {
        let speech;

        if (typeof input === 'number') {
            const terbilang = useTerbilang(input);
            speech = terbilang;
        } else {
            speech = input;
        }

        const utterance = new SpeechSynthesisUtterance(speech);
        utterance.lang = 'id-ID'; // Set language to Indonesian

        speechSynthesis.speak(utterance);
    } else {
        console.error('Text-to-speech not supported in this browser');
    }
}

export default useSpeak;