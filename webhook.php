<?php
/**
 * DPD Trust YouCan Integration Webhook Handler
 * Version: 1.0.0
 */

// Configuration
define('DPD_API_KEY', 'YOUR_DPD_API_KEY');
define('DPD_API_ENDPOINT', 'https://api.dpd.ma/v1/orders');
define('YOUCAN_WEBHOOK_SECRET', 'YOUR_YOUCAN_WEBHOOK_SECRET'); // Leave empty if signature verification is not used

// Read incoming request body
$rawPayload = file_get_contents('php://input');
$headers = getallheaders();

// Verify YouCan signature (highly recommended)
if (!empty(YOUCAN_WEBHOOK_SECRET)) {
    $signature = isset($headers['X-YouCan-Signature']) ? $headers['X-YouCan-Signature'] : '';
    $computedSignature = hash_hmac('sha256', $rawPayload, YOUCAN_WEBHOOK_SECRET);
    if (!hash_equals($computedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'INVALID_SIGNATURE']);
        exit;
    }
}

$data = json_decode($rawPayload, true);
if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'INVALID_PAYLOAD']);
    exit;
}

// Map YouCan payload to the approved minimum DPD telemetry structure
$dpdPayload = [
    'platform'           => 'youcan',
    'store_id'           => isset($data['store_id']) ? $data['store_id'] : 'unknown_store',
    'external_order_id'  => (string)$data['id'],
    'normalized_phone'   => normalizePhone(isset($data['billing_address']['phone']) ? $data['billing_address']['phone'] : ''),
    'payment_type'       => isset($data['payment_method']) && stripos($data['payment_method'], 'cod') !== false ? 'COD' : 'ONLINE',
    'amount'             => (float)$data['total_price'],
    'currency'           => isset($data['currency']) ? $data['currency'] : 'MAD',
    'status'             => strtoupper(isset($data['status']) ? $data['status'] : 'pending'),
    'timestamp'          => date('c', strtotime(isset($data['created_at']) ? $data['created_at'] : 'now'))
];

// Send event to DPD platform asynchronously
$ch = curl_init(DPD_API_ENDPOINT);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dpdPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . DPD_API_KEY,
    'Accept: application/json',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Return HTTP 200 immediately to YouCan so checkout is never blocked
http_response_code(200);
echo json_encode(['ok' => true, 'forwarded' => ($httpCode === 200)]);

/**
 * Basic phone normalization helper
 */
function normalizePhone($phone) {
    // Strip non-digits
    $clean = preg_replace('/\D/', '', $phone);
    // Standard Morocco phone formatting check
    if (preg_match('/^(212|0)([567]\d{8})$/', $clean, $matches)) {
        return '+212' . $matches[2];
    }
    return '+' . $clean;
}
