<?php

/* =========================================================
   SHARED HELPERS - Furnishop
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Admin CSRF (same session key the other admin pages already use) */
if (!function_exists('admin_csrf_token')) {
    function admin_csrf_token(): string
    {
        if (empty($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf'];
    }
}

if (!function_exists('admin_csrf_valid')) {
    function admin_csrf_valid(?string $token): bool
    {
        return !empty($_SESSION['admin_csrf'])
            && is_string($token)
            && hash_equals($_SESSION['admin_csrf'], $token);
    }
}

/* =========================================================
   CUSTOMER CSRF (login / register forms)
========================================================= */
if (!function_exists('customer_csrf_token')) {
    function customer_csrf_token(): string
    {
        if (empty($_SESSION['customer_csrf'])) {
            $_SESSION['customer_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['customer_csrf'];
    }
}

if (!function_exists('customer_csrf_valid')) {
    function customer_csrf_valid(?string $token): bool
    {
        return !empty($_SESSION['customer_csrf'])
            && is_string($token)
            && hash_equals($_SESSION['customer_csrf'], $token);
    }
}

/* =========================================================
   CUSTOMER LOGIN GUARD
   Call near the top of every page that needs a logged-in
   customer (after Config/database.php is loaded).
   - Not logged in            -> redirect to login.php
   - Account deleted/inactive -> log out and redirect
========================================================= */
if (!function_exists('require_customer_login')) {
    function require_customer_login(mysqli $connect, ?string $redirectAfterLogin = null): int
    {
        if (!isset($_SESSION['user_id'])) {
            if ($redirectAfterLogin !== null) {
                $_SESSION['redirect_after_login'] = $redirectAfterLogin;
            }
            header("Location: login.php");
            exit;
        }

        $uid  = (int) $_SESSION['user_id'];
        $stmt = mysqli_prepare($connect, "SELECT id FROM users WHERE id = ? AND status = 'Active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $ok = mysqli_stmt_num_rows($stmt) === 1;
        mysqli_stmt_close($stmt);

        if (!$ok) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            header("Location: login.php");
            exit;
        }

        return $uid;
    }
}

/* =========================================================
   UPDATE ORDER STATUS (keeps stock in sync)
   - Moving an order TO Cancelled returns its items to stock.
   - Moving an order OUT of Cancelled takes the stock again
     (and refuses if there is not enough).
   Returns [bool success, string message].
========================================================= */
if (!function_exists('admin_update_order_status')) {
    function admin_update_order_status(mysqli $connect, int $orderId, string $newStatus): array
    {
        $valid = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

        if ($orderId <= 0 || !in_array($newStatus, $valid, true)) {
            return [false, 'Invalid order or status.'];
        }

        mysqli_begin_transaction($connect);

        try {
            $stmt = mysqli_prepare($connect, "SELECT status FROM orders WHERE id = ? FOR UPDATE");
            mysqli_stmt_bind_param($stmt, "i", $orderId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$row) {
                throw new Exception('Order not found.');
            }

            $oldStatus = $row['status'];

            if ($oldStatus === $newStatus) {
                mysqli_commit($connect);
                return [true, 'Status unchanged.'];
            }

            $cancelling = ($newStatus === 'Cancelled' && $oldStatus !== 'Cancelled');
            $reopening  = ($oldStatus === 'Cancelled' && $newStatus !== 'Cancelled');

            if ($cancelling || $reopening) {
                $items = mysqli_prepare($connect, "SELECT product_id, product_name, quantity FROM order_items WHERE order_id = ?");
                mysqli_stmt_bind_param($items, "i", $orderId);
                mysqli_stmt_execute($items);
                $ires = mysqli_stmt_get_result($items);
                $rows = [];
                while ($ires && ($r = mysqli_fetch_assoc($ires))) {
                    $rows[] = $r;
                }
                mysqli_stmt_close($items);

                foreach ($rows as $it) {
                    $pid = (int) $it['product_id'];
                    $qty = (int) $it['quantity'];

                    if ($cancelling) {
                        $u = mysqli_prepare($connect, "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                        mysqli_stmt_bind_param($u, "ii", $qty, $pid);
                    } else {
                        $u = mysqli_prepare($connect, "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
                        mysqli_stmt_bind_param($u, "iii", $qty, $pid, $qty);
                    }

                    mysqli_stmt_execute($u);
                    $affected = mysqli_stmt_affected_rows($u);
                    mysqli_stmt_close($u);

                    if ($reopening && $affected < 1) {
                        throw new Exception("Not enough stock to reopen this order for '" . $it['product_name'] . "'.");
                    }
                }
            }

            $upd = mysqli_prepare($connect, "UPDATE orders SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "si", $newStatus, $orderId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            mysqli_commit($connect);

            $msg = "Order #" . $orderId . " status updated to " . $newStatus . ".";
            if ($cancelling) {
                $msg .= " Items returned to stock.";
            }
            return [true, $msg];

        } catch (Throwable $e) {
            mysqli_rollback($connect);
            return [false, $e->getMessage()];
        }
    }
}
