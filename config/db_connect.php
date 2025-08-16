<!-- <?php
// Database connection
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Get database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = connectDB();
    }
    
    return $conn;
}

/**
 * Get user initials from full name
 */
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= mb_substr($word, 0, 1);
            
            if (strlen($initials) >= 2) {
                break;
            }
        }
    }
    
    return strtoupper($initials);
}

/**
 * تنفيذ استعلام SQL مع معلمات آمنة
 * 
 * @param string $sql استعلام SQL
 * @param array $params معلمات الاستعلام
 * @return mysqli_result|bool نتيجة الاستعلام أو false في حالة الخطأ
 */
function executeQuery($sql, $params = []) {
    $conn = getDBConnection();
    
    // التحقق من اتصال قاعدة البيانات
    if ($conn->connect_error) {
        error_log("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
        return false;
    }
    
    // إعداد الاستعلام
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("خطأ في إعداد الاستعلام: " . $conn->error);
        return false;
    }
    
    // ربط المعلمات إذا وجدت
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // افتراضيًا كلها strings
        
        // يمكنك تحسين نوع البيانات إذا لزم الأمر
        $stmt->bind_param($types, ...$params);
    }
    
    // تنفيذ الاستعلام
    if (!$stmt->execute()) {
        error_log("خطأ في تنفيذ الاستعلام: " . $stmt->error);
        return false;
    }
    
    // الحصول على النتائج لاستعلامات SELECT
    $result = $stmt->get_result();
    
    // إذا كان الاستعلام ليس SELECT (مثل INSERT, UPDATE)
    if ($result === false) {
        // إرجاع عدد الصفوف المتأثرة
        return $stmt->affected_rows > 0;
    }
    
    return $result;
}
// Get single row
function fetchRow($sql, $params = []) {
    $result = executeQuery($sql, $params);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}
/**
 * جلب جميع الصفوف من نتيجة استعلام SQL
 *
 * @param string $sql استعلام SQL مع مكان للمعاملات (?)
 * @param array $params مصفوفة المعلمات لربطها بالاستعلام
 * @return array مصفوفة تحتوي على جميع الصفوف الناتجة أو مصفوفة فارغة إذا لم توجد نتائج
 * @throws Exception إذا فشل تنفيذ الاستعلام
 */
function fetchAll($sql, $params = []) {
    try {
        // تنفيذ الاستعلام
        $result = executeQuery($sql, $params);
        
        // إذا فشل الاستعلام
        if ($result === false) {
            throw new Exception("فشل تنفيذ الاستعلام: " . ($GLOBALS['conn']->error ?? 'خطأ غير معروف'));
        }
        
        $rows = [];
        
        // جلب جميع الصفوف إذا كان الاستعلام من نوع SELECT
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        
        return $rows;
    } catch (Exception $e) {
        // تسجيل الخطأ للسجلات
        error_log("خطأ في fetchAll: " . $e->getMessage());
        
        // يمكنك إما إعادة مصفوفة فارغة أو إعادة طرح الاستثناء
        // حسب متطلبات تطبيقك
        return [];
        
        // أو لإجبار التطبيق على التعامل مع الخطأ:
        // throw $e;
    }
}
// Insert data and return ID
function insertData($sql, $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Build the types string
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Create references array with types as first element
        $bindParams[] = $types;
        
        // Add references to each parameter
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        
        // Bind parameters using references
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        return $conn->insert_id;
    }
    
    return false;
}

// Update data
function updateData($sql, $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        // Build the types string
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Create references array with types as first element
        $bindParams[] = $types;
        
        // Add references to each parameter
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        
        // Bind parameters using references
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    
    return $stmt->affected_rows;
}
