# Channel Partner Program & Home Page Redesign

Channel partners replace public seller sign-up. A partner registers, uploads KYC
documents, is approved by an admin, and then orders from a dedicated portal at
contract prices that are agreed offline and entered by the admin.

## Deploying

1. **Back up the database.**
2. Upload / pull the code, then:
   ```bash
   composer install --no-dev --optimize-autoloader   # composer.lock updates nesbot/carbon to 2.73 (PHP 8.4)
   php artisan migrate --force                       # creates the channel_partner_* tables + permissions
   php artisan optimize:clear
   ```
   `php artisan migrate` only runs files in `database/migrations` (see `app/Database/AppMigrator.php`).
   The base schema still comes from `shop.sql` + `sqlupdates/`.
3. Make sure `storage/app/channel-partner-documents/` exists, is writable by PHP, and still
   contains its `.htaccess` (it blocks direct web access to KYC files).
4. PHP limits for document uploads: `upload_max_filesize >= 6M`, `post_max_size >= 8M`.
5. Configure SMTP (`MAIL_*` in `.env`) so admins get "new application" emails and partners get
   approval / rejection emails. Without mail the system still works; emails are skipped.
6. Optional: turn off **Vendor System** in admin settings if you no longer want seller features.
   The partner links do not depend on it.

Switch the whole program off with `CHANNEL_PARTNER_ENABLED=false` in `.env`.

## Where things are

| Who | URL |
| --- | --- |
| Public landing / sign-up | `/partner`, `/partner/register` |
| Partner login | `/partner/login` (email or mobile) |
| Partner portal | `/partner/dashboard`, `/catalog`, `/cart`, `/checkout`, `/orders`, `/account` |
| Admin | **Admin → Channel Partners** (`/admin/channel-partners`) |

Header and footer "Become a Seller" links now point to the partner program in every header/footer style.

## Admin workflow

1. A badge on **Channel Partners** shows applications waiting for review.
2. Open the application, view the documents (each can be marked verified/rejected with a remark).
3. **Approve** (partner gets a code like `CP00001`) or **Request changes** with a reason. The partner
   sees the reason, fixes the application and resubmits.
4. Fill **Contract terms**: default discount %, credit limit, minimum order value, payment terms,
   contract reference and expiry. Ordering pauses automatically after the expiry date.
5. Add **Pricing rules**.
6. **Suspend** / **Reactivate** at any time.

Staff access is controlled by three permissions (Staffs → Roles): `view_channel_partners`,
`manage_channel_partners`, `manage_channel_partner_pricing`.

### How a partner's price is worked out

For each cart line:

1. Rules for **that product** are used if any exist; otherwise rules for the product's **category**;
   otherwise **all-products** rules.
2. Within that scope, the rule with the **highest min. quantity ≤ ordered quantity** wins.
   This is how bulk tiers work (e.g. 1+ = 10% off, 50+ = 15% off, 200+ = fixed ₹120).
3. If nothing matches, the partner's **default discount %** applies.
4. The result is never higher than the current public (sale) price.

A rule is `% off list price`, `flat amount off per unit` or `fixed unit price`, with optional
start/end dates. Partners see the next price break ("Add 20 more to pay ₹X each") in the catalogue and cart.

## Partner orders

* Created as normal orders (`order_from = channel_partner`, `payment_type = partner_credit`,
  unpaid), so they appear in **Sales → All Orders** with the usual invoice, status and notifications.
* PO number and notes are saved in the order's additional info. The price breakdown per line is
  kept in `channel_partner_orders.pricing_snapshot` for audit.
* No shipping charge is added (freight is per contract). Stock is reserved with row locks and
  prices are re-checked when the order is placed.
* The credit limit counts unpaid, non-cancelled partner orders.

## Home page (nexa theme)

`resources/views/frontend/nexa/index.blade.php` was rebuilt, mobile first. It is still driven by the
same admin settings (sliders, banners 1-3, flash deal, today's deals, featured, best sellers,
categories, brands, new arrivals).

* Hero slider takes the proportions of the first slider image. Upload all slides at the same size.
  Mobile slider images are used on phones when set.
* The first two **Banner 1** images become the hero side tiles. Wide images (about 800x480) work best.
  Any further Banner 1 images show as a banner row.
* Without slider images, a branded hero uses the site motto and meta description.

## Rollback

Original versions of every modified file are in `../backups/pre-channel-partner-2026-10-25/`.
`php artisan migrate:rollback` drops the channel partner tables (this deletes partner data).
