

import Alpine from 'alpinejs';
import './modal';
import './confirm';
import agenda from './agenda';

window.Alpine = Alpine;

Alpine.data('agenda', agenda);

Alpine.start();
