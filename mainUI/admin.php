<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
$isLoggedIn = isset($_SESSION['admin_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ProBidder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;600&display=swap');

        :root {
            --main-color: #443;
            --border-radius: 95% 4% 97% 5% / 4% 94% 3% 95%;
            --border-radius-hover: 4% 95% 6% 95% / 95% 4% 92% 5%;
            --border: .2rem solid var(--main-color);
            --border-hover: .2rem dashed var(--main-color);
        }

        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            border: none;
            text-decoration: none;
            text-transform: capitalize;
            transition: all .2s linear;
        }

        body {
            background: #f5f5f5;
            font-size: 16px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
            background: #fff;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1);
            padding: 1rem 5%;
            height: 70px;
        }

        .header .logo {
            color: var(--main-color);
            font-size: 1.8rem;
            font-weight: 600;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .login-form {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-form h2 {
            text-align: center;
            color: var(--main-color);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--main-color);
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--main-color);
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }

        .header .btn {
            width: auto;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            font-weight: 500;
            background: #dc3545;
        }

        .header .btn:hover {
            background: #c82333;
        }

        .btn:hover {
            background: #332;
        }

        .admin-dashboard {
            padding: 90px 5% 2rem;
            display: none;
        }

        .admin-dashboard.active {
            display: block;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: var(--main-color);
            font-size: 1.2rem;
            margin-bottom: 0.8rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1da03e;
        }

        .products-table {
            width: 100%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 2rem;
        }

        .products-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .products-table th {
            background: var(--main-color);
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .products-table tr:hover {
            background: #f9f9f9;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }

        .product-details {
            max-width: 200px;
        }

        .product-name {
            font-weight: 500;
            margin-bottom: 0.3rem;
            color: var(--main-color);
        }

        .product-description {
            font-size: 0.8rem;
            color: #666;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #e3fcef;
            color: #1da03e;
        }

        .status-ended {
            background: #ffe5e5;
            color: #dc3545;
        }

        .status-upcoming {
            background: #fff4e5;
            color: #ff9800;
        }

        .delete-btn {
            background: #dc3545;
            color: #fff;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem 3%;
            }

            .admin-dashboard {
                padding: 90px 3% 2rem;
            }

            .products-table th,
            .products-table td {
                padding: 0.8rem;
                font-size: 0.8rem;
            }

            .stat-card {
                padding: 1.2rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="#" class="logo">PROBIDDER ADMIN</a>
        <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="btn">Logout</a>
        <?php endif; ?>
    </header>

    <?php if (!$isLoggedIn): ?>
    <div class="login-container">
        <form class="login-form" method="POST" action="admin_login.php">
            <h2>Admin Login</h2>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocapitalize="off">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">Invalid credentials</div>
            <?php endif; ?>
        </form>
    </div>
    <?php else: ?>
    <div class="admin-dashboard active">
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Sales</h3>
                <div class="stat-value">
                    <?php
                    $query = "SELECT SUM(current_price) as total_sales FROM products WHERE status = 'ended'";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    echo '$' . number_format($row['total_sales'] ?? 0, 2);
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Commission Revenue</h3>
                <div class="stat-value">
                    <?php
                    $query = "SELECT SUM(current_price * 0.05) as commission FROM products WHERE status = 'ended'";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    echo '$' . number_format($row['commission'] ?? 0, 2);
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="stat-value">
                    <?php
                    $query = "SELECT COUNT(*) as total_products FROM products";
                    $result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($result);
                    echo $row['total_products'] ?? 0;
                    ?>
                </div>
            </div>
        </div>

        <div class="products-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Image</th>
                        <th style="width: 250px;">Product Details</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 120px;">Sold Price</th>
                        <th style="width: 120px;">Commission</th>
                        <th style="width: 150px;">Buyer</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.*, 
                             CASE 
                                WHEN p.status = 'ended' THEN u.username 
                                ELSE NULL 
                             END as buyer_name 
                             FROM products p 
                             LEFT JOIN (
                                SELECT b1.* 
                                FROM bids b1
                                INNER JOIN (
                                    SELECT product_id, MAX(amount) as max_amount
                                    FROM bids
                                    GROUP BY product_id
                                ) b2 ON b1.product_id = b2.product_id AND b1.amount = b2.max_amount
                             ) b ON p.id = b.product_id 
                             LEFT JOIN users u ON b.user_id = u.id 
                             GROUP BY p.id
                             ORDER BY p.created_at DESC";
                    
                    $result = mysqli_query($conn, $query);
                    
                    if (!$result) {
                        echo "<tr><td colspan='7'>Error: " . mysqli_error($conn) . "</td></tr>";
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $commission = $row['status'] === 'ended' ? $row['current_price'] * 0.05 : 0;
                            $statusClass = 'status-' . $row['status'];
                            
                            // Handle image data
                            $imageData = $row['image'];
                            $imageSrc = '';
                            
                            // Check if the image data is base64 encoded
                            if (strpos($imageData, 'data:image') === 0) {
                                $imageSrc = $imageData; // Already base64 encoded
                            } else {
                                // If it's binary data, convert to base64
                                $imageSrc = 'data:image/jpeg;base64,' . base64_encode($imageData);
                            }
                            
                            echo "<tr>";
                            echo "<td><img src='{$imageSrc}' alt='{$row['name']}' class='product-image'></td>";
                            echo "<td class='product-details'>";
                            echo "<div class='product-name'>{$row['name']}</div>";
                            echo "<div class='product-description'>{$row['description']}</div>";
                            echo "</td>";
                            echo "<td><span class='status-badge {$statusClass}'>{$row['status']}</span></td>";
                            echo "<td>" . ($row['status'] === 'ended' ? '$' . number_format($row['current_price'], 2) : '-') . "</td>";
                            echo "<td>" . ($row['status'] === 'ended' ? '$' . number_format($commission, 2) : '-') . "</td>";
                            echo "<td>" . ($row['status'] === 'ended' ? ($row['buyer_name'] ?? '-') : '-') . "</td>";
                            echo "<td><button class='delete-btn' onclick='deleteProduct({$row['id']})'>Delete</button></td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                fetch('delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product deleted successfully');
                        location.reload();
                    } else {
                        alert('Error deleting product: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting product. Please try again.');
                });
            }
        }
    </script>
</body>
</html> 