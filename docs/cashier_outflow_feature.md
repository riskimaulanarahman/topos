# Cashier Outflow Feature

This document summarises the end-to-end cashier outflow capability that now exists across the backend API and Flutter client.

## Backend Overview

- **Migration**: `cashier_outflows` table stores cashier session relationship, amount, category, notes, offline metadata (`client_id`, `is_offline`, `synced_at`).
- **Model**: `App\Models\CashierOutflow` with relations to `CashierSession`, `User`, and `Outlet`.
- **API Endpoints** (`auth:sanctum`, `subscription.active`):
  - `GET /api/cashier/outflows?session_id=<id>` – list synced outflows for the current user/session.
  - `POST /api/cashier/outflows` – record an outflow during an active session (idempotent per `client_id`).
  - `POST /api/cashier/outflows/sync` – batch sync offline outflows.
- **Summary updates**: `CashierSummaryService` now aggregates outflow totals, category breakdown, and adjusts expected cash + difference.
- **Tests**: Feature coverage for create/sync endpoints and closing-session summary expectations (`CashierOutflowTest`, updated `CashierSessionTest`).

## Mobile Client Overview

- **Networking**: `CashierOutflowRemoteDatasource` wraps the new endpoints with friendly error handling; `Variables` exposes URL constants.
- **Local Storage**: `CashierOutflowLocalDatasource` queues pending offline entries per outlet/session and caches last synced list for offline viewing.
- **Models**:
  - `CashierOutflowModel` for API responses (with `categoryLabel`).
  - `PendingCashierOutflow` queued locally.
  - `CashierSummaryModel` extended with `CashierOutflowSummary`, categories, and `cashOutflows` in the cash balance.
- **State Management**: `CashierOutflowCubit` orchestrates loads, submission, offline queueing, and background sync when connectivity returns.
- **UI**:
  - New `CashierOutflowPage` accessible from Settings → Pengeluaran Kasir, showing synced & pending entries with sync controls.
  - `CashierOutflowFormDialog` for amount/category/note input.
  - `CashierSummarySheet` and `PrinterService` display outflow totals and category breakdowns in close-summary flows.

## Offline Behaviour Highlights

- When online submission fails or the device is offline, entries are stored in SharedPreferences with a generated `client_id`.
- `CashierOutflowCubit` surfaces pending entries in the UI and attempts sync after a successful remote fetch or when manually triggered.
- Sync success removes local queue items and refreshes remote data; failures surface a warning banner but keep pending items intact.

## Developer Notes

- The Flutter cubit is provided in `main.dart`, so any screen can access cached data via `context.read<CashierOutflowCubit>()`.
- `CashierSummaryModel.formatOutflowLabel` centralises category presentation; reuse it when rendering new UI.
- Ensure the cashier session is open before calling the store endpoint; the backend validates session ownership/outlet context.
- Do not delete pending entries manually—use the sync endpoint or allow the cubit to manage lifecycle.
- Backend summary JSON now contains `cash_balance.cash_outflows` and `outflows` sections; update any consumers accordingly.
