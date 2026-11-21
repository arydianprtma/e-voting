/**
 * Memperbarui favicon tab browser secara dinamis sesuai logo pemilihan yang aktif.
 *
 * @param {string} url - URL atau Object URL dari logo/ikon
 */
export function updateFavicon(url) {
    if (!url || typeof document === 'undefined') return;

    // Hapus link rel icon lama untuk memicu refresh instan di browser
    const existingIcons = document.querySelectorAll("link[rel*='icon']");
    existingIcons.forEach((el) => el.remove());

    // Buat link favicon baru
    const link = document.createElement('link');
    link.rel = 'icon';
    link.id = 'dynamic-favicon';
    if (url.includes('.svg')) {
        link.type = 'image/svg+xml';
    }
    link.href = url;
    document.head.appendChild(link);

    // Buat apple-touch-icon baru
    const appleLink = document.createElement('link');
    appleLink.rel = 'apple-touch-icon';
    appleLink.id = 'dynamic-apple-icon';
    appleLink.href = url;
    document.head.appendChild(appleLink);
}
