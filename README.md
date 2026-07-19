# DPD Trust YouCan Webhook Integration

A lightweight PHP script to capture YouCan.shop order placement webhooks, verify signatures, map the order fields, and forward Cash-on-Delivery (COD) telemetry to the DPD Trust API.

## Installation
1. Upload the `webhook.php` script to a public folder on your web server.
2. Ensure the PHP cURL extension is enabled.

## Configuration
Open `webhook.php` and configure:
* Set `DPD_API_KEY` to your store's DPD API key.
* Set `YOUCAN_WEBHOOK_SECRET` to your YouCan.shop webhook shared secret (leave blank to skip signature checks, though signature verification is recommended).

## Setup on YouCan.shop
1. Log in to your seller dashboard at YouCan.shop.
2. Go to **Settings** -> **Webhooks**.
3. Add a new webhook for **Order Create** (`order.create`).
4. Set the webhook URL to the public address of your uploaded script: e.g., `https://yourdomain.com/webhook.php`.
