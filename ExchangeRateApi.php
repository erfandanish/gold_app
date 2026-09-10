<?php
/**
 * services/ExchangeRateApi.php
 *
 * API 2 - Currency Exchange API (AbstractAPI Exchange Rates)
 * Docs: https://docs.abstractapi.com/api/exchange-rates
 *
 * Endpoint used:
 *   GET https://exchange-rates.abstractapi.com/v1/live?api_key=KEY&base=USD&target=MYR
 *
 * Response shape:
 *   {
 *     "base": "USD",
 *     "last_updated": 1699999999,
 *     "exchange_rates": { "MYR": 4.71, ... }
 *   }
 *
 * AbstractAPI states free-plan data is normally updated every 45-60
 * minutes, so the value returned here must be labelled as the "latest
 * available" exchange rate, not a real-time rate.
 */

/**
 * Fetch the latest available USD -> MYR exchange rate.
 *
 * @param string $apiKey AbstractAPI Exchange Rates API key.
 * @return array{provider:string, base:string, target:string, rate:float, source_field:string, updated_at:string}
 * @throws Exception
 */
function get_usd_to_myr_rate(string $apiKey): array
{
    if ($apiKey === '') {
        throw new Exception('Missing AbstractAPI key (exchange_api_key in config.php).');
    }

    $url = 'https://exchange-rates.abstractapi.com/v1/live?' . http_build_query([
        'api_key' => $apiKey,
        'base'    => 'USD',
        'target'  => 'MYR',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        // AbstractAPI (and the CDN in front of it) sometimes answers with a
        // 301 redirect (e.g. onto a canonical URL). Without this, curl
        // reports the 301 itself as a failure instead of following it.
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_USERAGENT      => 'gold-jewellery-app/1.0',
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('AbstractAPI request failed: ' . $error);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception(
            "AbstractAPI request failed with HTTP {$httpCode} (final URL: {$effectiveUrl})"
        );
    }

    $data = json_decode($body, true);

    if (!isset($data['exchange_rates']['MYR'])) {
        throw new Exception('AbstractAPI response did not contain exchange_rates.MYR.');
    }

    $rate = (float) $data['exchange_rates']['MYR'];

    $updatedAt = isset($data['last_updated'])
        ? gmdate('Y-m-d\TH:i:s\Z', (int) $data['last_updated'])
        : gmdate('Y-m-d\TH:i:s\Z');

    return [
        'provider'     => 'AbstractAPI (Exchange Rates)',
        'base'         => $data['base'] ?? 'USD',
        'target'       => 'MYR',
        'rate'         => $rate,
        'source_field' => 'exchange_rates.MYR',
        'updated_at'   => $updatedAt,
    ];
}
