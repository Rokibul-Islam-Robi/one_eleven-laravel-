# Seller authentication and onboarding decisions

## Authentication architecture

Sellers use a dedicated Laravel session guard and Eloquent provider backed by
the existing `sellers` table. The `password_hash` column remains the password
source. This keeps seller identity separate from storefront customers
(`customers`) and the legacy CMS/admin session (`admin_user`) without changing
either existing authentication flow.

Every seller request resolves the seller again from the database. A previously
authenticated session therefore cannot retain access after the seller is
rejected, suspended, or deactivated.

## Application and account status

Application review state is represented separately from account access state.
The existing seller `status` column continues to govern access; additive
application and decision metadata distinguish submitted, approved, and
rejected applications. Approval does not itself grant active dashboard access;
activation is an explicit administrator action.

Pending sellers may view their application status and maintain application
details. Active sellers can use the seller dashboard. Rejected, suspended, and
inactive accounts cannot perform seller writes.

## Contact verification

Email verification uses Laravel's signed verification links and the configured
mail transport. The current development setup uses Laravel's log mailer, so
operational email delivery requires the deployment to configure a real mail
transport. There is no SMS/OTP integration in this codebase; phone numbers are
not represented as verified and no phone-verification claim is made.

## Application review and administration

The seller module in the existing admin panel is restricted to the global
`admin` role; the current role model has no separate catalog or finance admin
roles. Administrators can review applications and private documents, approve
email-verified applications, activate or deactivate sellers, and reject or
suspend with a recorded reason. These state changes are checked against the
latest database state and recorded in `seller_logs`. Payout account numbers
are masked in the admin view, and decision emails are sent through Laravel's
configured mail transport.

Registration accepts one optional supporting document. Sellers can add
additional documents from their private profile after registration. Document
files stay on the private local disk, and both seller and admin retrieval
routes authorize access before returning a no-store response.

The additive Phase 1 schema is in
`database/migrations/2026_11_03_000002_add_seller_phase_one.php`. Existing
seller and audit rows are preserved; the migration creates document and
password-reset storage and adds application, verification, activation, and
admin audit metadata.

## Sensitive account changes and audit

Changes to payout-account details require the seller's current password and
are recorded without logging the account number or other sensitive values.
Seller documents remain on the existing private local storage disk and are
served only through seller- or administrator-authorized routes.

## Scope boundary

This decision implements identity, onboarding, approval, profile access,
seller isolation, and audit support only. Product lifecycle, fulfillment,
commission, payouts, financial reports, and notifications beyond onboarding
and decisions remain outside Phase 1.
