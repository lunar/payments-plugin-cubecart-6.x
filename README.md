# Lunar Online Payments for CubeCart 6.x

The software is provided “as is”, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.

## Supported CubeCart versions

*The plugin has been tested with most versions of CubeCart at every iteration. We recommend using the latest version of CubeCart, but if that is not possible for some reason, test the plugin with your CubeCart version and it would probably function properly.*

## Installation

Once you have installed CubeCart, follow these simple steps:
1. Signup at [lunar.app](https://lunar.app) (it’s free)
1. Create an account
1. Create an app key for your CubeCart website
1. Upload the `LunarPayments`, `lunar_card` and/or `lunar_mobilepay` folder to the `modules\plugins` folder from your CubeCart website. 
   * Alternatively you can upload the zip file named like "lunar_payments_x.y.z.zip" ([from the latest release](https://github.com/lunar/payments-plugin-cubecart-6.x/releases)) and unzip it into `modules\plugins` folder
1. Insert Lunar API keys, from https://lunar.app to the extension settings page you can find under the available extensions section in your admin.

## Updating settings

Under the Lunar payment method settings, you can:
   * Update the payment method name shown in the checkout
   * Add app & public keys
   * Change the capture type (Instant/Delayed)

**!!! Make sure to clear the cache after any setting update**

## How to

1. Capture
   * In Instant mode, the orders are captured automatically
   * In delayed mode you can capture an order by changing its status to `Order Complete`
2. Refund
   * To refund a *Captured* payment change his status to `Cancelled`
3. Void
   * To void an *Authorized* payment change his status to `Cancelled`

## Available features

1. Capture
   * CubeCart admin panel: full capture
   * Lunar admin panel: full/partial capture
2. Refund
   * CubeCart admin panel: full refund
   * Lunar admin panel: full/partial refund
3. Void
   * CubeCart admin panel: full void
   * Lunar admin panel: full/partial void
