<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

# POS Enhancements

This project includes additional backend modules for a POS system:

- Finance: Incomes and Expenses with categories
- Inventory: Raw Materials + Stock Movements (moving-average valuation)
- Product Recipes: HPP (COGS) calculation and batch production (consumes materials)
- Employees: Basic employee management (name, email, phone, pin) and roles (owner/manager/staff)
- Attendance: Employee clock-in/out (optional geotag + photo), self history, and admin reports (JSON/CSV)
- API for Flutter (PIN login/token, clock-in/out, history, profile)

## New Migrations

- incomes (updated to: date, reference_no, amount, category_id, notes, created_by, updated_by, timestamps, softDeletes)
- income_categories, expense_categories
- expenses (date, reference_no, amount, category_id, vendor, notes, created_by, updated_by, timestamps, softDeletes)
- raw_materials, raw_material_movements
- products (add: sku, sell_price, cogs_method, active)
- product_recipes, product_recipe_items
- employees, attendances
- product_variant_groups, product_variants, product_addon_groups, product_addons
- order_item_variants, order_item_addons

## New Env

Add to your .env:

- OFFICE_LAT= -6.200000
- OFFICE_LNG= 106.816666
- OFFICE_RADIUS_M= 100
- ATTENDANCE_PHOTO_REQUIRED=false

## API Auth

- Admin/backoffice uses Sanctum (already enabled). Roles used: users.roles where 'admin' is treated as owner/manager.
- Attendance API uses a separate guard 'employee' (Sanctum token for Employee model).

## Product Variants & Extras

- **Schema**: products now reference `product_variant_groups`/`product_variants` for required option sets (e.g. Size) and `product_addon_groups`/`product_addons` for optional extras (e.g. Topping). Each option tracks price deltas, stock (for variants), max selection, and sync metadata for offline clients. Order items persist selections via `order_item_variants` and `order_item_addons` snapshots.
- **Product payload**: `POST /api/products` and `/api/products/edit` accept `variant_groups` and `addon_groups` arrays. Example:

```json
{
  "name": "Latte",
  "price": 25000,
  "stock": 10,
  "category_id": 1,
  "variant_groups": [
    {
      "name": "Size",
      "selection_type": "single",
      "is_required": true,
      "variants": [
        { "name": "Regular", "price_adjustment": 0, "is_default": true },
        { "name": "Large", "price_adjustment": 5000 }
      ]
    }
  ],
  "addon_groups": [
    {
      "name": "Topping",
      "max_select": 2,
      "addons": [
        { "name": "Whipped Cream", "price_adjustment": 3000 },
        { "name": "Extra Shot", "price_adjustment": 4000 }
      ]
    }
  ]
}
```
- **Ordering**: `POST /api/orders` and `bulkStore` accept per-item selections:

```json
{
  "items": [
    {
      "product_id": 12,
      "quantity": 2,
      "variants": [{ "variant_id": 34 }],
      "addons": [
        { "addon_id": 51, "quantity": 1 },
        { "addon_id": 52, "quantity": 2 }
      ]
    }
  ]
}
```

  The backend validates selection rules (min/max per group, stock on variants, addon quantity caps), recalculates item totals, snapshots names/prices, and decrements variant stock when configured.
- **Sync**: `/api/sync/products/batch` mirrors the same structure. Variant/addon arrays are optional on updates—omit to keep existing options, send empty arrays to clear them. All synced records are returned as `synced` to keep mobile caches aligned.
- **Testing**: run `php artisan test --filter=OrderController` after adding assertions for variant/addon scenarios. Manual smoke tests: create product with variants/extras, place an order selecting each combination, and confirm payloads under `/api/orders` include `variants` and `addons` details.

## Endpoints (highlights)

- POST /api/auth/pin-login
- Incomes: GET/POST/GET:id/PUT/DELETE /api/incomes
- Expenses: GET/POST/GET:id/PUT/DELETE /api/expenses
- Categories: /api/income-categories, /api/expense-categories
- Raw Materials: /api/raw-materials, /api/raw-materials/{id}/adjust-stock, /api/raw-materials/{id}/movements
- Recipes & HPP: /api/products/{id}/recipe (GET/POST), /api/products/{id}/produce (POST), /api/products/{id}/cogs (GET)
- Employees: /api/employees (GET/POST/PUT/PATCH activate/deactivate)
- Attendance (Flutter):
  - GET /api/employees/me
  - GET /api/attendances/me?date_from&date_to
  - POST /api/attendances/clock-in { lat?, lng?, photo_base64? }
  - POST /api/attendances/clock-out { lat?, lng?, photo_base64? }
  - GET /api/reports/attendances?date_from&date_to&employee_id?&format=json|csv

Rate limiting is applied on attendance endpoints.

## How to use (Flutter via Dio)

Example PIN login:

```
json path=null start=null
POST /api/auth/pin-login
{
  "phone_or_email": "staff@example.com",
  "pin": "1234"
}
```

Use returned token as Bearer for subsequent attendance requests.

## Storage

Attendance photos are stored under storage/app/public/attendances and served via /storage after linking:

```
bash path=null start=null
php artisan storage:link
```

## Tests

Run tests:

```
bash path=null start=null
php artisan test
```

Includes feature tests for PIN login, attendance flow, and recipe/inventory (moving average + HPP).

## Notes

- COGS is calculated using moving average of raw materials with waste percentage: HPP = Σ( qty_per_yield * (1 + waste_pct/100) * average_unit_cost ) / yield_qty.
- Hooks into order consumption rely on the OrderPaid listener to deduct raw materials whenever sales are completed; manual production workflows are no longer available.
