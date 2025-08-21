# JVZoo Integration & User Onboarding - TODO

## Prerequisites & Dependencies

### 1. JVZoo Account Setup ✓
- [x] JVZoo merchant account exists
- [ ] Configure JVZoo webhook/IPN endpoint URLs in JVZoo dashboard
- [ ] Document JVZoo transaction ID format and expected payload structure
- [ ] Test JVZoo IPN payload structure and validation requirements

### 2. Environment Configuration  
- [ ] Add JVZoo webhook URL configuration to `config/services.php`:
  ```php
  'jvzoo' => [
      'webhook_url' => env('APP_URL') . '/jvzoo/webhook',
  ],
  ```

## Core Implementation Tasks

### 3. Database Schema Changes

#### 3.1 Add JVZoo Support to Subscription Provider Enum ✅
- [x] **File:** `app/Syllaby/Subscriptions/Enums/SubscriptionProvider.php`
  - Added `case JVZOO = 4;` 
  - Updated `label()` method to include 'JVZoo'
  - Added `SOURCE_JVZOO = 'jvzoo'` constant
  - Updated `source()` and `allSources()` methods

#### 3.2 Create JVZoo-Specific Tables Following Cashier Pattern

##### JVZoo Plans Table ✅
- [x] **Migration:** Create `jvzoo_plans` table:
  - `id` (primary key)
  - `plan_id` (foreign key to `plans` table)
  - `jvzoo_id` (JVZoo product identifier)
  - `name` (JVZoo product name)
  - `price` (product price in cents)
  - `currency` (default USD)
  - `interval` (billing interval: month, year, one_time, product)
  - `metadata` (JSON - additional JVZoo data including credits)
  - `is_active` (boolean)
  - `timestamps`

- [x] **Model:** `app/Syllaby/Subscriptions/JVZooPlan.php`

##### JVZoo Subscriptions Table ✅
- [x] **Migration:** Create `jvzoo_subscriptions` table:
  - `id` (primary key)
  - `user_id` (foreign key to users)
  - `jvzoo_plan_id` (foreign key to jvzoo_plans)
  - `receipt` (from JVZoo - renamed from receipt_id)
  - `status` (active, canceled, etc.)
  - `started_at` (subscription start date)
  - `ends_at` (nullable - for one-time purchases)
  - `trial_ends_at` (nullable)
  - `metadata` (JSON - JVZoo specific data)
  - `timestamps`

- [x] **Model:** `app/Syllaby/Subscriptions/JVZooSubscription.php`
  - Updated: Removed `transaction_id` field references
  - Updated: Changed `findByTransactionId()` to `findByReceipt()`

##### JVZoo Subscription Items Table ✅
- [x] **Migration:** Create `jvzoo_subscription_items` table:
  - `id` (primary key)
  - `jvzoo_subscription_id` (foreign key)
  - `jvzoo_plan_id` (foreign key)
  - `quantity` (default 1)
  - Unique constraint to prevent duplicates
  - `timestamps`

- [x] **Model:** `app/Syllaby/Subscriptions/JVZooSubscriptionItem.php`

##### JVZoo Transactions Table (for tracking/onboarding) ✅
- [x] **Migration:** Create `jvzoo_transactions` table:
  - `id` (primary key)
  - `user_id` (nullable, FK to users)
  - `jvzoo_subscription_id` (nullable, FK to jvzoo_subscriptions)
  - **Core JVZoo fields (from webhook payload):**
    - `receipt` (unique, maps to ctransreceipt)
    - `product_id` (maps to cproditem)
    - `product_name` (nullable, maps to cprodtitle)
    - `product_type` (nullable, maps to cprodtype - STANDARD/RECURRING)
    - `transaction_type` (maps to ctransaction)
    - `amount` (integer, maps to ctransamount in pennies)
    - `payment_method` (nullable, maps to ctranspaymentmethod)
    - `vendor` (nullable, maps to ctransvendor)
    - `affiliate` (nullable, maps to ctransaffiliate)
    - `customer_email` (maps to ccustemail)
    - `customer_name` (nullable, maps to ccustname)
    - `customer_state` (nullable, maps to ccuststate)
    - `customer_country` (nullable, maps to ccustcc)
    - `upsell_receipt` (nullable, maps to cupsellreceipt)
    - `affiliate_tracking_id` (nullable, maps to caffitid)
    - `vendor_through` (nullable, maps to cvendthru)
    - `verification_hash` (nullable, maps to cverify)
  - **Internal processing fields:**
    - `status` (using JVZooPaymentStatus enum, default PENDING)
    - `verified_at` (set when webhook signature is verified)
    - `onboarding_token` (64 char unique token)
    - `onboarding_expires_at`, `onboarding_completed_at` (onboarding tracking)
    - `referral_metadata` (JSON for UTM/referral data)
    - `payload` (JSON for full IPN data)
  - `timestamps`

- [x] **Model:** `app/Syllaby/Subscriptions/JVZooTransaction.php`
- [x] **Enums:** `JVZooTransactionType.php`, `JVZooPaymentStatus.php`

### 4. Universal Subscription Management (Cashier-like Pattern) ✅ COMPLETED

#### 4.1 Subscription Manager Interface ✅
- [x] **File:** `app/Syllaby/Subscriptions/Contracts/BillableContract.php`
  - **Enhanced Interface:** Added all helper methods from Cashier ManagesSubscriptions trait
  - **Methods Added:** `onTrial()`, `hasExpiredTrial()`, `onGenericTrial()`, `hasExpiredGenericTrial()`, `trialEndsAt()`, `hasIncompletePayment()`, `subscribedToPrice()`, `onPrice()`
  - **Type Safety:** All methods have proper type hints for parameters and return values
  - **Provider Agnostic:** Interface works consistently across all subscription providers

