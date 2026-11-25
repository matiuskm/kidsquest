// Perkecil foto di HP sebelum diunggah (hemat kuota & memori server); server tetap mengompres ulang.
const MAX_SIDE = 2000;

export default () => ({
    busy: false,
    preview: null,

    async shrink(event) {
        const input = event.target;
        const file = input.files[0];
        if (!file) return;
        this.preview = URL.createObjectURL(file);

        try {
            const bmp = await createImageBitmap(file);
            const scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
            if (scale === 1) return;

            const canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * scale);
            canvas.height = Math.round(bmp.height * scale);
            canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));

            const dt = new DataTransfer();
            dt.items.add(new File([blob], 'foto.jpg', { type: 'image/jpeg' }));
            input.files = dt.files;
        } catch {
            // Biarkan file asli; server akan memvalidasi.
        }
    },
});
