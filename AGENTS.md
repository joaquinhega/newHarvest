# New Harvest Agent Instructions

This repository has two active code paths:

- The legacy PHP application at the repository root (`api/`, `Controller/`, `Model/`, `View/`, `index.php`).
- The modern Laravel backend in `backend/`.

Before editing, always identify which side owns the behavior. Do not mix legacy PHP changes with Laravel changes unless the user explicitly asks for a bridge or migration step.

## Project references

- Read [README.md](README.md) for the legacy app overview and folder map.
- Read [backend/README.md](backend/README.md) for the Laravel baseline.

## Working rules

- Keep the migration incremental and non-breaking. Treat the legacy app as production-facing unless told otherwise.
- Never hardcode credentials, URLs, tokens, IDs, or environment-specific values. Use `.env`, `config/*.php`, or database relations.
- Prefer small, focused edits that preserve existing conventions.
- When asked to implement a task, explain the next step clearly and wait for explicit confirmation before moving to the following milestone.
- If a requirement is ambiguous, resolve it locally from nearby code or docs before broadening the search.
- Favor linking to existing docs instead of duplicating their content.
- When closing any task, always report the actual state in plain language: `completed`, `partially completed`, or `not completed`.
- In that closeout, explicitly state what was implemented, what remains if anything, and whether the user story/backlog description was fully satisfied.
- If the task is already implemented or mostly implemented, say so clearly and avoid redoing work without a reason.
- After the summary, include a short manual verification checklist for a human reviewer so they can test the change locally.

## Task closeout format

Use this structure at the end of each task response:

1. Short status line.
2. Brief summary of what changed.
3. Brief note on what is still pending, if anything.
4. Manual test steps for a human reviewer.

## Manual verification template

For backend/API tasks, include a practical local test flow such as:

1. Start the Laravel backend from `backend/` with `php artisan serve`.
2. Confirm the app is reachable on localhost in the browser or with `curl`.
3. Test the endpoint with Postman or Thunder Client using the expected method and payload.
4. Verify the response body, status code, and any returned token or data.
5. If the endpoint is protected, reuse the token to call a second authenticated endpoint.
6. Note any expected failures or invalid-credential cases that should return the proper error.

Example for authentication work:

1. Run `php artisan serve --host=127.0.0.1 --port=8000` inside `backend/`.
2. Send `POST http://127.0.0.1:8000/api/v1/auth/login` with `login` and `password`.
3. Confirm the API returns `success`, the user payload, and a Bearer token.
4. Call `GET http://127.0.0.1:8000/api/user` with `Authorization: Bearer <token>`.
5. Call `POST http://127.0.0.1:8000/api/v1/auth/logout` and confirm the token is rejected afterwards.

## Backend Laravel conventions

- Place API work under `backend/routes/api.php`, controllers in `backend/app/Http/Controllers/`, models in `backend/app/Models/`, and database changes in `backend/database/migrations/` and `backend/database/seeders/`.
- Keep API responses JSON-first and version new endpoints under `/api/v1/...` when extending the modern backend.
- Use Laravel Sanctum for authenticated API access when adding protected endpoints.

## Legacy PHP conventions

- Legacy endpoints live under `api/`, `Controller/`, `Model/`, and `View/`.
- Preserve the existing procedural style in the legacy layer unless the task is explicitly a refactor.
- For PHP files in the legacy layer, validate syntax before finishing a change.

## Common commands

Run these from `backend/` when working on Laravel:

```powershell
composer install
npm install
php artisan key:generate
php artisan test
npm run dev
php artisan serve
```

Run this from the repository root for legacy PHP syntax checks:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Notes for future agents

- If a task touches both systems, state the boundary first and work one side at a time.
- Mention /chronicle improve if the user wants to refine these instructions from past session friction.