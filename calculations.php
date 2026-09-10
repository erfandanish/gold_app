<?php


const TROY_OUNCE_IN_GRAMS = 31.1035;

function calc_pure_gold_price_per_gram_myr(float $goldSpotUsdPerOz, float $usdToMyrRate): float
{
    return ($goldSpotUsdPerOz * $usdToMyrRate) / TROY_OUNCE_IN_GRAMS;
}


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
