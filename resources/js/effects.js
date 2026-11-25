// Suara + konfeti. Autoplay butuh interaksi pengguna; kegagalan diam-diam diabaikan.
const EMOJI = ['🎉', '⭐', '✨', '🎈', '🌈'];
const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export default {
    audio: {},

    play(name) {
        try {
            const a = (this.audio[name] ??= new Audio(`/sounds/${name}.wav`));
            a.currentTime = 0;
            a.play().catch(() => {});
        } catch {
            // tidak ada audio, tidak apa-apa
        }
    },

    confetti() {
        if (reduceMotion()) return;
        const layer = document.createElement('div');
        layer.className = 'kq-confetti';
        for (let i = 0; i < 28; i++) {
            const piece = document.createElement('span');
            piece.textContent = EMOJI[i % EMOJI.length];
            piece.style.left = `${Math.random() * 100}%`;
            piece.style.animationDelay = `${Math.random() * 0.8}s`;
            piece.style.animationDuration = `${2 + Math.random() * 2}s`;
            layer.appendChild(piece);
        }
        document.body.appendChild(layer);
        setTimeout(() => layer.remove(), 5000);
    },

    // Bandingkan dengan nilai terakhir yang terlihat di device ini; bila naik, bunyi (dan konfeti).
    watch(key, value, sound, celebrate = false) {
        try {
            const k = `kq:${key}`;
            const prev = localStorage.getItem(k);
            localStorage.setItem(k, String(value));
            if (prev !== null && Number(value) > Number(prev)) {
                this.play(sound);
                if (celebrate) this.confetti();
            }
        } catch {
            // localStorage tidak tersedia
        }
    },
};