#### 4.2 Subscription Contract Interface ✅
- [x] **File:** `app/Syllaby/Subscriptions/Contracts/SubscriptionContract.php`
  - **Complete Interface:** All methods required by subscription models with proper type hints
  - **Core methods:** `active()`, `onTrial()`, `canceled()`, `expired()`, `onGracePeriod()`, `valid()`, `cancel()`, `trialEndsAt()`, `endsAt()`, `planId()`, `hasPrice()`
  - **Additional state methods:** `incomplete()`, `pastDue()`, `ended()`, `hasExpiredTrial()`, `recurring()`
  - **Query scopes:** All 11 scope methods with proper Builder type hints
  - **Relationships:** `user()`, `owner()`, `items()`, `findItemOrFail()` with proper return types

#### 4.3 Subscription Manager Implementations ✅
- [x] **File:** `app/Syllaby/Subscriptions/Managers/StripeSubscriptionManager.php`
  - **Cashier Integration:** Uses Laravel Cashier's ManagesSubscriptions trait directly
  - **Clean Implementation:** Only overrides interface-specific methods, delegates rest to Cashier
  - **Type Safety:** Implements BillableContract with proper type hints
  
- [x] **File:** `app/Syllaby/Subscriptions/Managers/GooglePlaySubscriptionManager.php`
  - **Complete Implementation:** All BillableContract methods implemented for Google Play
  - **Provider-Specific Logic:** Google Play-specific subscription handling
  - **Type Safety:** Proper type hints matching interface requirements
  
- [x] **File:** `app/Syllaby/Subscriptions/Managers/JVZooSubscriptionManager.php`
  - **Complete Implementation:** All BillableContract methods implemented for JVZoo
  - **Provider-Specific Logic:** JVZoo-specific subscription handling
  - **Type Safety:** Proper type hints matching interface requirements

#### 4.4 Universal Subscription Trait ✅
- [x] **File:** `app/Syllaby/Subscriptions/Traits/ManagesSubscriptions.php`
  - **Clean Delegation:** All methods delegate to appropriate subscription manager
  - **Provider Routing:** Automatically routes to correct manager based on user's subscription_provider
  - **Cashier Integration:** Uses Laravel Cashier's ManagesSubscriptions trait for maximum compatibility
  - **Type Safety:** All methods have proper type hints matching the interface

#### 4.5 Custom Billable Trait ✅
- [x] **File:** `app/Syllaby/Subscriptions/Traits/Billable.php`
  - **Cashier Compatibility:** Imports all Cashier traits except ManagesSubscriptions
  - **Custom Implementation:** Uses our custom ManagesSubscriptions trait instead
  - **Provider Agnostic:** Works with Stripe, Google Play, and JVZoo subscriptions

#### 4.6 Update Subscription Models ✅
- [x] **File:** `app/Syllaby/Subscriptions/Subscription.php` (Custom Stripe model)
  - Extends Laravel Cashier Subscription, implements SubscriptionContract interface
  - Added explicit method overrides with proper return types for interface compatibility
  - Added modern `status()` Attribute accessor to normalize `stripe_status` as `status`
  - Configured via AppServiceProvider

- [x] **File:** `app/Syllaby/Subscriptions/JVZooSubscription.php`
  - **Complete SubscriptionContract Implementation:** All interface methods implemented with proper type hints
  - **Core methods:** `user()`, `owner()`, `items()`, `findItemOrFail()`, `active()`, `onTrial()`, `canceled()`, `expired()`, `onGracePeriod()`, `valid()`, `cancel()`, `trialEndsAt()`, `endsAt()`, `planId()`, `hasPrice()`
  - **State methods:** `incomplete()`, `pastDue()`, `ended()`, `hasExpiredTrial()`, `recurring()`, `hasIncompletePayment()`
  - **Query scopes:** All 11 scope methods with proper Builder type hints
  - **Type Safety:** All methods have proper parameter and return type hints

