# Database Schema Documentation

This document provides a comprehensive overview of the database schema for the FulfillSupover application, organized by tables with their complete structure, relationships, and business purpose.

## Table of Contents

1. [Authentication & User Management](#authentication--user-management)
2. [Product Management](#product-management) 
3. [Order Management](#order-management)
4. [Dropship System](#dropship-system)
5. [Support System](#support-system)
6. [Financial Management](#financial-management)
7. [Tracking & Logistics](#tracking--logistics)
8. [Store Management](#store-management)
9. [System Tables](#system-tables)

---

## Authentication & User Management

### users
**Purpose**: Core user accounts for the system including sellers, customers, and administrators.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UNSIGNED INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| email | VARCHAR | UNIQUE, NOT NULL | User email address |
| username | VARCHAR | NULLABLE, INDEXED | Optional username |
| password | VARCHAR | NOT NULL | Hashed password |
| first_name | VARCHAR | NULLABLE | User's first name |
| last_name | VARCHAR | NULLABLE | User's last name |
| phone | VARCHAR | NULLABLE | Contact phone number |
| avatar | VARCHAR | NULLABLE | Profile picture path |
| address | VARCHAR | NULLABLE | User address |
| country_id | UNSIGNED INT | NULLABLE | Foreign key to countries |
| role_id | UNSIGNED INT | NOT NULL | Foreign key to roles |
| birthday | DATE | NULLABLE | Date of birth |
| last_login | TIMESTAMP | NULLABLE | Last login timestamp |
| status | VARCHAR(20) | INDEXED | Account status |
| two_factor_country_code | INT | NULLABLE | 2FA country code |
| two_factor_phone | BIGINT | NULLABLE | 2FA phone number |
| two_factor_options | TEXT | NULLABLE | 2FA configuration |
| email_verified_at | TIMESTAMP | NULLABLE | Email verification timestamp |
| wallet_balance | DOUBLE(8,2) | DEFAULT 0.00 | User's wallet balance |
| webhook_url | VARCHAR | NULLABLE | Webhook endpoint URL |
| telegram_id | BIGINT | NULLABLE | Telegram user ID |
| is_support_us | TINYINT | DEFAULT 0 | Support staff flag |
| max_debit | DECIMAL | NULLABLE | Maximum debit allowed |
| private_seller | TINYINT | DEFAULT 0 | Private seller flag |
| production_user | TINYINT | DEFAULT 0 | Production user flag |
| date_debit | DATE | NULLABLE | Debit date |
| debit_status | TINYINT | DEFAULT 0 | Debit status |
| type_debit | TINYINT | DEFAULT 0 | Debit type |
| type_price | TINYINT | DEFAULT 0 | Price type configuration |
| remember_token | VARCHAR | NULLABLE | Remember me token |
| created_at | TIMESTAMP | INDEXED | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Indexes**: 
- created_at, username, status

### roles
**Purpose**: User role definitions for authorization system.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UNSIGNED INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| name | VARCHAR | UNIQUE | Role name |
| display_name | VARCHAR | NULLABLE | Human-readable role name |
| description | VARCHAR | NULLABLE | Role description |
| removable | BOOLEAN | DEFAULT TRUE | Can role be deleted |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

### permissions
**Purpose**: Permission definitions for authorization system.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UNSIGNED INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| name | VARCHAR | UNIQUE | Permission name |
| display_name | VARCHAR | NULLABLE | Human-readable permission name |
| description | VARCHAR | NULLABLE | Permission description |
| removable | BOOLEAN | DEFAULT TRUE | Can permission be deleted |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

### permission_role
**Purpose**: Many-to-many relationship between permissions and roles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| permission_id | UNSIGNED INT | PRIMARY KEY, FOREIGN KEY | Reference to permissions.id |
| role_id | UNSIGNED INT | PRIMARY KEY, FOREIGN KEY | Reference to roles.id |

**Foreign Keys**:
- permission_id → permissions.id (CASCADE)
- role_id → roles.id (CASCADE)

---

## Product Management

### products
**Purpose**: Main product catalog with base product information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | BIGINT | NULLABLE | Owner/seller of the product |
| name | VARCHAR | NULLABLE | Product name |
| price | VARCHAR | NULLABLE | Base price |
| sku | VARCHAR | NULLABLE | Stock keeping unit |
| style | VARCHAR(55) | NULLABLE | Product style |
| color | VARCHAR | NULLABLE | Product color |
| size | VARCHAR | NULLABLE | Product size |
| stock | INT | NULLABLE | Stock quantity |
| mockup_src | VARCHAR | NULLABLE | Product mockup image |
| weight | INT | NULLABLE | Product weight |
| length | INT | NULLABLE | Product length |
| width | INT | NULLABLE | Product width |
| height | INT | NULLABLE | Product height |
| brand | VARCHAR | NULLABLE | Product brand |
| warehouse_name | VARCHAR | NULLABLE | Warehouse location |
| status | INT | DEFAULT 0, INDEXED | Product status |
| type | INT | DEFAULT 0, INDEXED | Product type |
| catalog_id | VARCHAR | DEFAULT '', INDEXED | Catalog reference ID |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Indexes**: 
- status, type, catalog_id

### product_variants
**Purpose**: Product variations (size, color, style combinations).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| product_id | INT | NULLABLE | Foreign key to products |
| price | VARCHAR | NULLABLE | Variant price |
| sku | VARCHAR | NULLABLE | Variant SKU |
| style | VARCHAR | NULLABLE | Variant style |
| color | VARCHAR | NULLABLE | Variant color |
| size | VARCHAR | NULLABLE | Variant size |
| stock | INT | NULLABLE | Variant stock |
| mockup_src | VARCHAR | NULLABLE | Variant mockup image |
| weight | INT | NULLABLE | Variant weight |
| length | INT | NULLABLE | Variant length |
| width | INT | NULLABLE | Variant width |
| height | INT | NULLABLE | Variant height |
| brand | VARCHAR | NULLABLE | Variant brand |
| warehouse_name | VARCHAR | NULLABLE | Warehouse location |
| variant_id | VARCHAR | NULLABLE | External variant ID |
| basecode | VARCHAR | NULLABLE | Base code for variant |
| active | TINYINT | DEFAULT 1 | Is variant active |
| private_price | DECIMAL | NULLABLE | Private pricing |
| price_regular | DECIMAL | NULLABLE | Regular price |
| supplier_price | DECIMAL | NULLABLE | Supplier cost |
| price_gold | DECIMAL | NULLABLE | Gold tier pricing |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- product_id → products.id

---

## Order Management

### orders
**Purpose**: Main order records for regular fulfillment.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| ref_id | VARCHAR | NULLABLE, UNIQUE | External reference ID |
| order_stt | VARCHAR | NULLABLE | Order status code |
| shipping_label | TEXT | NULLABLE | Shipping label data |
| label_printed | TEXT | NULLABLE | Label print status |
| shipping_service | VARCHAR | NULLABLE | Shipping service used |
| tracking_id | VARCHAR | NULLABLE | Package tracking ID |
| tracking_status | VARCHAR | NULLABLE | Current tracking status |
| fulfill_status | VARCHAR | NULLABLE, INDEXED | Order fulfillment status |
| seller_id | UNSIGNED BIGINT | NULLABLE | Seller user ID |
| store_id | UNSIGNED BIGINT | NULLABLE | Associated store |
| total_cost | DECIMAL | NULLABLE | Total order cost |
| paid_cost | DECIMAL | NULLABLE | Amount paid |
| shipping_cost | DECIMAL | NULLABLE | Shipping cost |
| print_cost | DECIMAL | NULLABLE | Printing cost |
| override_label | TEXT | NULLABLE | Custom label override |
| note | TEXT | NULLABLE | Order notes |
| first_name | VARCHAR | NULLABLE | Customer first name |
| last_name | VARCHAR | NULLABLE | Customer last name |
| phone | VARCHAR | NULLABLE | Customer phone |
| address_1 | VARCHAR | NULLABLE | Primary address |
| address_2 | VARCHAR | NULLABLE | Secondary address |
| city | VARCHAR | NULLABLE | City |
| state | VARCHAR | NULLABLE | State/Province |
| postcode | VARCHAR | NULLABLE | Postal code |
| country | VARCHAR | NULLABLE | Country |
| api_key | TEXT | NULLABLE | API key for order |
| sync_design | TINYINT | DEFAULT 0 | Design sync status |
| sync_label | TINYINT | DEFAULT 0 | Label sync status |
| convert_label | TINYINT | DEFAULT 0 | Label conversion status |
| tracking_link | TEXT | NULLABLE | Tracking URL |
| shipping_json | TEXT | NULLABLE | Shipping data JSON |
| process_time | INT | NULLABLE | Processing time |
| payment_status | VARCHAR | NULLABLE | Payment status |
| post_json | TEXT | NULLABLE | POST data JSON |
| updated_at_buy_label | TIMESTAMP | NULLABLE | Label purchase time |
| updated_at_convert_label | TIMESTAMP | NULLABLE | Label conversion time |
| resole_tracking_not_active | TINYINT | DEFAULT 0 | Resolve tracking flag |
| scan_early | TINYINT | DEFAULT 0 | Early scan flag |
| priority | TINYINT | DEFAULT 0 | Order priority |
| gearment_fulfill | TINYINT | DEFAULT 0 | Garment fulfillment flag |
| complete_time | TIMESTAMP | NULLABLE | Order completion time |
| carrier | VARCHAR | NULLABLE | Shipping carrier |
| order_type | TINYINT | DEFAULT 0, INDEXED | Order type |
| created_at | TIMESTAMP | INDEXED | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Indexes**: 
- fulfill_status, created_at, seller_id, order_type, ref_id (unique)

### order_items
**Purpose**: Individual items within orders.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| order_id | UNSIGNED BIGINT | NULLABLE | Foreign key to orders |
| variant_id | VARCHAR | NULLABLE | Product variant ID |
| price | DECIMAL | NULLABLE | Item price |
| sku | VARCHAR | NULLABLE | Item SKU |
| status | VARCHAR | NULLABLE | Item status |
| quantity | DECIMAL | NULLABLE | Item quantity |
| product_name | TEXT | NULLABLE | Product name |
| front_design | TEXT | NULLABLE | Front design data |
| back_design | TEXT | NULLABLE | Back design data |
| hand_design | TEXT | NULLABLE | Hand design data |
| mockup | TEXT | NULLABLE | Item mockup |
| mockup_back | TEXT | NULLABLE | Back mockup |
| back_mockup | TEXT | NULLABLE | Alternative back mockup |
| design_printed | TEXT | NULLABLE | Print design status |
| override_design | TEXT | NULLABLE | Design override |
| front_design_qr | TEXT | NULLABLE | QR code for front design |
| design_qr | TEXT | NULLABLE | Design QR code |
| append_qr_design | TINYINT | DEFAULT 0 | Append QR to design |
| override_qr | TINYINT | DEFAULT 0 | Override QR code |
| id_style | BIGINT | NULLABLE | Style ID |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- order_id → orders.id

### order_item_metas
**Purpose**: Metadata for order items (key-value pairs).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| order_item_id | UNSIGNED BIGINT | NULLABLE | Foreign key to order_items |
| meta_key | VARCHAR | NOT NULL | Metadata key |
| meta_value | TEXT | NOT NULL | Metadata value |
| update_time | TIMESTAMP | NULLABLE | Last update time |
| oversize_site | VARCHAR | NULLABLE | Oversize site info |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- order_item_id → order_items.id

---

## Dropship System

### order_dropships
**Purpose**: Dropship orders separate from regular fulfillment.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| ref_id | VARCHAR | NOT NULL | External reference ID |
| fulfill_status | INT | NULLABLE | Fulfillment status |
| store_id | UNSIGNED BIGINT | NULLABLE | Associated store |
| user_id | UNSIGNED BIGINT | NULLABLE | Owner user ID |
| shipping_label | TEXT | NULLABLE | Shipping label data |
| print_cost | DECIMAL | DEFAULT 0 | Printing cost |
| shipping_cost | DECIMAL | DEFAULT 0 | Shipping cost |
| total_cost | DECIMAL | DEFAULT 0 | Total order cost |
| paid_cost | DECIMAL | DEFAULT 0 | Amount paid |
| order_stt | VARCHAR | NULLABLE | Order status |
| tracking_id | VARCHAR | NULLABLE | Tracking ID |
| payment_status | VARCHAR | NULLABLE | Payment status |
| convert_label | TINYINT | DEFAULT 0 | Label conversion status |
| complete_time | TIMESTAMP | NULLABLE | Completion time |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

### order_item_dropships
**Purpose**: Items within dropship orders.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| order_id | UNSIGNED BIGINT | NULLABLE | Foreign key to order_dropships |
| variant_id | DECIMAL | NULLABLE | Product variant ID |
| price | DECIMAL | NULLABLE | Item price |
| quantity | DECIMAL | NULLABLE | Item quantity |
| product_name | TEXT | NULLABLE | Product name |
| mockup | TEXT | NULLABLE | Item mockup |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- order_id → order_dropships.id

### order_item_meta_dropships
**Purpose**: Metadata for dropship order items.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| order_item_id | BIGINT | NOT NULL | Foreign key to order_item_dropships |
| meta_key | VARCHAR | NOT NULL | Metadata key |
| meta_value | VARCHAR | NOT NULL | Metadata value |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- order_item_id → order_item_dropships.id

### customer_dropships
**Purpose**: Customer information for dropship orders.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| order_dropship_id | BIGINT | NOT NULL | Foreign key to order_dropships |
| first_name | VARCHAR | NULLABLE | Customer first name |
| last_name | VARCHAR | NULLABLE | Customer last name |
| phone | VARCHAR | NULLABLE | Customer phone |
| address_1 | VARCHAR | NULLABLE | Primary address |
| address_2 | VARCHAR | NULLABLE | Secondary address |
| city | VARCHAR | NULLABLE | City |
| state | VARCHAR | NULLABLE | State/Province |
| postcode | VARCHAR | NULLABLE | Postal code |
| country | VARCHAR | NULLABLE | Country |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- order_dropship_id → order_dropships.id

### product_variant_dropships
**Purpose**: Product variants specifically for dropship system.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| product_id | BIGINT | NOT NULL | Foreign key to products |
| skus | VARCHAR | NOT NULL | SKU list |
| variant_name | VARCHAR | NOT NULL | Variant name |
| weight | INT | NOT NULL | Variant weight |
| length | INT | NOT NULL | Variant length |
| width | INT | NOT NULL | Variant width |
| height | INT | NOT NULL | Variant height |
| stock | INT | NOT NULL | Stock quantity |
| status | INT | NOT NULL | Variant status |
| catalog_object_id | VARCHAR | NULLABLE | Catalog object ID |
| location | VARCHAR | NULLABLE | Storage location |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- product_id → products.id

### package_dropship
**Purpose**: Package tracking for dropship orders.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| tracking_number | VARCHAR | NULLABLE | Package tracking number |
| carrier | VARCHAR | NULLABLE | Shipping carrier |
| status | TINYINT | DEFAULT 0 | Package status |
| note | VARCHAR | DEFAULT '' | Package notes |
| user_id | BIGINT | NOT NULL | Owner user ID |
| bill | VARCHAR | DEFAULT '' | Bill information |
| packing_slip | VARCHAR | DEFAULT '' | Packing slip |
| receive_date | DATETIME | NULLABLE | Package receive date |
| total_price | DECIMAL | DEFAULT 0 | Total package price |
| sync_inventory | TINYINT | DEFAULT 0 | Inventory sync status |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

---

## Support System

### supports
**Purpose**: Support tickets for regular orders.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | INT | NULLABLE | User who created ticket |
| order_id | INT | NULLABLE | Related order ID |
| status | INT | NULLABLE | Ticket status |
| subject | VARCHAR | NULLABLE | Ticket subject |
| image_link | VARCHAR | NULLABLE | Support image link |
| user_solved | INT | NULLABLE | User who solved ticket |
| user_reply | INT | NULLABLE, INDEXED | User who replied |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Indexes**: 
- user_reply

### support_chats
**Purpose**: Chat messages within support tickets.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| support_id | INT | NULLABLE | Foreign key to supports |
| user_id | INT | NULLABLE | Message author |
| message | TEXT | NULLABLE | Chat message content |
| image_link | VARCHAR | NULLABLE | Message image link |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- support_id → supports.id

### support_dropships
**Purpose**: Support tickets for dropship orders.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| status | TINYINT | DEFAULT 0 | Ticket status |
| subject | TINYINT | NOT NULL | Ticket subject code |
| order_dropship_id | BIGINT | NOT NULL | Foreign key to order_dropships |
| user_solved | BIGINT | NOT NULL | User who solved ticket |
| user_reply | BIGINT | NULLABLE | User who replied |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Indexes**: 
- Composite index on (status, order_dropship_id, user_solved)

**Relationships**:
- order_dropship_id → order_dropships.id

### support_chat_dropships
**Purpose**: Chat messages within dropship support tickets.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| support_dropship_id | BIGINT | NOT NULL, INDEXED | Foreign key to support_dropships |
| content | TEXT | NOT NULL | Chat message content |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Indexes**: 
- support_dropship_id

**Relationships**:
- support_dropship_id → support_dropships.id

---

## Financial Management

### transactions
**Purpose**: Financial transactions including payments, refunds, and withdrawals.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| seller_id | UNSIGNED BIGINT | NOT NULL | Seller user ID |
| order_id | UNSIGNED BIGINT | NULLABLE | Related order ID |
| amount | DOUBLE(8,2) | DEFAULT 0.00 | Transaction amount |
| fee | DOUBLE(8,2) | DEFAULT 0.00 | Transaction fee |
| remaining_balance | DOUBLE(8,2) | DEFAULT 0.00 | Balance after transaction |
| type | ENUM | DEFAULT 'pay' | Transaction type |
| status | ENUM | DEFAULT 'pending' | Transaction status |
| note | TEXT | NULLABLE | Transaction notes |
| approved_by | UNSIGNED BIGINT | NULLABLE | Approver user ID |
| transaction_id | VARCHAR | NULLABLE, UNIQUE, INDEXED | External transaction ID |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Enum Values**:
- type: 'payoneer', 'lianlian', 'pingpong', 'paypal', 'banktransfer', 'pay', 'refund', 'surcharge'
- status: 'pending', 'approved', 'cancelled'

**Indexes**: 
- transaction_id (unique)

**Relationships**:
- seller_id → users.id
- order_id → orders.id
- approved_by → users.id

---

## Tracking & Logistics

### tracking
**Purpose**: Package tracking information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| tracking_id | VARCHAR | NULLABLE | External tracking ID |
| tracking_link | VARCHAR | NULLABLE | Tracking URL |
| order_id | INT | NULLABLE | Related order ID |
| status | VARCHAR | NULLABLE | Tracking status |
| method | VARCHAR | NULLABLE | Shipping method |
| service | VARCHAR | NULLABLE | Shipping service |
| total_day | INT | NULLABLE | Total shipping days |
| time_create | DATETIME | NULLABLE | Tracking creation time |
| update_time | TIMESTAMP | NULLABLE | Last tracking update |
| ssk | VARCHAR | NULLABLE | SSK tracking code |
| push_tracking | TINYINT | DEFAULT 0 | Push tracking status |
| scan | TINYINT | DEFAULT 0 | Scan status |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- order_id → orders.id

---

## Store Management

### stores
**Purpose**: Store/shop information for sellers.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | UNSIGNED BIGINT | NOT NULL | Store owner user ID |
| name | VARCHAR | NULLABLE | Store name |
| type | VARCHAR | NOT NULL | Store type |
| status | VARCHAR | NULLABLE | Store status |
| api_key | TEXT | NULLABLE | Store API key |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

**Relationships**:
- user_id → users.id

---

## System Tables

### password_resets
**Purpose**: Password reset tokens.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| email | VARCHAR | INDEXED | User email |
| token | VARCHAR | | Reset token |
| created_at | TIMESTAMP | | Token creation time |

### personal_access_tokens
**Purpose**: API tokens for authentication.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| tokenable_type | VARCHAR | NOT NULL | Model type |
| tokenable_id | BIGINT | NOT NULL | Model ID |
| name | VARCHAR | NOT NULL | Token name |
| token | VARCHAR(64) | UNIQUE, NOT NULL | Token hash |
| abilities | TEXT | NULLABLE | Token permissions |
| last_used_at | TIMESTAMP | NULLABLE | Last usage time |
| expires_at | TIMESTAMP | NULLABLE | Token expiration |
| created_at | TIMESTAMP | | Record creation time |
| updated_at | TIMESTAMP | | Record update time |

### sessions
**Purpose**: User session storage.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | VARCHAR | PRIMARY KEY | Session ID |
| user_id | INT | NULLABLE, INDEXED | Associated user |
| ip_address | VARCHAR(45) | NULLABLE | Client IP |
| user_agent | TEXT | NULLABLE | Client user agent |
| payload | TEXT | NOT NULL | Session data |
| last_activity | INT | NOT NULL | Last activity timestamp |

### failed_jobs
**Purpose**: Failed queue job tracking.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| uuid | VARCHAR | UNIQUE | Job UUID |
| connection | TEXT | NOT NULL | Queue connection |
| queue | TEXT | NOT NULL | Queue name |
| payload | LONGTEXT | NOT NULL | Job payload |
| exception | LONGTEXT | NOT NULL | Exception details |
| failed_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Failure time |

### jobs
**Purpose**: Queued job storage.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| queue | VARCHAR | INDEXED | Queue name |
| payload | LONGTEXT | NOT NULL | Job payload |
| attempts | TINYINT UNSIGNED | NOT NULL | Attempt count |
| reserved_at | INT UNSIGNED | NULLABLE | Reserved timestamp |
| available_at | INT UNSIGNED | NOT NULL | Available timestamp |
| created_at | INT UNSIGNED | NOT NULL | Creation timestamp |

---

## Key Relationships Summary

### Primary Relationships
1. **users** → **stores** (1:N) - Users can own multiple stores
2. **users** → **orders** (1:N) - Users can have multiple orders as sellers
3. **users** → **order_dropships** (1:N) - Users can have multiple dropship orders
4. **orders** → **order_items** (1:N) - Orders contain multiple items
5. **order_dropships** → **order_item_dropships** (1:N) - Dropship orders contain multiple items
6. **order_dropships** → **customer_dropships** (1:1) - Each dropship order has customer info
7. **products** → **product_variants** (1:N) - Products have multiple variants
8. **products** → **product_variant_dropships** (1:N) - Products have dropship variants
9. **orders** → **supports** (1:N) - Orders can have multiple support tickets
10. **order_dropships** → **support_dropships** (1:N) - Dropship orders can have support tickets
11. **supports** → **support_chats** (1:N) - Support tickets contain chat messages
12. **support_dropships** → **support_chat_dropships** (1:N) - Dropship support tickets contain chats
13. **users** → **transactions** (1:N) - Users have transaction history
14. **orders** → **tracking** (1:N) - Orders can have tracking information

### Authorization System
- **users** → **roles** (N:1) - Users have assigned roles
- **roles** ↔ **permissions** (N:N) - Roles have multiple permissions via permission_role pivot table

This schema supports a comprehensive e-commerce fulfillment platform with separate regular and dropship order workflows, integrated support systems, financial tracking, and role-based access control.