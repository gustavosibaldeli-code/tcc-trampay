<?php
// config.php — configs gerais do projeto

// ===================== TRAMPAY (PIX e taxa) =====================
define('TRAMPAY_PIX_KEY', '11988862604');  // sua chave PIX
define('TRAMPAY_FEE_PERCENT', 0.00);       // 0.00 = recebe 100%

// ===================== E-MAIL (opcional) =====================
define('MAIL_FROM', 'no-reply@trampay.local');
define('MAIL_FROM_NAME', 'Trampay');
define('MAIL_SMTP_HOST', '');
define('MAIL_SMTP_USER', '');
define('MAIL_SMTP_PASS', '');
define('MAIL_SMTP_PORT', 587);

// ===================== WEBHOOK público (https) =====================
// Ex.: https://abcd-1234.sa.ngrok.io/pagamento_webhook_pix.php
define('WEBHOOK_URL', 'https://abcd-1234.sa.ngrok.io/pagamento_webhook_pix.php');
