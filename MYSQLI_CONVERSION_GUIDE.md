# MySQLi Conversion Guide

## ✅ COMPLETED FILES (Already Converted)
- admin_auth.php
- admin_login.php
- login_process.php
- register_process.php
- register.php
- dashboard.php
- profile.php
- change_password.php
- update_profile.php
- reserve.php
- my_reservations.php
- cancel_reservation.php

## ⚠️ REMAINING FILES TO CONVERT (Manual Fix Needed)

### Files Still Using PDO Syntax:
1. **reservation_details.php**
2. **message.php**
3. **get_messages.php**
4. **send_message.php**
5. **auth.php** (if has DB queries)
6. **admin/dashboard.php**
7. **admin/parking_slots.php**
8. **admin/edit_slot.php**
9. **admin/delete_slot.php**
10. **admin/reservations.php**
11. **admin/update_reservation.php**
12. **admin/users.php**
13. **admin/messages.php**
14. **admin/send_admin_message.php**
15. **admin/get_admin_messages.php**

## MySQLi Conversion Pattern

### OLD PDO PATTERN:
```php
$stmt = $conn->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([$param]);
$result = $stmt->fetch();
```

### NEW MySQLi PATTERN:
```php
$sql = "SELECT * FROM table WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $param);  // "i" for integer, "s" for string
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();  // single row
// OR
$rows = $result->fetch_all(MYSQLI_ASSOC);  // multiple rows
```

## bind_param Type Mapping:
- "i" = integer
- "s" = string
- "d" = double
- "b" = blob

## How to Fix Remaining Files:

### Step 1: Find all PDO patterns
Search for: `$stmt->execute(` or `$stmt->fetch(` or `PDOException`

### Step 2: Replace with MySQLi equivalents
- `prepare()` stays the same
- `execute()` → `execute()` (no parameters)
- `bind_param()` → use before execute
- `get_result()` → after execute
- `fetch()` → `fetch_assoc()`
- `fetchAll()` → `fetch_all(MYSQLI_ASSOC)`
- `fetchColumn()` → use result count

### Step 3: Remove try-catch PDOException
MySQLi throws different exceptions - use general Exception or check return values

## Test After Each File:
1. Upload to server
2. Test the feature
3. Check for errors in browser
