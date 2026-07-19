# Changelog: DPD Trust YouCan Webhook

All notable changes to this webhook script will be documented in this file.

## [1.0.0] - 2026-07-19
### Added
* Initial release of the PHP-based YouCan order creation webhook handler.
* Implemented signature verification using `X-YouCan-Signature`.
* Added phone normalization supporting Moroccan prefix mapping.
* Integrated cURL request wrapper targeting `https://api.dpd.ma/v1/orders`.
