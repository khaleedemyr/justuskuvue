# Self Order Web Staging Flow

## Target
Persist self-order from web reservation flow to staging tables first:
- `web_self_orders`
- `web_self_order_items`

## Endpoint (Web)
- Method: `POST`
- Path: `/proxy/ymsoft-api/self-order/staging`
- Handler: `ErpSiteProxyController@storeSelfOrderStaging`

Do not write directly to POS core tables (`orders`, `order_items`) from web channel.

## Recommended Write Sequence
1. Start DB transaction.
2. Insert header into `web_self_orders`.
3. Insert all item rows into `web_self_order_items`.
4. Commit transaction.
5. A separate sync worker/service promotes data to POS core tables.

## Minimum Header Payload Mapping
- `id`: generated unique string (e.g. UUID/ULID).
- `order_no`: generated business order number for web channel.
- `reservation_id`: reservation id if available.
- `reservation_number`: reservation number if available.
- `outlet_id`: selected outlet id.
- `customer_name`, `customer_phone`, `customer_email`: from reservation form.
- `pax`: reservation guest count.
- `table_ids_json`: JSON array of selected table IDs.
- `notes`: reservation note / checkout note.
- `subtotal`: sum(item.subtotal).
- `dpp`, `pb1`, `service`, `grand_total`: pricing snapshot at submit time.
- `status`: `pending_sync`.
- `paid_status`: `unpaid`.
- `created_at`, `updated_at`: current server timestamp.

## Minimum Item Payload Mapping
- `id`: generated unique string.
- `web_self_order_id`: header id reference.
- `item_id`: POS item master id.
- `item_name`: snapshot name.
- `qty`, `price`, `subtotal`.
- `modifiers`: JSON string.
- `notes`: item note.
- `b1g1_promo_id`, `b1g1_status`: nullable.
- `created_at`: current server timestamp.

## Example Insert Pattern
```sql
START TRANSACTION;

INSERT INTO web_self_orders (
  id, reservation_id, reservation_number, order_no, outlet_id,
  customer_name, customer_phone, customer_email,
  order_channel, order_type, pax, table_ids_json, notes,
  subtotal, discount, cashback, dpp, pb1, service, grand_total,
  status, paid_status, created_at, updated_at
) VALUES (
  :id, :reservation_id, :reservation_number, :order_no, :outlet_id,
  :customer_name, :customer_phone, :customer_email,
  'self_order_web', 'dine_in', :pax, :table_ids_json, :notes,
  :subtotal, :discount, :cashback, :dpp, :pb1, :service, :grand_total,
  'pending_sync', 'unpaid', NOW(), NOW()
);

-- Repeat for each item
INSERT INTO web_self_order_items (
  id, web_self_order_id, item_id, item_name, qty, price, subtotal,
  tally, modifiers, notes, b1g1_promo_id, b1g1_status, created_at
) VALUES (
  :item_id, :web_self_order_id, :pos_item_id, :item_name, :qty, :price, :subtotal,
  :tally, :modifiers_json, :item_notes, :b1g1_promo_id, :b1g1_status, NOW()
);

COMMIT;
```

## Example Request JSON (from frontend)
```json
{
  "reservation_id": 123,
  "reservation_number": "RSV-20260508-0001",
  "outlet_id": 7,
  "outlet_code": "JST-BDG-01",
  "customer_name": "Hendi",
  "customer_phone": "081234567890",
  "customer_email": "hendi@example.com",
  "order_channel": "self_order_web",
  "order_type": "dine_in",
  "pax": 4,
  "table_ids": [21, 22],
  "notes": "No spicy",
  "subtotal": 240000,
  "discount": 0,
  "cashback": 0,
  "dpp": 240000,
  "pb1": 24000,
  "service": 12000,
  "grand_total": 276000,
  "commfee": 0,
  "rounding": 0,
  "items": [
    {
      "item_id": "53361",
      "item_name": "French Fries",
      "qty": 2,
      "price": 45000,
      "subtotal": 90000,
      "modifiers": [{"modifier_id": 1, "option_id": 2, "qty": 1}],
      "notes": "No salt"
    },
    {
      "item_id": "53437",
      "item_name": "Spaghetti Bolognese",
      "qty": 1,
      "price": 150000,
      "subtotal": 150000,
      "modifiers": [],
      "notes": null
    }
  ]
}
```

## Suggested Promotion Status Lifecycle
- `pending_sync` -> `synced` (success to POS core tables)
- `pending_sync` -> `sync_failed` (retryable error)
- `synced` + `paid_status='paid'` when linked POS order is paid