- [x] **File:** `app/Syllaby/Subscriptions/GooglePlaySubscription.php**  
  - **Cleaned Implementation:** Removed unnecessary compatibility methods and duplicates
  - **Complete SubscriptionContract Implementation:** All interface methods implemented with proper type hints
  - **Core methods:** `user()`, `owner()`, `items()`, `findItemOrFail()`, `active()`, `canceled()`, `onTrial()`, `onGracePeriod()`, `recurring()`, `incomplete()`, `pastDue()`, `ended()`, `hasExpiredTrial()`, `valid()`, `cancel()`, `trialEndsAt()`, `endsAt()`, `planId()`, `hasPrice()`, `hasIncompletePayment()`
  - **Query scopes:** All 11 scope methods with proper Builder type hints
  - **Streamlined Code:** Removed compatibility methods like `getStripeStatusAttribute()`, `cancelNow()`, `updateStripeSubscription()`, custom query builder
  - **Type Safety:** All methods have proper parameter and return type hints

### 5. Webhook Infrastructure

#### 5.1 JVZoo Webhook Controller (With Signature Verification) ✅
- [x] **File:** `app/Http/Controllers/Webhooks/JVZooWebhookController.php`
  - Handle IPN/webhook receipt with official JVZoo signature verification (cverify)
  - Validate payload structure and required fields
  - Parse purchase data using correct field mappings from JVZoo documentation
  - Create/update `JVZooTransaction` record with duplicate detection (using receipt field)
  - Dispatch events for further processing
  - Return proper response codes per JVZoo requirements
  - **Enhanced:** Moved signature validation and payload validation to middleware (`JVZooSignatureValidator`)
  - **Enhanced:** Added refund/chargeback handling via `refund()` method that routes to cancellation handler
  - **Enhanced:** Clean match statement for transaction type routing

#### 5.2 Update Routes ✅
- [x] **File:** `routes/webhooks.php`
  - Added route: `Route::post('/jvzoo/webhook', [Webhooks\JVZooWebhookController::class, 'handle'])->name('jvzoo.webhook');`

- [x] **File:** `app/Http/Middleware/VerifyCsrfToken.php`
  - Added `'jvzoo/webhook'` to `$except` array

#### 5.3 JVZoo Signature Validator Middleware ✅
- [x] **File:** `app/Http/Middleware/JVZooSignatureValidator.php`
  - Validates JVZoo webhook signatures using official cverify method
  - Validates required payload fields (transaction type, email format, amount validation)
  - Registered in Kernel.php as 'verify.jvzoo'
  - Applied to JVZooWebhookController via constructor
  - SHA1-based signature verification following JVZoo's official documentation
  - Comprehensive payload validation before processing

### 6. Domain Logic & Actions

#### 6.1 JVZoo Transaction Processing Action ✅
- [x] **File:** `app/Syllaby/Subscriptions/Actions/ProcessJVZooTransactionAction.php`
  - Validate transaction data integrity
  - Check for duplicate transactions
  - Map JVZoo products to Syllaby plans (auto-creates plans)
  - Extract and store referral data (JVZoo vendor, third-party, IP address)
  - Generate secure onboarding token (UUID, 48h expiration)
  - Process transaction status mapping
  - **Enhanced:** Set interval from Stripe plan type when creating JVZoo plans
  - **Enhanced:** Handle zero-amount transactions with fallback to plan price
  - **Enhanced:** Include credit metadata (full_credits, trial_credits) in JVZoo plan creation
  - **Simplified:** Streamlined referral data extraction to focus on JVZoo-specific fields

#### 6.2 JVZoo Account Activation Action ✅
- [x] **File:** `app/Syllaby/Auth/Actions/JVZooActivationAction.php`
  - **Architecture:** Clean action pattern following PasswordRecoveryController approach
  - **Validation:** Token + email double verification with purchase record
  - **Security:** Expiration checking, one-time use with cleanup
  - **Password handling:** User model mutator handles hashing automatically
  - **Token cleanup:** Clears onboarding token and expiration after use
  - **Form request:** Uses dedicated JVZooActivationRequest for validation
  - **Simple response:** Returns success message (no user data or tokens)

### 7. Event System

#### 7.1 JVZoo Events
- [x] **File:** `app/Syllaby/Subscriptions/Events/JVZooPurchaseReceived.php`
- [ ] **File:** `app/Syllaby/Subscriptions/Events/JVZooPurchaseVerified.php`  
- [ ] **File:** `app/Syllaby/Subscriptions/Events/JVZooSubscriptionCreated.php`

#### 7.2 Event Listeners
- [ ] **File:** `app/Syllaby/Subscriptions/Listeners/ProcessJVZooPurchaseListener.php`
  - Handle purchase verification and processing
  
- [ ] **File:** `app/Syllaby/Subscriptions/Listeners/JVZooSetSubscriptionCreditsListener.php`
  - Set user credits based on JVZoo purchase
  - Follow existing credit system patterns

### 8. User Interface & API Endpoints

#### 8.1 JVZoo Activation Controller ✅
- [x] **File:** `app/Http/Controllers/Api/v1/Authentication/JVZooActivationController.php`
  - `POST /v1/authentication/jvzoo/activation` - Complete JVZoo account activation with password
  - **RESTful naming:** Uses `/activation` noun following REST conventions
  - **Clean architecture:** Action pattern with proper middleware and validation
  - **Proper location:** Placed alongside other authentication controllers
  - **API-only design:** No GET endpoint needed (SPA frontend handles UI)
  - **Payload-based:** Token sent in POST body, not URL parameter
  - **Features:** Token validation, password setup, security cleanup (no auto-login)

#### 8.2 Request Validation ✅
- [x] **Form Request:** `app/Http/Requests/Authentication/JVZooActivationRequest.php`
  - **Clean validation:** Dedicated form request following Laravel best practices
  - Token validation (`required|string`)
  - Email validation (`required|email`) - double verification with purchase record
  - Password validation using `Utility::setPasswordRules()` with confirmation
  - **Removed:** Name field (simplified as requested)
  - **Pattern:** Follows same approach as other authentication requests

#### 8.3 API Routes ✅
- [x] **File:** `routes/api-v1.php`
  - **RESTful route:** `POST /v1/authentication/jvzoo/activation`
  - **Proper naming:** Uses noun (`/activation`) following REST conventions
  - **Middleware:** Protected with `guest` middleware
  - Grouped with other authentication endpoints (login, register, logout)
  - **Clean API design:** Follows authentication endpoint conventions

### 9. Email Templates & Notifications

#### 9.1 Onboarding Email
- [ ] **File:** `app/Syllaby/Subscriptions/Mails/JVZooOnboardingEmail.php`
  - Welcome message for JVZoo customers
  - Secure onboarding link with token
  - Instructions for password setup
  - Account activation timeline

#### 9.2 Email View Template
- [ ] **File:** `resources/views/emails/jvzoo-onboarding.blade.php`
  - Professional email design consistent with brand
  - Clear call-to-action for account setup
  - Support information and next steps

### 10. Validation & Security

#### 10.1 Token Security
- [ ] Implement secure token generation (random, time-limited)
- [ ] Add token expiration (recommend 24-48 hours)
- [ ] Prevent token reuse after successful onboarding
- [ ] Add rate limiting for onboarding attempts

#### 10.2 Duplicate Prevention
- [ ] Ensure unique JVZoo transaction IDs
- [ ] Prevent duplicate user accounts for same transaction
- [ ] Handle edge cases (failed onboarding, expired tokens)

#### 10.3 Data Validation
- [ ] Validate all JVZoo payload data
- [ ] Sanitize user inputs during onboarding
- [ ] Implement proper error handling and logging

### 11. Integration Testing

#### 11.1 Unit Tests
- [ ] **File:** `tests/Unit/Subscriptions/JVZooSubscriptionManagerTest.php`
- [ ] **File:** `tests/Unit/Actions/ProcessJVZooPurchaseActionTest.php`
- [ ] **File:** `tests/Unit/Actions/JVZooOnboardingActionTest.php`

#### 11.2 Feature Tests  
- [x] **File:** `tests/Feature/Subscriptions/JVZoo/JVZooWebhookControllerTest.php` ✅ COMPLETED
  - ✅ Comprehensive test coverage for all webhook scenarios (25 tests total)
  - ✅ Test scenarios include:
    - Invalid transaction type rejection
    - Duplicate transaction handling
    - New user creation and onboarding
    - Existing user cancellation
    - Payment failure and recovery
    - Trial-to-paid conversion with same receipt ID
    - Plan upgrades/downgrades with different receipt IDs
    - Full and partial refunds
    - Chargebacks
    - Subscription renewals
    - Multiple payment attempts
    - Orphaned transactions
    - Out-of-order transaction handling
    - **NEW: Subscription renewal credits allocation**
    - **NEW: Trial credits allocation when user starts trial**
    - **NEW: User can only have one active subscription at a time**
    - **NEW: User can only have one trial subscription at a time**
    - **NEW: User can only have one suspended subscription at a time**
    - **NEW: User can create new subscription if previous is canceled**
    - **NEW: User can create new subscription if no previous exists**
  - ✅ **Test Helper:** `payload()` function properly maps factory attributes to webhook field names
  - ✅ **Middleware:** Tests exclude JVZooSignatureValidator middleware for isolated testing
  - ✅ **Credit Testing Requirements:**
    - Must seed `CreditEventTableSeeder` in `beforeEach`
    - JVZoo plans must include `metadata` with `trial_credits` and `full_credits`
    - Credit history assertions use `description` field (not `event_id`)
    - Trial credits use `CreditEventEnum::SUBSCRIBE_TO_TRIAL`
    - Renewal credits use `CreditEventEnum::SUBSCRIPTION_AMOUNT_PAID`
- [ ] **File:** `tests/Feature/JVZoo/OnboardingFlowTest.php`
- [ ] **File:** `tests/Feature/JVZoo/SubscriptionManagerTest.php`

#### 11.3 Test Data & Factories ✅ COMPLETED
- [x] **File:** `database/factories/JVZooTransactionFactory.php`
- [x] **File:** `database/factories/JVZooSubscriptionFactory.php`
- [x] **File:** `database/factories/JVZooPlanFactory.php`
- [x] **Test Helper:** Webhook payload generator function in tests

### 12. Configuration & Documentation

#### 12.1 Service Configuration
- [ ] **File:** `config/jvzoo.php` (new configuration file)
  - Webhook endpoints
  - Product mapping
  - Onboarding settings

#### 12.2 Plan Mapping Configuration
- [ ] Map JVZoo product IDs to Syllaby plan IDs
- [ ] Configure credit amounts for each plan
- [ ] Set up trial vs paid plan logic

---

## Implementation Phases

### Phase 1: Database Schema & Models ✅ COMPLETED
**Tasks:** 3.1, 3.2 (all database changes and basic models)
**Review Point:** Database structure and model relationships
**Status:** ✅ COMPLETED
**Files Created:**
- ✅ Migration: `2025_07_14_110903_create_jvzoo_plans_table.php`
- ✅ Migration: `2025_07_14_111124_create_jvzoo_subscriptions_table.php`
- ✅ Migration: `2025_07_14_111230_create_jvzoo_subscription_items_table.php`
- ✅ Migration: `2025_07_14_111313_create_jvzoo_transactions_table.php`
- ✅ Model: `JVZooPlan.php` with relationships and helper methods
- ✅ Model: `JVZooSubscription.php` with status management
- ✅ Model: `JVZooSubscriptionItem.php` with quantity tracking
- ✅ Model: `JVZooTransaction.php` with IPN validation and onboarding support
- ✅ Enum: `JVZooTransactionType.php` with business logic methods
- ✅ Enum: `JVZooPaymentStatus.php` with status validation
- ✅ Updated: `SubscriptionProvider.php` enum with JVZOO support

### Phase 2: Universal Subscription Management ✅ COMPLETED
**Tasks:** 4.1, 4.2, 4.3, 4.4, 4.5, 4.6 (Cashier-like abstraction)
**Review Point:** Universal subscription interface working with existing providers
**Status:** ✅ COMPLETED

#### Phase 2.1: Enhanced Interface & Implementation ✅ COMPLETED
**Complete overhaul of subscription management system with proper interfaces and implementations:**

**Key Achievements:**
- ✅ **BillableContract Enhancement:** Added all helper methods from Cashier's ManagesSubscriptions trait
- ✅ **SubscriptionContract Enhancement:** Complete interface with all required methods and proper type hints
- ✅ **Manager Pattern:** All three managers (Stripe, Google Play, JVZoo) implement complete BillableContract
- ✅ **Stripe Integration:** StripeSubscriptionManager uses Cashier's ManagesSubscriptions trait directly
- ✅ **Code Cleanup:** GooglePlaySubscription cleaned up, removed unnecessary compatibility methods
- ✅ **Type Safety:** All interfaces and implementations have proper type hints
- ✅ **Provider Agnostic:** User can call any subscription method regardless of provider

**Files Enhanced:**
- ✅ Contract: `BillableContract.php` - Added helper methods with type safety
- ✅ Contract: `SubscriptionContract.php` - Complete interface with all scope methods
- ✅ Manager: `StripeSubscriptionManager.php` - Uses Cashier trait directly, minimal custom code
- ✅ Manager: `GooglePlaySubscriptionManager.php` - Complete implementation
- ✅ Manager: `JVZooSubscriptionManager.php` - Complete implementation
- ✅ Trait: `ManagesSubscriptions.php` - Clean delegation to managers
- ✅ Trait: `Billable.php` - Custom trait that uses our ManagesSubscriptions
- ✅ Model: `JVZooSubscription.php` - Complete SubscriptionContract implementation
- ✅ Model: `GooglePlaySubscription.php` - Cleaned up and streamlined implementation

**Interface Methods Implemented Across All Providers:**
- **Relationship Methods:** `user()`, `owner()`, `items()`, `findItemOrFail()`
- **State Methods:** `active()`, `canceled()`, `onTrial()`, `onGracePeriod()`, `recurring()`, `incomplete()`, `pastDue()`, `ended()`, `hasExpiredTrial()`, `valid()`, `hasIncompletePayment()`
- **Data Methods:** `trialEndsAt()`, `endsAt()`, `planId()`, `hasPrice()`, `cancel()`
- **Query Scopes:** 11 scope methods with proper Builder type hints
- **Manager Helper Methods:** `subscription()`, `subscriptions()`, `subscribed()`, `onTrial()`, `hasExpiredTrial()`, `onGenericTrial()`, `hasExpiredGenericTrial()`, `trialEndsAt()`, `hasIncompletePayment()`, `subscribedToPrice()`, `onPrice()`, `scopeOnGenericTrial()`

#### Phase 2.2: Cashier-Compatible Helper Methods ✅ COMPLETED
**Complete implementation of Laravel Cashier helper methods across all providers:**

**Key Achievement:** Successfully implemented provider-specific helper methods that adapt to each provider's unique database structure and business logic, rather than forcing Cashier patterns where they don't make sense.

**Manager Implementation Strategy (Final):**
- **StripeSubscriptionManager:** Direct implementation using Cashier logic patterns to avoid infinite recursion and maintain upgrade compatibility
- **JVZooSubscriptionManager:** Adapted implementation for JVZoo's structure - no subscription types, uses jvzoo_plan_id relationships, simplified trial logic
- **GooglePlaySubscriptionManager:** Adapted implementation for Google Play's structure - uses product_id instead of types, leverages trial_start_at/trial_end_at fields, payment_state for incomplete payments

**Architecture Decisions:**
- **Avoided Complex Proxy Patterns:** Chose direct implementation over hacky proxy solutions for maintainability and clarity
- **Minimal Code Duplication:** StripeSubscriptionManager replicates some Cashier logic to prevent infinite recursion with custom traits
- **Upgrade Strategy:** Code duplication is minimal and focused - manageable for future Cashier updates through testing and changelog review
- **Clean Separation:** Each manager is self-contained, debuggable, and optimized for its specific provider
- **No Scope Methods in Managers:** Scopes belong on models - removed scopeOnGenericTrial from BillableContract interface

**Helper Methods Implemented in All Managers (Adapted Per Provider):**
- `onTrial(string $type = 'default', ?string $price = null): bool` - Check trial status (Stripe: uses type, JVZoo: ignores type, Google Play: maps type to product_id)
- `hasExpiredTrial(string $type = 'default', ?string $price = null): bool` - Check expired trial status (adapted per provider's trial fields)
- `onGenericTrial(): bool` - Check model-level trial status (User.trial_ends_at) - consistent across all providers
- `hasExpiredGenericTrial(): bool` - Check expired model-level trial - consistent across all providers
- `trialEndsAt(string $type = 'default'): ?Carbon` - Get trial end date (Stripe: subscription.trial_ends_at, Google Play: trial_end_at, JVZoo: trial_ends_at)
- `hasIncompletePayment(string $type = 'default'): bool` - Check incomplete payment (Stripe: Cashier logic, Google Play: payment_state, JVZoo: status-based)
- `subscribedToPrice($prices, string $type = 'default'): bool` - Check subscription to specific price(s) (adapted to each provider's price/plan structure)
- `onPrice(string $price): bool` - Check if user has any valid subscription for specific price (adapted to each provider's identification system)

**Usage Examples:**
```php
// All methods work identically across providers (Stripe, JVZoo, Google Play)
$user->onTrial()                                    // Check if on trial (any type)
$user->onTrial('premium')                          // Check specific subscription type trial
$user->onTrial('premium', 'price_123')             // Check trial for specific type and price
$user->hasExpiredTrial()                           // Check if trial expired
$user->onGenericTrial()                            // Check User model trial_ends_at
$user->trialEndsAt()                               // Get trial end date
$user->subscribedToPrice(['price_1', 'price_2'])   // Check multiple prices
$user->onPrice('price_123')                        // Check specific price subscription

