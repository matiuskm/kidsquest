import Alpine from 'alpinejs';
import photoUpload from './photo-upload';

window.Alpine = Alpine;
Alpine.data('photoUpload', photoUpload);
Alpine.start();
