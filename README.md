# Vending Machine (Laravel)

A vending machine modelled with Domain-Driven Design inside a single bounded
context, served through a Laravel application.

The machine accepts coins of `0.05`, `0.10`, `0.25` and `1.00`, sells Water
(`0.65`), Juice (`1.00`) and Soda (`1.50`), returns the inserted coins on
demand, and gives change when you overpay.

The important architectural point: **the domain knows nothing about Laravel.**
It lives in `src/VendingMachine` and depends only on PHP. Laravel is wired in
through a thin adapter layer (a service provider, a controller, form requests,
a console command and a cache-backed repository). Swap the framework and the
domain code does not change.

---

## Requirements

- PHP 8.3+
- Composer

Tests use the in-memory SQLite / array cache configured in `phpunit.xml`, so no
external services are needed.

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Running it

```bash
php artisan vending:demo   # the three spec examples, through the container
php artisan test           # domain unit tests + HTTP feature tests
php bin/demo.php            # framework-free demo, straight on the domain
php artisan serve          # serve the API
```

`php artisan vending:demo` prints:

```
Example 1  -> Soda, change: (no change)
Example 2  -> returned: 0.1, 0.1
Example 3  -> Water, change: 0.25, 0.1
```

---

## With Docker (recommended for evaluation)

No local PHP needed, and no external services are spun up.

```bash
docker compose build
docker compose run --rm app php artisan vending:demo   # spec examples
docker compose run --rm app php artisan test           # full test suite
docker compose up                                      # API on http://localhost:8000
```

The image installs only the extensions this app uses (`mbstring`, `pdo_sqlite`).
Tests run on SQLite `:memory:` and the array cache, so there is deliberately no
MySQL/Redis container - the app needs none. (Laravel Sail would work too, but it
provisions services this domain doesn't use; a purpose-built image keeps
evaluation a single command.)

---

## HTTP API

| Method & path                   | Action                | Body                                             |
|---------------------------------|-----------------------|--------------------------------------------------|
| `POST /api/coins`               | insert a coin         | `{ "coin": 0.25 }`                               |
| `POST /api/coins/return`        | return inserted coins | -                                                |
| `POST /api/products/{selector}` | buy a product         | - (`selector` = WATER / JUICE / SODA)            |
| `POST /api/service`             | restock + change      | `{ "products": {"SODA":5}, "coins": {"25":10} }` |

Business failures (unknown product, sold out, not enough money, no change
possible, invalid coin) return HTTP `422` with `{ "error": "..." }`. The mapping
is registered once in `bootstrap/app.php`, so controllers stay free of error
handling.

Example session:

```bash
curl -X POST localhost:8000/api/service -H 'Content-Type: application/json' \
  -d '{"products":{"WATER":5,"JUICE":5,"SODA":5},"coins":{"100":10,"25":10,"10":10,"5":10}}'

curl -X POST localhost:8000/api/coins -H 'Content-Type: application/json' -d '{"coin":1}'
curl -X POST localhost:8000/api/products/WATER
# -> {"product":"Water","change":[0.25,0.1]}
```

---

## Where things live

```
src/VendingMachine/                 # the bounded context - pure PHP, no framework
|-- Domain/
|   |-- Money/        Coin, Money (integer cents), CoinCollection, CoinBank,
|   |                 ChangeCalculator (+ Exception/)
|   |-- Catalog/      ProductSelector, Product, Catalog, ProductInventory (+ Exception/)
|   |-- Vending/      VendingMachine (aggregate root), VendOutcome,
|   |                 VendingMachineRepository (port) (+ Exception/)
|   `-- VendingMachineException.php          # marker for every business failure
|-- Application/
|   |-- Action/       InsertCoin / ReturnCoins / SelectProduct / ServiceMachine
|   |-- Request/      input DTOs (raw input -> validated domain types)
|   `-- Dto/          output DTOs (domain -> primitives)
`-- Infrastructure/
    `-- Persistence/  InMemoryVendingMachineRepository   # for CLI / tests

app/                                 # the Laravel adapter layer
|-- Providers/VendingMachineServiceProvider.php   # binds the port + change strategy
|-- Http/Controllers/VendingMachineController.php # HTTP -> Action -> JSON
|-- Http/Requests/                                # HTTP-shape validation
|-- Console/Commands/VendingDemoCommand.php       # php artisan vending:demo
`-- VendingMachine/Persistence/CacheVendingMachineRepository.php  # the only Laravel-aware adapter
```

---

## Design decisions

- **Money is always integer cents.** Floats appear only at the presentation
  boundary; arithmetic never touches them. Removes a whole class of rounding
  bugs.

- **The aggregate enforces every invariant** (enough money, in stock, change is
  possible) and is the only thing that can change machine state. Actions only
  orchestrate; the controller only translates HTTP.

- **Operations are atomic.** `vend()` projects the change against a *copy* of the
  coin bank before mutating anything; if change can't be made, nothing changes
  and the balance survives. Guard order is stock -> funds -> change.

- **Persistence is a port with two adapters.** The in-memory one (in `src/`)
  suits a single long-lived process; the Laravel cache one (in `app/`) keeps the
  aggregate alive between stateless HTTP requests. Switching cost the domain
  exactly zero changes - that is the point of the `VendingMachineRepository`
  interface.

- **Change is an isolated strategy** (`ChangeCalculator`, greedy and
  availability-aware). Greedy is optimal here with unlimited coins; with limited
  stock it can miss a solution a dynamic-programming version would find.
  Replacing it is a one-class change.

- **The framework is a detail.** The domain has no `use Illuminate\...`. Only the
  files in `app/` that *must* know Laravel do.

---

## Tests

- **`tests/Unit`** - value objects, coin bank, change calculator, product
  inventory and the `VendingMachine` aggregate (the spec examples and every
  invariant). Pure PHPUnit, no framework boot.
- **`tests/Feature/VendingScenarioTest.php`** - the use cases through the Actions
  and the in-memory repository, framework-free.
- **`tests/Feature/VendingApiTest.php`** - the full HTTP stack: routes, container
  bindings, the cache repository and the exception handler.