// Query scopes available on User model
User::onGenericTrial()->get()                      // Users with active generic trials
```

### Phase 3: Webhook & Core Processing ✅ COMPLETED
**Tasks:** 5.1, 5.2, 6.1 (webhook handling and purchase processing)
**Review Point:** Webhook receiving and processing JVZoo purchases
**Status:** ✅ COMPLETED
**Files Created:**
- ✅ Controller: `JVZooWebhookController.php` - Handles JVZoo webhooks with validation and processing
- ✅ Action: `ProcessJVZooTransactionAction.php` - Processes purchase data and creates records
- ✅ Event: `JVZooPurchaseReceived.php` - Event for purchase processing
- ✅ Updated: `webhooks.php` routes - Added JVZoo webhook route
- ✅ Updated: `VerifyCsrfToken.php` middleware - Excluded JVZoo webhook from CSRF
- ✅ Updated: `JVZooTransactionType.php` - Added `isValid()` method

**Key Features Implemented:**
- Official JVZoo signature verification using cverify parameter (SHA1 hash validation)
- Webhook payload validation with proper field mapping from JVZoo documentation
- Duplicate transaction detection using transaction_receipt field
- Secure onboarding token generation (UUID-based, 48-hour expiration)
- Referral data extraction (JVZoo vendor, third-party, IP address tracking)
- Automatic JVZoo plan creation for new products with proper plan mapping
- Transaction status mapping using correct enum values
- Field mapping aligned with official JVZoo webhook parameters
- Comprehensive logging for debugging and monitoring
- **Enhanced:** Interval mapping from Stripe plan types (month, year, one_time, product)
- **Enhanced:** Smart price handling with fallback to plan price for zero-amount transactions
- **Enhanced:** Credit metadata inclusion for trial and full subscription credits

### Phase 4: JVZoo Webhook Processing & User Onboarding ✅ COMPLETED
**Tasks:** Enhanced webhook processing with conditional methods and comprehensive scenario handling
**Review Point:** Complete webhook processing for all transaction types and first-time user onboarding flow
**Status:** ✅ COMPLETED

#### Phase 4a: Webhook Controller Enhancement ✅ COMPLETED
**Files Updated:**
- ✅ **File:** `JVZooWebhookController.php` - Enhanced with conditional methods pattern
  - ✅ Added detection methods: `onboardUser()`, `planSwap()`, `paymentFailed()`, `renewal()`, `cancellation()`
  - ✅ Added handler method stubs for each scenario
  - ✅ Implemented match statement for clean transaction routing
  - ✅ Added proper type safety with enum-based parameters
  - ✅ Enhanced validation with early returns and proper HTTP status codes
  - ✅ Optimized to single transaction type evaluation
  - ✅ Added proper null-safety for user handling

#### Phase 4b: First-Time User Onboarding ✅ COMPLETED
**Files Created:**
- ✅ **Action:** `JVZooOnboardingAction.php` - Complete onboarding flow (leverages RegistrationAction + subscription + email)
  - ✅ **User account creation using RegistrationAction**: Concurrency protection, event firing, default settings
  - ✅ JVZoo plan finding/creation with product mapping
  - ✅ JVZoo subscription creation and linking
  - ✅ Purchase record linking to user and subscription
  - ✅ Database transaction for data integrity
  - ✅ Comprehensive logging and error handling
  - ✅ **Architecture Improvement**: Removed code duplication by using existing RegistrationAction
  - ✅ **Enhanced:** `findOrCreatePlan()` method now creates JVZoo plans if they don't exist to handle out-of-order webhooks
  - ✅ **Enhanced:** Determines trial vs active status based on transaction amount ($0 = trial)

#### Phase 4c: Additional Scenario Actions ✅ COMPLETED
**Files Created:**
- ✅ Action: `JVZooPlanSwapAction.php` - Handle existing user plan changes
  - ✅ **Enhanced:** Always creates new subscriptions (JVZoo doesn't support plan updates)
  - ✅ **Enhanced:** Uses different receipt IDs for plan changes (old subscription refunded, new subscription created)
  - ✅ **Fixed:** Removed logic that tried to update existing subscriptions
- ✅ Action: `JVZooRenewalAction.php` - Process subscription renewals (BILL transactions)
  - ✅ Handles recurring payment renewals for active subscriptions
  - ✅ **Fixed:** Does NOT extend ends_at (only set during cancellation for grace period)
  - ✅ Awards renewal credits using existing credit system
  - ✅ Distinguishes between trial conversion and regular renewal
  - ✅ **Enhanced:** Handles payment recovery after failure (INSF → BILL)
    - ✅ Detects past due subscriptions and restores to active
    - ✅ Clears payment failure tracking metadata
    - ✅ Logs recovery events for monitoring
- ✅ Action: `JVZooCancellationAction.php` - Handle subscription cancellations
  - ✅ Updates subscription status to CANCELED
  - ✅ Calculates grace period end date (allows access until billing period ends)
  - ✅ Immediate cancellation for refunds (RFND) and chargebacks (CGBK)
  - ✅ Links cancellation transaction to subscription
  - ✅ **Enhanced:** Fixed duplicate transaction handling - checks receipt + type combination
  - ✅ **Enhanced:** Records all transactions even when user not found

#### Phase 4d: Trial Management ✅ COMPLETED
**Files Created:**
- ✅ **Action:** `JVZooTrialConversionAction.php` - Handle trial conversions and cancellations
  - ✅ Convert trial to active when payment received (BILL transaction)
  - ✅ Cancel expired trials automatically
  - ✅ Award appropriate credits for trial vs full subscriptions
- ✅ **Command:** `ProcessExpiredJVZooTrialsCommand.php` - Daily cleanup of expired trials
- ✅ **Configuration:** Added trial management to services config
  - ✅ `trial_days` setting (default: 7 days)
  - ✅ `trial_products` array for specific trial product configuration
- ✅ **Onboarding Updates:** Modified JVZooOnboardingAction to handle trial subscriptions
  - ✅ **Business Rule:** ALL JVZoo products offer 7-day free trials (simple, no detection logic)
  - ✅ Always sets trial_ends_at and STATUS_TRIAL for all subscriptions
  - ✅ Configurable trial duration via services.jvzoo.trial_days (default: 7 days)
- ✅ Action: `JVZooPaymentFailureAction.php` - Process payment failures (INSF transactions)
  - ✅ Marks subscription as past due (STATUS_EXPIRED)
  - ✅ Links failed payment transaction to subscription
  - ✅ Tracks failure count and timestamp in metadata
  - ✅ Comprehensive logging for monitoring

#### Phase 4d: Testing ✅ COMPLETED
**Files Created:**
- ✅ **Test:** `tests/Feature/Subscriptions/JVZoo/JVZooWebhookControllerTest.php`
  - 18 comprehensive test cases covering all webhook scenarios
  - Proper middleware exclusion for isolated testing
  - Factory-based test data generation
  - Helper function for webhook payload creation

**Events & Listeners (Still Pending for Phase 5):**
- Event: `JVZooUserOnboardedEvent.php` - Dispatched when new user is onboarded
- Event: `JVZooPlanSwappedEvent.php` - Dispatched when existing user changes plans
- Event: `JVZooRenewalEvent.php` - Dispatched on subscription renewals
- Event: `JVZooCancellationEvent.php` - Dispatched on cancellations
- Event: `JVZooPaymentFailedEvent.php` - Dispatched on payment failures
- Listener: `SendJVZooOnboardingEmailListener.php` - Send welcome emails
- Listener: `AssignJVZooCreditsListener.php` - Assign plan-based credits
- Listener: `LogJVZooTransactionListener.php` - Comprehensive transaction logging

**Key Features Completed in Phase 4:**
- ✅ Cashier-style webhook controller with conditional methods
- ✅ Automatic user account creation for first-time purchasers
- ✅ Complete onboarding workflow: Purchase → User → Subscription → Credits → Email
- ✅ Transaction type handling: Sales, renewals, cancellations, payment failures, refunds, chargebacks
- ✅ Plan mapping between JVZoo products and internal subscription plans
- ✅ Middleware-based validation (signature and payload)
- ✅ Comprehensive error handling and logging
- ✅ Full test coverage with 18 test scenarios

### Phase 5: Credit System ✅ COMPLETED
**Tasks:** Credit allocation for trials, conversions, renewals, and plan swaps
**Review Point:** Credit allocation and subscription activation
**Status:** ✅ COMPLETED

#### Credit System Implementation ✅ COMPLETED
**Key Features:**
- ✅ **JVZoo Plan Metadata**: Includes `full_credits` and `trial_credits` in all JVZoo plans
- ✅ **Trial Credits**: Awarded when trial starts via `JVZooOnboardingAction`
- ✅ **Full Credits**: Set when user converts from trial to paid via `JVZooTrialConversionAction`
- ✅ **Renewal Credits**: No rollover - uses `CreditService::set()` to replace credits
- ✅ **Plan Swap Credits**: Awarded when user changes plans via `JVZooPlanSwapAction`
- ✅ **Yearly Plan Handling**: Divides full_credits by 12 for monthly allocation

**Files Updated:**
- ✅ `ProcessJVZooTransactionAction.php` - Creates JVZoo plans with credit metadata
- ✅ `JVZooOnboardingAction.php` - Awards trial/full credits based on transaction amount
- ✅ `JVZooTrialConversionAction.php` - Awards full credits on trial conversion
- ✅ `JVZooRenewalAction.php` - Awards renewal credits (no rollover)
- ✅ `JVZooPlanSwapAction.php` - Awards credits for new plan

**Credit Defaults:**
- Trial Credits: 50 (from plan metadata)
- Full Credits: 500 (from plan metadata)
- Yearly Plans: Full credits divided by 12 for monthly release

**Completed Tasks:**
- [x] Create `ReleaseMonthlyJVZooCreditsCommand` for yearly plan credit rollover (similar to `ReleaseMonthlyCreditsCommand`)
  - ✅ Command: `app/Syllaby/Subscriptions/Commands/ReleaseMonthlyJVZooCreditsCommand.php`
  - ✅ Uses `trial_ends_at` as anniversary date (instead of `cycle_anchor_at`)
  - ✅ Requires trial to have ended (`trial_ends_at < today()`)
  - ✅ Awards monthly credits to users on yearly JVZoo plans
  - ✅ Test: `tests/Unit/Subscriptions/ReleaseMonthlyJVZooCreditsCommandTest.php` - All tests passing

### Phase 6: Email & UI ⏳
**Tasks:** 9.1, 9.2 (email templates and notifications)
**Review Point:** Email delivery and user experience
**Status:** Not Started
**Files to Create:**
- Mail: `JVZooOnboardingEmail.php`
- View: `jvzoo-onboarding.blade.php`

### Phase 7: Security & Validation ⏳
**Tasks:** 10.1, 10.2, 10.3 (security measures and validation)
**Review Point:** Security implementation and edge case handling
**Status:** Not Started

### Phase 8: Testing & Configuration ⏳
**Tasks:** 11.1, 11.2, 11.3, 12.1, 12.2 (testing and configuration)
**Review Point:** Complete testing coverage and final configuration
**Status:** Not Started

---

## Dependencies Summary

### External Dependencies:
- JVZoo webhook URL configuration
- Frontend onboarding page development (if needed)

### Internal Dependencies:
- Existing subscription and credit systems
- User authentication system
- Email template infrastructure

---

## Timeline Estimates

- **Phase 1:** 3-4 days ✅ COMPLETED
- **Phase 2:** 4-5 days ✅ COMPLETED (Enhanced with complete interface overhaul)
- **Phase 3:** 3-4 days ✅ COMPLETED
- **Phase 4:** 3-4 days ✅ COMPLETED (Comprehensive webhook processing and testing)
- **Phase 5:** 2-3 days ⏳ Next
- **Phase 6:** 2-3 days
- **Phase 7:** 2-3 days
- **Phase 8:** 3-4 days

**Total Estimated Time:** 22-30 days
**Completed So Far:** ~17-21 days (Phases 1-5)

---

## Notes
- This plan creates a universal subscription management system similar to Laravel Cashier
- Supports multiple providers (Stripe, Google Play, JVZoo) with identical interfaces
- Enhanced with complete Cashier-style methods and proper type safety
- Keeps JVZoo implementation simple and focused on core requirements
- Review points after each phase ensure quality and progress tracking 
- **Latest Update**: Complete interface overhaul with BillableContract enhancement and subscription model cleanup 

## JVZoo Implementation Decisions (Based on Actual Behavior)
Based on extensive testing and implementation, we've documented JVZoo's actual webhook behavior:

### Receipt ID Usage
- **Trial-to-Paid Conversion**: Uses the SAME receipt ID
  - Initial $0 SALE transaction for trial
  - Subsequent BILL transaction with full amount using same receipt ID
- **Plan Changes**: Uses DIFFERENT receipt IDs
  - Refund (RFND) for old plan with original receipt ID
  - New SALE for new plan with different receipt ID
  - JVZoo doesn't support true plan swapping - it cancels and creates new subscriptions

### Transaction Handling
- **Refunds (RFND) and Chargebacks (CGBK)**: Always result in immediate subscription cancellation
- **Renewals (BILL)**: Do NOT modify `ends_at` field - this is only set during cancellation for grace period
- **Payment Failures (INSF)**: Mark subscription as expired/past due
- **Payment Recovery**: BILL transaction after INSF restores subscription to active status

### Key Implementation Details
- `JVZooOnboardingAction`: Creates JVZoo plans if they don't exist (handles out-of-order webhooks)
- `JVZooPlanSwapAction`: Always creates new subscriptions (never updates existing ones)
  - **Enhanced (2025-08-01)**: Added constraint to prevent duplicate active subscriptions
  - Only creates new subscription if no active subscription exists (STATUS_ACTIVE, STATUS_TRIAL, STATUS_EXPIRED)
  - Returns existing active subscription if one already exists
- `ProcessJVZooTransactionAction`: Fetches all Plan fields to handle $0 transactions with price fallback
- Middleware handles all signature and payload validation (controller focuses on business logic)
- Comprehensive test coverage ensures all scenarios work as expected (25 test cases)

### Field Renaming (Completed)
- **Database Migration Updated**: Renamed fields to be more concise and removed redundant `transaction_time`
- **Field Mapping**:
  - `transaction_receipt` → `receipt`
  - `product_item` → `product_id`
  - `product_title` → `product_name`
  - `transaction_amount` → `amount`
  - `transaction_time` → removed (using `created_at` timestamp instead)
- **Files Updated**: Migration, Model, Factory, Actions (ProcessJVZooTransactionAction, JVZooPlanSwapAction, JVZooOnboardingAction), Controller, and all test files
- **Consistency**: All references throughout the codebase have been updated to use the new field names

### JVZoo Subscriptions Table Field Update (2025-08-01) ✅
- **Removed Fields**:
  - `transaction_id` - Removed as redundant (was duplicate of receipt)
- **Renamed Fields**:
  - `receipt_id` → `receipt` - Simplified naming
- **Files Updated**:
  - Migration: `2025_07_14_111124_create_jvzoo_subscriptions_table.php`
  - Model: `JVZooSubscription.php` - Updated `findByTransactionId()` to `findByReceipt()`
  - Factory: `JVZooSubscriptionFactory.php` - Updated to use single `receipt` field
  - Actions: `JVZooOnboardingAction.php`, `JVZooPlanSwapAction.php` - Updated field references
  - Tests: `JVZooWebhookControllerTest.php`, `ReleaseMonthlyJVZooCreditsCommandTest.php` - Updated test data

### Subscription Constraint Business Rule (2025-08-01) ✅
- **Business Rule**: A user can only have one active subscription at a time
- **Active States**: STATUS_ACTIVE, STATUS_TRIAL, STATUS_EXPIRED (suspended)
- **Implementation**:
  - Updated `JVZooPlanSwapAction::createOrUpdateSubscription()` to check for existing active subscriptions
  - Returns existing subscription if one is found in an active state
  - Only creates new subscription if no active subscription exists or if previous is STATUS_CANCELED
- **Test Coverage**: Added 5 new tests to ensure constraint is enforced:
  - User can only have one active subscription
  - User can only have one trial subscription
  - User can only have one suspended (expired) subscription
  - User can create new subscription if previous is canceled
  - User can create new subscription if no previous exists
