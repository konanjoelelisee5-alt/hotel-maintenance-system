

import Alpine from 'alpinejs';
import './modal';
import './confirm';
// Après modal.js et confirm.js : leurs envois interceptés gardent leur propre affichage.
import './ui';
import agenda from './agenda';
import hkOutbox from './hk-outbox';
import pushToggle from './push';

window.Alpine = Alpine;
// Scan du QR de chambre (signalement HK) : module séparé, chargé seulement quand on scanne.
window.loadQrScanner = () => import('./qr-scanner');
// Signalements HK gardés dans le téléphone quand le réseau coupe (envoi différé).
window.hkOutbox = hkOutbox;

Alpine.data('agenda', agenda);
Alpine.data('pushToggle', pushToggle);

Alpine.start();
