import Alpine from 'alpinejs';

import { adminLogsData } from './data/admin-logs';

window.addEventListener('DOMContentLoaded', () => {
    window.Alpine = Alpine

    adminLogsData()
    
    Alpine.start()
  });