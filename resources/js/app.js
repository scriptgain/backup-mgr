import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// Alpine powers the interactive components (toggles, modals, dropdowns). We
// never use native alert/confirm/prompt; confirmation flows use <x-modal>.
Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();
