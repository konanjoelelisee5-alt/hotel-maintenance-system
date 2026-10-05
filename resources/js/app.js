

import Alpine from 'alpinejs';
import './modal';
import './confirm';
import agenda from './agenda';
import hkOutbox from './hk-outbox';

window.Alpine = Alpine;
// Scan du QR de chambre (signalement HK) : module séparé, chargé seulement quand on scanne.
window.loadQrScanner = () => import('./qr-scanner');
// Signalements HK gardés dans le téléphone quand le réseau coupe (envoi différé).
window.hkOutbox = hkOutbox;

Alpine.data('agenda', agenda);

Alpine.start();
