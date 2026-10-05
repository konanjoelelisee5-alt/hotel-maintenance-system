// Lecture du QR code collé dans la chambre (signalement Housekeeping).
// Chargé à la demande (window.loadQrScanner dans app.js) : la bibliothèque n'alourdit
// pas les autres pages. BarcodeDetector quand le navigateur l'a (Android/Chrome),
// sinon jsQR, qui fonctionne aussi sur iPhone.
import jsQR from 'jsqr';

/**
 * Démarre la caméra arrière dans `video` et appelle `onCode(texte)` au premier QR lu.
 * Rend une fonction d'arrêt (caméra coupée). Rejette si la caméra est refusée.
 */
export async function startScan(video, onCode) {
    const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false,
    });
    video.srcObject = stream;
    video.setAttribute('playsinline', '');
    await video.play();

    let stopped = false;
    let frame = null;
    let detector = null;
    if ('BarcodeDetector' in window) {
        try {
            const formats = await window.BarcodeDetector.getSupportedFormats();
            if (formats.includes('qr_code')) detector = new window.BarcodeDetector({ formats: ['qr_code'] });
        } catch (e) { detector = null; }
    }
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    const stop = () => {
        stopped = true;
        if (frame) cancelAnimationFrame(frame);
        stream.getTracks().forEach((t) => t.stop());
        video.srcObject = null;
    };

    const tick = async () => {
        if (stopped) return;
        if (video.readyState >= 2) {
            let text = null;
            if (detector) {
                const codes = await detector.detect(video).catch(() => []);
                text = codes[0]?.rawValue ?? null;
            } else {
                // Image réduite : jsQR reste fluide sur un téléphone modeste.
                const scale = Math.min(1, 640 / video.videoWidth);
                canvas.width = Math.round(video.videoWidth * scale);
                canvas.height = Math.round(video.videoHeight * scale);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
                text = jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' })?.data ?? null;
            }
            if (text && !stopped) {
                stop();
                onCode(text);
                return;
            }
        }
        frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);

    return stop;
}

/** Numéro de chambre lu dans le QR : lien « …/signaler?chambre=214 » ou numéro seul. */
export function roomFromCode(text) {
    try {
        const url = new URL(text);
        const room = url.searchParams.get('chambre');
        if (room) return room.trim();
    } catch (e) { /* pas une adresse */ }
    const digits = String(text).trim();
    return /^[0-9A-Za-z-]{1,6}$/.test(digits) ? digits : null;
}
