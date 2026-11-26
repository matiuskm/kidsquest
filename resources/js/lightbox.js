// Tautan [data-lightbox] dibuka di <dialog>, bukan pindah halaman. Tanpa JS tautannya tetap membuka foto.
document.addEventListener('click', (event) => {
    const box = document.getElementById('lightbox');
    if (!box) return;

    const link = event.target.closest('[data-lightbox]');
    if (link) {
        event.preventDefault();
        box.querySelector('img').src = link.href;
        box.showModal();
    } else if (event.target === box) {
        box.close(); // klik latar gelap
    }
});
