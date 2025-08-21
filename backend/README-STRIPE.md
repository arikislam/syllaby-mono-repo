Steps to configure Stripe Pricing Table, Stripe Customer Portal to create and manage subscriptions.

Configuration steps:

1.  Create a stripe account
2. Go to [API Keys](https://dashboard.stripe.com/test/apikeys) and copy 
    *   **Publishable key** set it as `STRIPE_KEY` in .env file
    *   **Secret key** set it as `STRIPE_SECRET` in .env file
3.  Create a [Plan](https://dashboard.stripe.com/test/products)
    -   Create one or more prices. Set it to **recurring** and enter the amount
    -   Navigate to each price:
        -   Set a proper price description. Eg: "Basic Monthly"
        -   Add two metadata entries with the keys:
            -  `full_credits` and `trial_credits` and set a different amount for each (eg: 20000 & 10000)
4.  Create a [Product](https://dashboard.stripe.com/test/products) for extra credits
    -   Create one or more prices. Set it to **one time** and enter the amount
    -   Navigate to each price:
        -   Set a proper price description. Eg: "10k Credits"
        -   Add one metadata entriy with the key:
            -  `credits` and in the value field set the amount of credits (eg: 20000 & 10000)
4.  Create a [Coupon](https://dashboard.stripe.com/test/coupons)
    *   Name & Id: `UNSUB50`
    *   Type: **Percentage Discount**
    *   Percentage off: **50%**
    *   Duration: **Multiple Months** > **3 months**
    *   Set the `UNSUB50` as the `STRIPE_UNSUB_COUPON` value in .env file
5.  Create a [Pricing Table](https://stripe.com/docs/payments/checkout/pricing-table)
    -   On each products set don't show confirmation page:
        -   Redirect to url: `<local-url>/content-ideas?subscribed=1`
    -   Special attention when configuring the customer portal to select only actuall active products(plans/prices)
    -   Set `STRIPE_PRICING_TABLE_ID` env key
6.  Configure [Customer Portal](https://dashboard.stripe.com/test/settings/billing/portal)
    -   Cancelations:
        -   Disable them (this will be taken care on our side)
    -   Subscriptions:
        -   Allow Switch Plans
        -   Prorate Subscriptions Updates: **Issue invoice at the end of billing period**
    -   Business Information:
        -   Redirect Link: `<local-url>/subscriptions`
7.  List of [webhooks](https://dashboard.stripe.com/test/webhooks):
    -   `checkout.session.completed`
    -   `customer.subscription.created`
    -   `customer.subscription.updated`
    -   `customer.subscription.deleted`
    -   `customer.updated`
    -   `customer.deleted`
    -   `invoice.payment_succeeded`
    -   `invoice.payment_action_required`
    -   `invoice.payment_failed`
    -   `payment_method.updated`
8.  Seed the database:
    - `php artisan sync:stripe`
    - `php artisan sync:credits`

**Tutorial**: https://drive.google.com/drive/folders/1f-uQV-dFw5CHDHAayOs-Diy5bpNS2Ipq
**Important**: When creating a subscription the webhooks should be enabled.
