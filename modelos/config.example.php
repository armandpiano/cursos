ni modelos\config.example.php -ItemType File -Force
set-content modelos\config.example.php @'
<?php
// Copia este archivo a modelos/config.php y coloca tus credenciales reales.
const CLIENT_ID     = 'CAMBIAR_CLIENT_ID';
const CLIENT_SECRET = 'CAMBIAR_CLIENT_SECRET';
const REDIRECT_URI  = 'http://localhost/cursos/index.php';
'@
