# Copilot Instructions for NationalResourceBenefits

NOTE: YOU MUST REFERENCE THIS FILE .github/FRONTEND_INTEGRATION_ROADMAP.md FOR ADDITIONAL CONTEXT FOR USER AUTHENTICATED BUILDING PROCESS.
IMPORTANT: This file is intended to provide context to AI code assistants like GitHub Copilot. It should not be modified or deleted.
This project is a Laravel 12 application using Inertia.js with Vue 3 and Tailwind CSS (Vite-based).

## Big Picture Architecture
- **Framework:** Laravel 12 (PHP 8.2+)
- **Frontend Stack:** Vue 3 (Composition API) with Inertia.js for seamless SPA-like experience.
- **Styling:** Tailwind CSS 3/4 via Vite.
- **Routing:** Handled by Laravel (`routes/web.php`), with Ziggy for using Laravel routes in Vue.
- **Authentication:** Laravel Breeze (Inertia/Vue version).

## Project Structure & Conventions
- **Controllers:** Located in `app/Http/Controllers`. Return `Inertia::render('PageName', [...$data])`.
- **Models:** Located in `app/Models`. Use Eloquent with type-hinted casts.
- **Frontend Pages:** Located in `resources/js/Pages`. Follow the structure of Laravel routes.
- **Frontend Components:** Located in `resources/js/Components`.
- **Data Flow:** Laravel Controllers -> Inertia Props -> Vue Components.

## Essential Workflows
- **Development:** `composer run dev` (Runs `php artisan serve`, `queue:listen`, and `npm run dev` concurrently).
- **Setup:** `composer run setup` (Installs deps, copies .env, generates key, migrates, and builds).
- **Testing:** `./vendor/bin/phpunit` or `php artisan test`.
- **Linting:** `./vendor/bin/pint` for PHP styling.

## Key Patterns
- **Inertia Rendering:**
  ```php
  return Inertia::render('Dashboard', [
      'user' => $request->user(),
  ]);
  ```
- **Vue Composition API:** Use `<script setup>` in Vue components.
- **Route Usage in JS:** Use the `route()` helper (provided by Ziggy).
  ```javascript
  const submit = () => {
      form.post(route('profile.update'));
  };
  ```

## Important Files
- `routes/web.php`: Primary entry point for web routes.
- `resources/js/app.js`: Inertia & Vue initialization.
- `composer.json`: Defines custom scripts like `dev` and `setup`.
- `tailwind.config.js` & `vite.config.js`: Frontend build configuration.
