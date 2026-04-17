<?php
// pix.php — BR Code Pix (EMV) padrão: TXID em 62-05, chave em 26-01

function emv_crc16($payload) {
  $polynom = 0x1021; $result = 0xFFFF;
  $len = strlen($payload);
  for ($i = 0; $i < $len; $i++) {
    $result ^= (ord($payload[$i]) << 8);
    for ($b = 0; $b < 8; $b++) {
      $result = ($result & 0x8000) ? (($result << 1) ^ $polynom) : ($result << 1);
      $result &= 0xFFFF;
    }
  }
  return strtoupper(str_pad(dechex($result), 4, '0', STR_PAD_LEFT));
}

function emv_kv($id, $value) {
  $len = strlen($value);
  return $id . str_pad((string)$len, 2, '0', STR_PAD_LEFT) . $value;
}

/**
 * $pixKey: telefone/email/CPF/CNPJ/EVP
 * $merchantName: máx ~25
 * $merchantCity: máx ~15
 * $amount: string "150.00"
 * $txid: até 35 chars
 */
function build_pix_brcode(string $pixKey, string $merchantName, string $merchantCity, string $amount, string $txid): string {
  $pfi = emv_kv('00', '01'); // Payload Format Indicator
  $poi = emv_kv('01', '12'); // 12 = dinâmico

  $mai = emv_kv('00', 'br.gov.bcb.pix') . emv_kv('01', $pixKey);
  $mai = emv_kv('26', $mai);

  $mcc = emv_kv('52', '0000');
  $cur = emv_kv('53', '986');
  $amt = emv_kv('54', number_format((float)$amount, 2, '.', ''));
  $cty = emv_kv('58', 'BR');
  $nam = emv_kv('59', mb_strimwidth($merchantName, 0, 25, ''));
  $cit = emv_kv('60', mb_strimwidth($merchantCity, 0, 15, ''));

  $add = emv_kv('05', $txid);
  $add = emv_kv('62', $add);

  $payload = $pfi . $poi . $mai . $mcc . $cur . $amt . $cty . $nam . $cit . $add;

  $toCRC = $payload . '6304';
  $crc = emv_crc16($toCRC);
  return $payload . '63' . '04' . $crc;
}
