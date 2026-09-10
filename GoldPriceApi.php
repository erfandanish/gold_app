<?php
/**
 * services/GoldPriceApi.php
 *
 * API 1 - Gold Price API (MetalpriceAPI)
 * Docs: https://metalpriceapi.com/documentation
 *
 * Endpoint used:
 *   GET https://api.metalpriceapi.com/v1/latest?api_key=KEY&base=USD&currencies=XAU
 *
 * MetalpriceAPI's "latest" endpoint returns rates expressed as
 * "1 unit of base = X units of target". With base=USD and currencies=XAU,
 * rates.XAU is a fraction of a troy ounce per 1 USD (e.g. 0.00053853).
 * The API also returns a ready-made "USDXAU" field which is already the
 * gold price in USD per troy ounce (1 / rates.XAU), so that field is used
 * directly and we fall back to the manual inversion if it is ever missing.
 *
 * The MetalpriceAPI free plan provides delayed daily data, so the value
 * returned here must be described as the "latest available" price, not
 * a real-time price.
 */

/**
 * Fetch the latest available XAU/USD gold spot price.
 *
 * @param string $apiKey MetalpriceAPI API key.
 * @return array{provider:string, price_per_ounce_usd:float, source_field:string, unit:string, updated_at:string}
 * @throws Exception
 */
function get_gold_price_usd_per_ounce(string $apiKey): array
{
    if ($apiKey === '') {
        throw new Exception('Missing MetalpriceAPI key (gold_api_key in config.php).');
    }

    $url = 'https://api.metalpriceapi.com/v1/latest?' . http_build_query([
        'api_key'    => $apiKey,
        'base'       => 'USD',
        'currencies' => 'XAU',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_USERAGENT      => 'gold-jewellery-app/1.0',
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('MetalpriceAPI request failed: ' . $error);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception(
            "MetalpriceAPI request failed with HTTP {$httpCode} (final URL: {$effectiveUrl})"
        );
    }

    $data = json_decode($body, true);

    if (empty($data['success'])) {
        $message = $data['error']['message'] ?? 'Unknown MetalpriceAPI error';
        throw new Exception('MetalpriceAPI error: ' . $message);
    }

    // Prefer the ready-made "USDXAU" field (USD price per ounce of gold).
    // Fall back to inverting rates.XAU (ounces of gold per 1 USD) if needed.
    if (isset($data['rates']['USDXAU'])) {
        $pricePerOunceUsd = (float) $data['rates']['USDXAU'];
        $sourceField = 'rates.USDXAU';
    } elseif (isset($data['rates']['XAU'])) {
        $pricePerOunceUsd = 1 / (float) $data['rates']['XAU'];
        $sourceField = 'rates.XAU (inverted: 1 / rates.XAU)';
    } else {
        throw new Exception('MetalpriceAPI response did not contain an XAU rate.');
    }

    $updatedAt = isset($data['timestamp'])
        ? gmdate('Y-m-d\TH:i:s\Z', (int) $data['timestamp'])
        : gmdate('Y-m-d\TH:i:s\Z');

    return [
        'provider'             => 'MetalpriceAPI',
        'price_per_ounce_usd'  => $pricePerOunceUsd,
        'source_field'         => $sourceField,
        'unit'                 => 'USD per troy ounce (XAU)',
        'updated_at'           => $updatedAt,
    ];
}
