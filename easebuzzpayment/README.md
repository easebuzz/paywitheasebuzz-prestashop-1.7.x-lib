# Easebuzz Payment Gateway Module for PrestaShop 1.7

**Version**: 1.0.0  
**Compatibility**: PrestaShop 1.7.x  

## Introduction

The Easebuzz Payment Gateway module allows you to integrate Easebuzz’s secure payment gateway into your PrestaShop 1.7 store. It enables multiple payment methods such as credit cards, debit cards, net banking, UPI, and wallets for a seamless checkout experience.

## Features

- Secure and reliable payment processing via Easebuzz.
- Multiple payment methods supported (credit/debit cards, net banking, UPI, wallets, etc.).
- Easy configuration in the PrestaShop admin panel.
- Supports both test (sandbox) and live environments.
- View and manage transactions from the PrestaShop back office.
- Debug mode for troubleshooting payment issues.
- Responsive and mobile-friendly payment process.

## Requirements

- PrestaShop version 1.7.x.
- An active Easebuzz merchant account.

## Installation

1. **Download the Module**  
   Download the Easebuzz Payment Gateway module as a ZIP file from the official Easebuzz website or marketplace.

2. **Upload the Module**  
   - Log in to the PrestaShop back office.
   - Navigate to `Modules > Module Manager`.
   - Click the **Upload a module** button.
   - Select the downloaded ZIP file and upload it.

3. **Install the Module**  
   - Once uploaded, locate the module in the module list.
   - Click **Install** to install the Easebuzz Payment Gateway.

4. **Configure the Module**  
   - After installation, click **Configure**.
   - Enter your Easebuzz API credentials (API Key, Salt, and Environment).
   - Enable **Save logs** for logging request/response details if needed.
   - Save the configuration.

## Configuration

| Field Name        | Description                                                                 |
|-------------------|-----------------------------------------------------------------------------|
| **API Key**        | The API Key from your Easebuzz merchant account.                            |
| **Salt**           | The Salt Key from your Easebuzz account for encryption.                     |
| **Environment**    | Select `Sandbox` or `Live` depending on your store environment.                |
| **Save logs**     | Enable logging of request/response data for troubleshooting purposes.        |

## Usage

Once configured, the Easebuzz Payment Gateway will appear as a payment option during checkout. Customers selecting Easebuzz will be redirected to the secure payment page to complete the transaction. Upon completion, they will be redirected back to your store's **Success URL** or **Failure URL**.

You can view the payment status in the **Orders** section of the PrestaShop back office.

## Troubleshooting

1. **Invalid API Key or Salt**: Double-check the credentials entered in the module configuration.
2. **Payment Errors**: Enable **Debug Mode** to capture logs for request/response details and investigate further.

## Uninstallation

To uninstall the Easebuzz Payment Gateway module:

1. Go to `Modules > Module Manager` in the PrestaShop back office.
2. Locate the Easebuzz Payment Gateway module.
3. Click **Uninstall** to remove it from your store.

## Support

For any issues or inquiries, please contact Easebuzz support via their website:  
[https://easebuzz.in/](https://easebuzz.in/)

## Changelog

### Version 1.0.0
- Initial release of the Easebuzz Payment Gateway module for PrestaShop 1.7.
