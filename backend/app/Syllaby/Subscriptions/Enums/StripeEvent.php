<?php

namespace App\Syllaby\Subscriptions\Enums;

enum StripeEvent: string
{
    case CUSTOMER_SUBSCRIPTION_CREATED = 'customer.subscription.created';
    case CUSTOMER_SUBSCRIPTION_UPDATED = 'customer.subscription.updated';
    case CUSTOMER_SUBSCRIPTION_RESUMED = 'customer.subscription.resumed';
    case CUSTOMER_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';
    case CUSTOMER_SUBSCRIPTION_TRIAL_WILL_END = 'customer.subscription.trial_will_end';
    case CUSTOMER_UPDATED = 'customer.updated';
    case CUSTOMER_DELETED = 'customer.deleted';
    case INVOICE_PAYMENT_SUCCEEDED = 'invoice.payment_succeeded';
    case INVOICE_PAYMENT_FAILED = 'invoice.payment_failed';
    case INVOICE_PAYMENT_ACTION_REQUIRED = 'invoice.payment_action_required';
    case CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';
    case PAYMENT_METHOD_ATTACHED = 'payment_method.attached';
}
