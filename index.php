<?php
/**
 * index.php
 * Single entry-point page. Reads config, fetches both APIs server-side,
 * joins with the database, runs the required calculations and renders
 * one HTML table.
 *
 * Place this whole project folder inside your XAMPP htdocs directory,
 * e.g.  C:\xampp\htdocs\gold-jewellery-app\
 * then browse to  http://localhost/gold-jewellery-app/
 */

require __DIR__ . '/db/db.php';
require __DIR__ . '/services/GoldPriceApi.php';
require __DIR__ . '/services/ExchangeRateApi.php';
require __DIR__ . '/services/calculations.php';

$configFile = __DIR__ . '/config/config.php';
$error = null;
$rows = [];
$goldPrice = null;
$exchangeRate = null;
$pureGoldPricePerGramMyr = null;

//check
try {
    if (!file_exists($configFile)) {
        throw new Exception(
            'config/config.php not found. Copy config/config.example.php to config/config.php and fill in your DB and API details.'
        );
    }
    $config = require $configFile;

    // 1. Fetch both third-party APIs server-side (keys never reach the browser).
    $goldPrice    = get_gold_price_usd_per_ounce($config['gold_api_key']);
    $exchangeRate = get_usd_to_myr_rate($config['exchange_api_key']);

    // 2. Get the joined order/customer/product data from the database.
    $pdo    = get_db_connection($config);
    $orders = get_orders_with_details($pdo);

    // 3. Run the required calculations for every order.
    $pureGoldPricePerGramMyr = calc_pure_gold_price_per_gram_myr(
        $goldPrice['price_per_ounce_usd'],
        $exchangeRate['rate']
    );

    foreach ($orders as $order) {
        $calc = calc_order_value(
            $pureGoldPricePerGramMyr,
            (float) $order['product_weight_g'],
            (int) $order['quantity'],
            (int) $order['gold_purity']
        );
        $rows[] = array_merge($order, $calc);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gold Jewellery Orders - Estimated Intrinsic Gold Value</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; margin: 24px; color: #222; }
    h1 { margin-bottom: 4px; }
    .subtitle { color: #555; margin-top: 0; }
    .api-summary { background: #f6f6f6; border: 1px solid #ddd; border-radius: 6px;
                   padding: 12px 16px; margin-bottom: 20px; }
    .api-summary p { margin: 4px 0; }
    table { border-collapse: collapse; width: 100%; margin-top: 12px; }
    th, td { border: 1px solid #ccc; padding: 8px 10px; text-align: left; font-size: 14px; }
    th { background: #2c3e50; color: #fff; }
    tr:nth-child(even) { background: #fafafa; }
    .note { font-size: 12px; color: #777; margin-top: 16px; }
    .error { background: #fdecea; border: 1px solid #f5c2c0; color: #a33; padding: 12px 16px;
             border-radius: 6px; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <h1>Gold Jewellery Shop - Customer Orders</h1>
  <p class="subtitle">Estimated intrinsic gold value per order (excludes workmanship, gemstones, tax, dealer margin and other retail costs)</p>

  <?php if ($error): ?>
    <div class="error">
      <strong>Could not load live data:</strong> <?= htmlspecialchars($error) ?>
      <br>Check config/config.php has valid DB credentials and both API keys are active.
    </div>
  <?php else: ?>

    <div class="api-summary">
      <p><strong>API 1 - Gold spot price</strong> (latest available, not real-time):
        <?= number_format($goldPrice['price_per_ounce_usd'], 2) ?> USD per troy ounce
        &mdash; provider: <?= htmlspecialchars($goldPrice['provider']) ?>,
        source field: <?= htmlspecialchars($goldPrice['source_field']) ?>,
        updated: <?= htmlspecialchars($goldPrice['updated_at']) ?></p>
      <p><strong>API 2 - USD/MYR exchange rate</strong> (latest available, not real-time):
        1 USD = <?= number_format($exchangeRate['rate'], 4) ?> MYR
        &mdash; provider: <?= htmlspecialchars($exchangeRate['provider']) ?>,
        source field: <?= htmlspecialchars($exchangeRate['source_field']) ?>,
        updated: <?= htmlspecialchars($exchangeRate['updated_at']) ?></p>
      <p><strong>Calculated pure-gold price:</strong>
        RM <?= number_format($pureGoldPricePerGramMyr, 2) ?> per gram
        (= <?= number_format($goldPrice['price_per_ounce_usd'], 2) ?> USD/oz &times;
        <?= number_format($exchangeRate['rate'], 4) ?> MYR/USD &divide; 31.1035 g/oz)</p>
    </div>

    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer Name</th>
          <th>Product Name</th>
          <th>Weight (g)</th>
          <th>Purity</th>
          <th>Quantity</th>
          <th>Order Date</th>
          <th>Gold Spot (USD/oz)</th>
          <th>USD-MYR Rate</th>
          <th>Pure-Gold Price (RM/g)</th>
          <th class="right">Estimated Gold Value (RM)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['order_id']) ?></td>
            <td><?= htmlspecialchars($r['customer_name']) ?></td>
            <td><?= htmlspecialchars($r['product_name']) ?></td>
            <td><?= number_format((float) $r['product_weight_g'], 2) ?></td>
            <td><?= htmlspecialchars($r['gold_purity']) ?></td>
            <td><?= htmlspecialchars($r['quantity']) ?></td>
            <td><?= htmlspecialchars($r['order_date']) ?></td>
            <td><?= number_format($goldPrice['price_per_ounce_usd'], 2) ?></td>
            <td><?= number_format($exchangeRate['rate'], 4) ?></td>
            <td><?= number_format($pureGoldPricePerGramMyr, 2) ?></td>
            <td class="right">RM <?= number_format($r['estimated_value_myr'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <p class="note">
      Gold spot price labelled as "latest available gold spot price" (MetalpriceAPI free plan provides delayed daily data).
      Exchange rate labelled as "latest available USD-to-MYR exchange rate" (AbstractAPI free plan updates roughly every 45-60 minutes).
      Figures are estimated intrinsic gold value only and exclude workmanship charges, gemstones, tax, dealer margin and other retail costs.
    </p>
  <?php endif; ?>
</body>
</html>
