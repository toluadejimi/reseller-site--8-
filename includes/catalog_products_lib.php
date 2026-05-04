<?php
/**
 * Fetch reseller catalog products from the platform API.
 *
 * @return array{products: array<int, array>, error: string}
 */
function reseller_fetch_catalog_products(string $apiKey, string $baseUrl): array
{
    $products = [];
    $error = '';
    $baseUrl = rtrim($baseUrl, '/');
    if ($apiKey === '' || $baseUrl === '') {
        return ['products' => [], 'error' => 'Store configuration incomplete.'];
    }
    $ch = curl_init($baseUrl . '/api/reseller/products');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-Api-Key: ' . $apiKey],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200 && $res) {
        $data = json_decode($res, true);
        if (!empty($data['success']) && isset($data['data'])) {
            $products = is_array($data['data']) ? $data['data'] : [];
        } else {
            $error = isset($data['message']) ? (string) $data['message'] : 'Failed to load products.';
        }
    } else {
        if ($res) {
            $data = is_string($res) ? json_decode($res, true) : null;
            if (is_array($data) && isset($data['message']) && (string) $data['message'] !== '') {
                $error = (string) $data['message'];
            } else {
                $error = 'HTTP ' . $code . '. Check API key and reseller status on the platform.';
            }
        } else {
            // Do not expose raw cURL messages (timeouts, SSL, etc.) to the storefront.
            $error = 'Something went wrong. Please try again.';
        }
    }
    return ['products' => $products, 'error' => $error];
}
