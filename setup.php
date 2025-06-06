<?php
/**
 * TRENDY STATION - AUTO SETUP SCRIPT
 * Script tự động cài đặt và cấu hình hệ thống
 */

echo "🏪 THE TRENDY STATION - AUTO SETUP SCRIPT\n";
echo "==========================================\n\n";

// Kiểm tra môi trường
function checkEnvironment() {
    echo "🔍 Checking environment...\n";
    
    // Check PHP version
    $phpVersion = phpversion();
    echo "   PHP Version: $phpVersion ";
    if (version_compare($phpVersion, '7.4.0', '>=')) {
        echo "✅\n";
    } else {
        echo "❌ (Required PHP 7.4+)\n";
        exit(1);
    }
    
    // Check required extensions
    $requiredExtensions = ['pdo_mysql', 'json', 'mbstring'];
    foreach ($requiredExtensions as $ext) {
        echo "   Extension $ext: ";
        if (extension_loaded($ext)) {
            echo "✅\n";
        } else {
            echo "❌ (Missing)\n";
            exit(1);
        }
    }
    
    echo "\n";
}

// Cấu hình database
function setupDatabase() {
    echo "🗄️  Setting up database...\n";
    
    // Prompt for database credentials
    echo "Enter database configuration:\n";
    $host = readline("  Database host (localhost): ") ?: 'localhost';
    $username = readline("  Database username (root): ") ?: 'root';
    $password = readline("  Database password: ");
    $dbname = readline("  Database name (trendy_station): ") ?: 'trendy_station';
    
    try {
        // Test connection
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "   Database '$dbname' created/verified ✅\n";
        
        // Switch to the database
        $pdo->exec("USE `$dbname`");
        
        // Import SQL files in order
        $sqlFiles = [
            'database.sql',
            'database_customers.sql', 
            'database_imports.sql',
            'database_returns.sql'
        ];
        
        foreach ($sqlFiles as $file) {
            if (file_exists($file)) {
                echo "   Importing $file... ";
                $sql = file_get_contents($file);
                $pdo->exec($sql);
                echo "✅\n";
            } else {
                echo "   Warning: $file not found ⚠️\n";
            }
        }
        
        // Create config file
        $configContent = "<?php\n";
        $configContent .= "// Database Configuration\n";
        $configContent .= "\$host = '$host';\n";
        $configContent .= "\$dbname = '$dbname';\n";
        $configContent .= "\$username = '$username';\n";
        $configContent .= "\$password = '$password';\n\n";
        $configContent .= "try {\n";
        $configContent .= "    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password, [\n";
        $configContent .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
        $configContent .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
        $configContent .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
        $configContent .= "        PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"\n";
        $configContent .= "    ]);\n";
        $configContent .= "} catch (PDOException \$e) {\n";
        $configContent .= "    error_log(\"Database connection failed: \" . \$e->getMessage());\n";
        $configContent .= "    die(\"Lỗi kết nối database. Vui lòng kiểm tra cấu hình.\");\n";
        $configContent .= "}\n";
        $configContent .= "?>";
        
        file_put_contents('config/database.php', $configContent);
        echo "   Database config saved ✅\n";
        
    } catch (PDOException $e) {
        echo "   Database setup failed: " . $e->getMessage() . " ❌\n";
        exit(1);
    }
    
    echo "\n";
}

// Thiết lập thư mục và quyền
function setupDirectories() {
    echo "📁 Setting up directories...\n";
    
    $directories = [
        'config',
        'includes', 
        'pages',
        'ajax',
        'assets/css',
        'assets/js',
        'assets/images',
        'uploads',
        'backups'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "   Created directory: $dir ✅\n";
        } else {
            echo "   Directory exists: $dir ✅\n";
        }
    }
    
    // Set proper permissions
    chmod('config', 0755);
    chmod('uploads', 0777);
    chmod('backups', 0777);
    
    echo "\n";
}

// Tạo file .htaccess cơ bản
function setupHtaccess() {
    echo "🔧 Setting up .htaccess...\n";
    
    $htaccessContent = "# Trendy Station - Security & Performance\n\n";
    $htaccessContent .= "# Hide sensitive files\n";
    $htaccessContent .= "<Files \".env\">\n    Order allow,deny\n    Deny from all\n</Files>\n\n";
    $htaccessContent .= "<Files \"*.sql\">\n    Order allow,deny\n    Deny from all\n</Files>\n\n";
    $htaccessContent .= "<FilesMatch \"\\.(md|txt|log)\$\">\n    Order allow,deny\n    Deny from all\n</FilesMatch>\n\n";
    $htaccessContent .= "# Pretty URLs\n";
    $htaccessContent .= "RewriteEngine On\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccessContent .= "RewriteRule ^(.*)\$ index.php [QSA,L]\n\n";
    $htaccessContent .= "# Security headers\n";
    $htaccessContent .= "Header always set X-Content-Type-Options nosniff\n";
    $htaccessContent .= "Header always set X-Frame-Options DENY\n";
    $htaccessContent .= "Header always set X-XSS-Protection \"1; mode=block\"\n\n";
    $htaccessContent .= "# Gzip compression\n";
    $htaccessContent .= "<IfModule mod_deflate.c>\n";
    $htaccessContent .= "    AddOutputFilterByType DEFLATE text/plain text/html text/css application/javascript\n";
    $htaccessContent .= "</IfModule>\n";
    
    file_put_contents('.htaccess', $htaccessContent);
    echo "   .htaccess created ✅\n\n";
}

