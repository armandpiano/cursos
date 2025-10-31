// clls/app.js
// JS ligero para posibles mejoras de UX. Mantiene la funcionalidad intacta.
(function () {
  'use strict';

  // Evita doble inicialización si en el futuro amplías
  if (window.__LOGIN_APP_INIT__) return;
  window.__LOGIN_APP_INIT__ = true;

  // Hint: podrías leer params ?error=... y mostrar alert
  const params = new URLSearchParams(location.search);
  if (params.get('oauth') === 'failed') {
    console.warn('OAuth fallido');
  }
})();
