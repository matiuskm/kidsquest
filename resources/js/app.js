import Alpine from 'alpinejs';
import effects from './effects';
import photoUpload from './photo-upload';
import { logoutForm, pushToggle } from './push';

window.Alpine = Alpine;
Alpine.store('fx', effects);
Alpine.data('photoUpload', photoUpload);
Alpine.data('pushToggle', pushToggle);
Alpine.data('logoutForm', logoutForm);
Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
