document.addEventListener('DOMContentLoaded', () => {
    const startButton = document.querySelector('#start-scanner');
    const stopButton = document.querySelector('#stop-scanner');
    const status = document.querySelector('#scanner-status');
    const manualCode = document.querySelector('#location_code');

    if (!startButton || typeof Html5Qrcode === 'undefined') return;

    const scanner = new Html5Qrcode('qr-reader');
    let running = false;

    const stop = async () => {
        if (!running) return;
        await scanner.stop();
        scanner.clear();
        running = false;
        startButton.hidden = false;
        stopButton.hidden = true;
        status.textContent = 'Kamera dimatikan. Anda juga dapat memakai kode label.';
    };

    const openResult = async (decodedText) => {
        await stop();
        try {
            const result = new URL(decodedText, window.location.origin);
            if (result.origin === window.location.origin && /^\/lapor\/[0-9a-f-]{36}$/i.test(result.pathname)) {
                status.textContent = 'QR dikenali. Membuka formulir…';
                window.location.assign(result.href);
                return;
            }
        } catch (_) {}

        manualCode.value = decodedText.trim().toUpperCase();
        status.textContent = 'QR bukan label Apart Care. Periksa atau masukkan kode yang tercetak.';
        manualCode.focus();
    };

    startButton.addEventListener('click', async () => {
        status.textContent = 'Meminta izin kamera…';
        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 230, height: 230 }, aspectRatio: 1 },
                openResult,
                () => {},
            );
            running = true;
            startButton.hidden = true;
            stopButton.hidden = false;
            status.textContent = 'Arahkan QR ke dalam bingkai dan tahan sebentar.';
        } catch (_) {
            status.textContent = 'Kamera tidak dapat digunakan. Izinkan akses kamera atau masukkan kode label.';
            startButton.hidden = false;
            stopButton.hidden = true;
        }
    });

    stopButton.addEventListener('click', stop);
    window.addEventListener('pagehide', stop);
});