// Tạo dữ liệu mẫu
function createSampleData() {
    echo "📊 Creating sample data...\n";
    
    $choice = readline("Create sample data? (y/N): ");
    if (strtolower($choice) !== 'y') {
        echo "   Skipped sample data creation\n\n";
        return;
    }
    
    try {
        include 'config/database.php';
        
        // Sample products
        $products = [
            ["Áo thun nam basic", "Thời trang nam", 250000, 50, "Áo thun cotton 100% chất lượng cao"],
            ["Quần jeans nữ", "Thời trang nữ", 450000, 30, "Quần jeans skinny fit phong cách"],
            ["Giày sneaker unisex", "Giày dép", 680000, 25, "Giày thể thao đa năng"],
            ["Túi xách nữ", "Phụ kiện", 320000, 40, "Túi xách da PU cao cấp"],
            ["Mũ snapback", "Phụ kiện", 150000, 60, "Mũ hip-hop streetwear"]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO products (product_name, category, price, stock_quantity, description) VALUES (?, ?, ?, ?, ?)");
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        echo "   Sample products created ✅\n";
        
        // Sample customers
        $customers = [
            ["Nguyễn Văn An", "0901234567", "an@email.com", "123 Đường ABC, Q1, HCM"],
            ["Trần Thị Bình", "0907654321", "binh@email.com", "456 Đường XYZ, Q2, HCM"],
            ["Lê Văn Cường", "0909876543", "cuong@email.com", "789 Đường DEF, Q3, HCM"]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO customers (customer_name, phone, email, address) VALUES (?, ?, ?, ?)");
        foreach ($customers as $customer) {
            $stmt->execute($customer);
        }
        echo "   Sample customers created ✅\n";
        
        // Sample suppliers
        $suppliers = [
            ["Công ty TNHH Thời Trang ABC", "0123456789", "abc@supplier.com", "100 Đường Supply, Q1, HCM"],
            ["NCC Phụ Kiện XYZ", "0987654321", "xyz@supplier.com", "200 Đường Fashion, Q2, HCM"]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO suppliers (supplier_name, phone, email, address) VALUES (?, ?, ?, ?)");
        foreach ($suppliers as $supplier) {
            $stmt->execute($supplier);
        }
        echo "   Sample suppliers created ✅\n";
        
    } catch (Exception $e) {
        echo "   Error creating sample data: " . $e->getMessage() . " ❌\n";
    }
    
    echo "\n";
}

// Hiển thị thông tin hoàn thành
function showCompletionInfo() {
    echo "🎉 SETUP COMPLETED SUCCESSFULLY!\n";
    echo "================================\n\n";
    echo "📍 Access your system at: http://localhost/trendy_station\n\n";
    echo "📚 Quick Start Guide:\n";
    echo "   1. Open browser and go to: http://localhost/trendy_station\n";
    echo "   2. Start with Dashboard (Home page)\n";
    echo "   3. Use keyboard shortcuts for quick navigation:\n";
    echo "      - F1: Products   - F2: Sales\n";
    echo "      - F3: Imports    - F4: Customers\n";
    echo "      - F5: Refresh\n\n";
    echo "📖 Documentation:\n";
    echo "   - README.md: Main documentation\n";
    echo "   - KEYBOARD_SHORTCUTS.md: All keyboard shortcuts\n";
    echo "   - INSTALLATION_GUIDE.md: Detailed installation guide\n";
    echo "   - PRODUCTION_DEPLOYMENT.md: Production deployment guide\n\n";
    echo "🎯 Key Features:\n";
    echo "   ✅ Dashboard with statistics & charts\n";
    echo "   ✅ Product management (CRUD)\n";
    echo "   ✅ Sales management with auto-save\n";
    echo "   ✅ Import management\n";
    echo "   ✅ Customer management\n";
    echo "   ✅ Returns management\n";
    echo "   ✅ Reports & analytics\n";
    echo "   ✅ Print invoices & receipts\n";
    echo "   ✅ Real-time AJAX operations\n";
    echo "   ✅ Mobile responsive design\n\n";
    echo "❤️  Thank you for using The Trendy Station!\n";
    echo "🚀 Happy coding!\n\n";
}

// Chạy setup
try {
    checkEnvironment();
    setupDirectories();
    setupDatabase();
    setupHtaccess();
    createSampleData();
    showCompletionInfo();
} catch (Exception $e) {
    echo "Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
