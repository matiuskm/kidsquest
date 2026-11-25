// Perkecil foto di HP sebelum diunggah (hemat kuota & memori server); server tetap mengompres ulang.
const MAX_SIDE = 2000;
const MAX_BYTES = 10 * 1024 * 1024; // sama dengan batas server

export default () => ({
    busy: false,
    working: false,
    error: null,
    preview: null,

    async shrink(event) {
        const input = event.target;
        const file = input.files[0];
        this.error = null;
        if (!file) return;
        this.preview = URL.createObjectURL(file);
        this.working = true;

        try {
            const bmp = await createImageBitmap(file);
            const scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
            if (scale === 1) return this.checkSize(file);

            const canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * scale);
            canvas.height = Math.round(bmp.height * scale);
            canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));

            const dt = new DataTransfer();
            dt.items.add(new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
            input.files = dt.files;
            this.checkSize(dt.files[0]);
        } catch {
            this.checkSize(file); // biarkan file asli; server tetap memvalidasi
        } finally {
            this.working = false;
        }
    },

    checkSize(file) {
        if (file.size > MAX_BYTES) this.error = 'Fotonya terlalu besar (maksimal 10 MB).';
    },
});
