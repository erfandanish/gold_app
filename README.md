# Gold Jewellery Consumer Web APIs Integration (PHP / XAMPP)

A small PHP web app for a jewellery shop, built for XAMPP (Apache + MySQL +
PHP). It joins customer/order data from a MySQL database with live data
from two external APIs (MetalpriceAPI for the gold spot price, AbstractAPI
for the USD→MYR rate) and shows the estimated intrinsic gold value of
every order in one table.

## What's in this project

```
gold-jewellery-php/
├── db/
│   ├── schema.sql       # CREATE DATABASE + CREATE TABLE + INSERT + JOIN query (submit this)
│   └── db.php            # PDO connection + the JOIN query
├── services/
│   ├── GoldPriceApi.php     # API 1 — MetalpriceAPI (XAU/USD), uses cURL
│   ├── ExchangeRateApi.php  # API 2 — AbstractAPI (USD→MYR), uses cURL
│   └── calculations.php     # Pure-gold price & intrinsic value formulas
├── config/
│   └── config.example.php   # Placeholder DB + API keys (submit this, not config.php)
├── index.php                # The single results webpage (entry point)
└── .gitignore
```

## 1. Install into XAMPP

1. Copy the whole `gold-jewellery-php` folder into your XAMPP `htdocs`
   directory, e.g. `C:\xampp\htdocs\gold-jewellery-app\` (Windows) or
   `/Applications/XAMPP/htdocs/gold-jewellery-app/` (Mac).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

## 2. Create the database

Open **phpMyAdmin** (`http://localhost/phpmyadmin`), click the **SQL** tab,
paste the entire contents of `db/schema.sql`, and run it. This creates the
`gold_jewellery` database plus the `Customers`, `GoldProducts` and `Orders`
tables with the 3 sample rows each required by the assignment.

(Alternative, from a terminal: `mysql -u root < db/schema.sql`.)

To take the "three tables and their records" screenshots, expand
`gold_jewellery` in the left sidebar of phpMyAdmin and click **Browse** on
each table.

## 3. Configure your API keys

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` and fill in:
- Your MySQL credentials (XAMPP defaults are already filled in: host
  `localhost`, user `root`, empty password)
- `gold_api_key` — your free MetalpriceAPI key from https://metalpriceapi.com/
- `exchange_api_key` — your free AbstractAPI key from
  https://www.abstractapi.com/api/exchange-rate-api

`config/config.php` is listed in `.gitignore` and is only ever `require`d
from server-side PHP — it is never sent to the browser, satisfying the
assignment's security requirement.

## 4. Run it

With Apache running, browse to:

```
http://localhost/gold-jewellery-app/
```

The page will:
1. Call MetalpriceAPI and AbstractAPI server-side via cURL.
2. Read the joined Orders/Customers/GoldProducts data from MySQL via PDO.
3. Calculate the pure-gold price per gram and each order's estimated
   intrinsic gold value.
4. Render everything in one HTML table.

If a key or DB credential is wrong, the page shows a red error box
instead of a fatal error — useful for debugging before your screenshot.

## 5. Evidence checklist (maps to the assignment sections)

| Assignment ask | Where to find it |
|---|---|
| CREATE TABLE statements | `db/schema.sql` (section 1) |
| INSERT statements | `db/schema.sql` (section 2) |
| Screenshots of the 3 tables + records | phpMyAdmin → `gold_jewellery` → each table → Browse |
| Server-side request + JSON-parsing code for both APIs | `services/GoldPriceApi.php`, `services/ExchangeRateApi.php` — copy-paste as selectable text |
| One successful response screenshot per API | Temporarily add `var_dump($data);` after the `json_decode(...)` line in each service file, reload the page, then **black out the `api_key` value** before screenshotting |
| Example config file with placeholders only | `config/config.example.php` |
| SQL JOIN query | Bottom of `db/schema.sql`, also in `db/db.php` |
| Screenshot of JOIN output | phpMyAdmin → SQL tab → paste the JOIN query → Go |
| Final webpage screenshot | `http://localhost/gold-jewellery-app/` after setup |

## Notes on the calculations

```
Pure-gold price (MYR/gram) = Gold spot price (USD/troy oz) × USD-to-MYR rate ÷ 31.1035
Purity factor               = Gold purity ÷ 1000
Total weight (grams)        = Product weight × Quantity
Estimated intrinsic value   = Pure-gold price (MYR/g) × Total weight × Purity factor
```

Implemented in `services/calculations.php` and verified against the
assignment's worked example (RM450.00/g × 10g × 0.916 = RM4,122.00).

## Notes on the two APIs

- **MetalpriceAPI** (`services/GoldPriceApi.php`) calls
  `GET /v1/latest?base=USD&currencies=XAU` and reads the ready-made
  `rates.USDXAU` field (USD price per troy ounce), falling back to
  `1 / rates.XAU` if that field is ever absent. The free plan returns
  delayed daily data, so the page labels this "latest available", not
  real-time.
- **AbstractAPI Exchange Rates** (`services/ExchangeRateApi.php`) calls
  `GET /v1/live?base=USD&target=MYR` and reads `exchange_rates.MYR`. The
  free plan updates roughly every 45–60 minutes, so the page labels this
  "latest available" too.

## Troubleshooting

- **"could not find driver" / PDO error** — enable the `pdo_mysql`
  extension in `php.ini` (XAMPP ships it; it's usually already on).
- **cURL errors on Windows** — make sure `extension=curl` is uncommented
  in `php.ini` (XAMPP ships it enabled by default) and that your machine
  has internet access / isn't blocked by a firewall.
- **"config/config.php not found"** — you skipped step 3; copy
  `config.example.php` to `config.php`.
