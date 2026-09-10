<?php
/**
 * services/calculations.php
 *
 * Implements the required calculations exactly as specified:
 *
 *   Pure-gold price (MYR/gram) = Gold spot price (USD/troy oz) x USD-to-MYR rate / 31.1035
 *   Purity factor              = Gold purity / 1000
 *   Total weight (grams)       = Product weight x Quantity
 *   Estimated intrinsic gold value (MYR) = Pure-gold price (MYR/g) x Total weight x Purity factor
 */

const TROY_OUNCE_IN_GRAMS = 31.1035;

function calc_pure_gold_price_per_gram_myr(float $goldSpotUsdPerOz, float $usdToMyrRate): float
{
    return ($goldSpotUsdPerOz * $usdToMyrRate) / TROY_OUNCE_IN_GRAMS;
}

/**
 * @return array{purity_factor:float, total_weight_g:float, estimated_value_myr:float}
 */
function calc_order_value(float $pureGoldPricePerGramMyr, float $weightG, int $quantity, int $purity): array
{
    $purityFactor = $purity / 1000;
    $totalWeightG = $weightG * $quantity;
    $estimatedValueMyr = $pureGoldPricePerGramMyr * $totalWeightG * $purityFactor;

    return [
        'purity_factor'       => $purityFactor,
        'total_weight_g'      => $totalWeightG,
        'estimated_value_myr' => $estimatedValueMyr,
    ];
}
