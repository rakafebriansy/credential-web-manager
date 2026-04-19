<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Database Structure</h1>
        
        <?php
        require_once 'config/database.php';
        
        echo "<h2>Step 1: Checking Current Structure</h2>";
        
        $check = mysqli_query($conn, "SHOW COLUMNS FROM websites");
        if ($check) {
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($col = mysqli_fetch_assoc($check)) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h2>Step 2: Fixing Structure</h2>";
        
        // Check if jenis_web exists
        $check_jenis = mysqli_query($conn, "SHOW COLUMNS FROM websites LIKE 'jenis_web'");
        
        if (mysqli_num_rows($check_jenis) == 0) {
            echo "<div class='info'>Adding missing columns...</div>";
            
            // Add columns
            $queries = [
                "ALTER TABLE websites ADD COLUMN jenis_web VARCHAR(100) DEFAULT NULL AFTER link_url",
                "ALTER TABLE websites ADD COLUMN letak_server VARCHAR(100) DEFAULT NULL AFTER jenis_web",
                "ALTER TABLE websites ADD COLUMN pic VARCHAR(100) DEFAULT NULL AFTER letak_server"
            ];
            
            foreach ($queries as $query) {
                if (mysqli_query($conn, $query)) {
                    echo "<div class='success'>✓ Query executed successfully</div>";
                } else {
                    echo "<div class='error'>✗ Error: " . mysqli_error($conn) . "</div>";
                }
            }
        } else {
            echo "<div class='info'>Columns already exist, skipping...</div>";
        }
        
        // Modify column sizes
        echo "<div class='info'>Increasing column sizes...</div>";
        
        $modify_queries = [
            "ALTER TABLE websites MODIFY COLUMN holding VARCHAR(255) NOT NULL",
            "ALTER TABLE websites MODIFY COLUMN link_url VARCHAR(500) NOT NULL"
        ];
        
        foreach ($modify_queries as $query) {
            if (mysqli_query($conn, $query)) {
                echo "<div class='success'>✓ Column size increased</div>";
            } else {
                echo "<div class='error'>✗ Error: " . mysqli_error($conn) . "</div>";
            }
        }
        
        echo "<h2>Step 3: Final Structure</h2>";
        
        $final = mysqli_query($conn, "SHOW COLUMNS FROM websites");
        if ($final) {
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($col = mysqli_fetch_assoc($final)) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<div class='success'><h3>✓ Database Fixed Successfully!</h3></div>";
        
        mysqli_close($conn);
        ?>
        
        <a href="websites" class="btn">Go to Websites Page</a>
        <a href="index" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>
