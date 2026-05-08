-- Self-order web staging tables (run manually, not migration)
-- Purpose:
-- 1) Store self-order input from web channel safely without touching POS core tables.
-- 2) Keep payload snapshot before being transformed to POS orders/order_items.

CREATE TABLE IF NOT EXISTS web_self_orders (
  id varchar(100) NOT NULL,
  reservation_id bigint NULL,
  reservation_number varchar(100) NULL,
  order_no varchar(100) NOT NULL,
  outlet_id int NOT NULL,
  outlet_code varchar(50) NULL,

  customer_name varchar(255) NULL,
  customer_phone varchar(50) NULL,
  customer_email varchar(190) NULL,

  order_channel varchar(50) NOT NULL DEFAULT 'self_order_web',
  order_type varchar(50) NOT NULL DEFAULT 'dine_in',
  pax int NULL,
  table_ids_json longtext NULL,
  notes text NULL,

  subtotal int NOT NULL DEFAULT 0,
  discount int NOT NULL DEFAULT 0,
  cashback int NOT NULL DEFAULT 0,
  dpp int NOT NULL DEFAULT 0,
  pb1 int NOT NULL DEFAULT 0,
  service int NOT NULL DEFAULT 0,
  grand_total int NOT NULL DEFAULT 0,
  commfee decimal(15,2) NOT NULL DEFAULT 0.00,
  rounding decimal(15,2) NOT NULL DEFAULT 0.00,

  status varchar(50) NOT NULL DEFAULT 'pending_sync',
  paid_status varchar(50) NOT NULL DEFAULT 'unpaid',

  -- Linked values after item is promoted to POS core orders table.
  pos_order_id varchar(100) NULL,
  pos_order_nomor varchar(100) NULL,

  sync_attempt_count int NOT NULL DEFAULT 0,
  last_sync_at datetime NULL,
  sync_error text NULL,

  created_at datetime NULL,
  updated_at datetime NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_web_self_orders_order_no (order_no),
  KEY idx_web_self_orders_status (status),
  KEY idx_web_self_orders_paid_status (paid_status),
  KEY idx_web_self_orders_reservation_id (reservation_id),
  KEY idx_web_self_orders_pos_order_id (pos_order_id),
  KEY idx_web_self_orders_created_at (created_at),
  KEY idx_web_self_orders_outlet_id (outlet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS web_self_order_items (
  id varchar(100) NOT NULL,
  web_self_order_id varchar(100) NOT NULL,

  -- Must map to POS item master id.
  item_id varchar(100) NOT NULL,
  item_name varchar(255) NOT NULL,
  qty int NOT NULL DEFAULT 0,
  price int NOT NULL DEFAULT 0,
  subtotal int NOT NULL DEFAULT 0,

  tally varchar(100) NULL,
  modifiers longtext NULL,
  notes text NULL,

  b1g1_promo_id int NULL,
  b1g1_status varchar(20) NULL,

  -- Linked value after item is promoted to POS core order_items table.
  pos_order_item_id varchar(100) NULL,

  created_at datetime NULL,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_web_self_order_items_order_id (web_self_order_id),
  KEY idx_web_self_order_items_item_id (item_id),
  KEY idx_web_self_order_items_created_at (created_at),
  KEY idx_web_self_order_items_b1g1_promo_id (b1g1_promo_id),
  KEY idx_web_self_order_items_pos_order_item_id (pos_order_item_id),
  CONSTRAINT fk_web_self_order_items_order
    FOREIGN KEY (web_self_order_id) REFERENCES web_self_orders(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
