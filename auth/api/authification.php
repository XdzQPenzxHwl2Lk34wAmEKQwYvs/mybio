<?php
// authification.php
// API endpoint for license authentication via POST request.
// Corrected for logs table column names and automatic expiry calculation.

header('Content-Type: application/json');

// --- 1. Include Database Connection ---
include ("../server.php"); // Adjust path as needed

if (!$con || $con->connect_errno) {
    // Database connection failed
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed.'
    ]);
    if ($con) {
        error_log("Database connection failed in authification.php: " . $con->connect_error);
    }
    exit();
}

// --- 2. Check for Required POST Data ---
if (!isset($_POST['key']) || !isset($_POST['hwid'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters: key, hwid'
    ]);
    $con->close();
    exit();
}

// --- 3. Sanitize Input Data ---
// It's crucial to sanitize data coming from user requests.
$license_key_input = trim($_POST['key']);
$client_hwid_input = trim($_POST['hwid']);

// Use prepared statements for database interaction (safer)
// For initial lookup, we still need the key to fetch data
$license_key = $con->real_escape_string($license_key_input);
$client_hwid = $con->real_escape_string($client_hwid_input);

// --- 4. Get Client IP Address ---
$client_ip = '';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $client_ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $client_ip = $_SERVER['REMOTE_ADDR'];
}

// --- 5. Query Database for License and Associated Program ---
// Join license and programs tables to get all necessary information in one query.
// Assumes license.PROGRAM links to programs.PROGRAM_NAME
$sql = "SELECT
            l.ID as license_id,
            l.KEY as license_key,
            l.PROGRAM as license_program_name, -- Links to p.PROGRAM_NAME
            l.USER_ID as license_user_id,
            l.HWID as license_hwid,
            l.EXPIRY as license_expiry,
            l.BANNED as license_banned,
            l.BANNED_REASON as license_ban_reason,
            l.LEVEL as license_level,
            l.DURATION as license_duration,
            p.PROGRAM_NAME as program_name,
            p.PROGRAM_ID as program_id,
            p.PROGRAM_USER as program_user,
            p.DISABLED as program_disabled
            -- Note: RESPONSE_* columns removed as they are not in your DESCRIBE output
        FROM license l
        JOIN programs p ON l.PROGRAM = p.PROGRAM_NAME
        WHERE l.KEY = '$license_key'";

$result = $con->query($sql);

if (!$result) {
    // Database query error
    echo json_encode([
        'success' => false,
        'error' => 'Database query error occurred.'
    ]);
    error_log("Database query error in authification.php: " . $con->error);
    $con->close();
    exit();
}

if ($result->num_rows == 0) {
    // License key not found in the database
    // Log this failed attempt (using corrected column names)
    $log_message = "Invalid Key Attempt for key: " . substr($license_key_input, 0, 10) . '...';
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        // Use a default or dummy program/user if not found
        $dummy_program = "Unknown_Program";
        $dummy_user_id = 0;
        $log_stmt->bind_param("siss", $dummy_program, $dummy_user_id, $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }

    echo json_encode([
        'success' => false,
        'error' => 'Invalid license key provided.'
    ]);
    $con->close();
    exit();
}

$license_data = $result->fetch_assoc();

// --- 6. Perform Authentication Checks ---

// --- Check 1: Is the associated program disabled? ---
if ($license_data['program_disabled'] == "Yes") {
    // Log attempt if needed
    $log_message = "Login Failed - Program Disabled";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }
    echo json_encode([
        'success' => false,
        'error' => 'The program associated with this license is currently disabled.'
    ]);
    $con->close();
    exit();
}

// --- Check 2: Has the license expired? ---
// --- FIX: Handle "Not Set" expiry correctly ---
$license_expiry_raw = $license_data['license_expiry'];
if ($license_expiry_raw === "Not Set" || $license_expiry_raw === "Not set" || empty($license_expiry_raw)) {
    // Decide how to handle "Not Set" expiry.
    // Option 1: Treat as expired.
    // Option 2: Treat as valid indefinitely (less common).
    // We'll treat it as an error requiring admin setup or having an unset expiry date.
    $log_message = "Login Failed - License expiry date is not configured correctly (Not Set).";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }

    echo json_encode([
        'success' => false,
        'error' => 'License expiry date is not configured correctly. Please contact support.'
    ]);
    $con->close();
    exit();
}

// Now that we know it's not "Not Set", try to parse it.
try {
    $current_time = new DateTime(); // Current server time
    $expiry_time = new DateTime($license_expiry_raw); // Parse the license expiry
} catch (Exception $e) {
    // If parsing fails for any other reason (e.g., invalid format like "2023-15-45")
    $log_message = "Login Failed - License expiry date format is invalid.";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }

    echo json_encode([
        'success' => false,
        'error' => 'License expiry date format is invalid. Please contact support.'
    ]);
    error_log("DateTime parsing error in authification.php for key {$license_key}: " . $e->getMessage());
    $con->close();
    exit();
}

// Now perform the actual expiry check
if ($current_time >= $expiry_time) {
    // Log expired attempt
    $log_message = "Login Failed - Expired";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }

    echo json_encode([
        'success' => false,
        'error' => 'License has expired.',
        'expired' => true // Optional flag for client
    ]);
    $con->close();
    exit();
}
// --- END OF EXPIRY CHECK FIX ---

// --- Check 3: Is the license banned? ---
if ($license_data['license_banned'] == "Yes") {
    // Log banned attempt
    $ban_reason_log = $license_data['license_ban_reason'] ?? 'No specific reason provided.';
    $log_message = "Login Failed - Banned: " . $ban_reason_log;
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }

    $ban_reason = $license_data['license_ban_reason'] ?? 'No specific reason provided.';
    echo json_encode([
        'success' => false,
        'error' => 'License is banned.',
        'ban_reason' => $ban_reason
    ]);
    $con->close();
    exit();
}

// --- Check 4: Does the HWID match? ---
// Handle the "Waiting For User" case for initial HWID assignment
$hwid_status = strtolower(trim($license_data['license_hwid'])); // Normalize for comparison

if ($hwid_status == "waiting for user") {
    // Assign the client's HWID to this license
    $update_hwid_stmt = $con->prepare("UPDATE license SET HWID = ? WHERE ID = ?");
    if ($update_hwid_stmt) {
        $update_hwid_stmt->bind_param("si", $client_hwid, $license_data['license_id']);
        if (!$update_hwid_stmt->execute()) {
            // Log update error
            $log_message = "Failed to register hardware ID.";
            $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
            if ($log_stmt) {
                $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
                $log_stmt->execute();
                $log_stmt->close();
            }

            echo json_encode([
                'success' => false,
                'error' => 'Failed to register hardware ID.'
            ]);
            error_log("Failed to update HWID in authification.php: " . $update_hwid_stmt->error);
            $update_hwid_stmt->close();
            $con->close();
            exit();
        }
        $update_hwid_stmt->close();
    } else {
        // Log prepare error
        $log_message = "Failed to prepare HWID update statement.";
        $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
        if ($log_stmt) {
            $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
            $log_stmt->execute();
            $log_stmt->close();
        }

        echo json_encode([
            'success' => false,
            'error' => 'Failed to prepare HWID update statement.'
        ]);
        error_log("Failed to prepare HWID update statement in authification.php: " . $con->error);
        $con->close();
        exit();
    }

    // --- Authentication Successful (Initial HWID Set) ---
    // Log the successful login with corrected column names
    $log_message = "Login Success (Initial HWID Set)";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    } // Ignore log failure for main flow

    // Fetch minimal user info (Placeholder - adjust if you have a users table)
    $username = "User_" . $license_data['license_user_id']; // Placeholder
    $account_type = "License Holder"; // Placeholder

    // Send success response with relevant data
    // Include the correctly formatted expiry date
    echo json_encode([
        'success' => true,
        'message' => 'Authentication successful. Hardware ID registered.',
        'data' => [
            'username' => $username,
            'account_type' => $account_type,
            'expiry_date' => $license_data['license_expiry'], // Send the raw string (now correctly set)
            'api_key' => '', // Fetch if you have a specific API key column for the user/license
            'license_level' => $license_data['license_level'] ?? '0',
            'program_name' => $license_data['program_name'],
            'license_key' => $license_data['license_key'] // Echo back the key if needed by client
        ]
    ]);
    $con->close();
    exit();

} else if ($license_data['license_hwid'] != $client_hwid) {
    // HWID mismatch - the key is locked to a different machine
    // Log invalid HWID attempt with corrected column names
    $log_message = "Login Failed - Invalid HWID";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }

    echo json_encode([
        'success' => false,
        'error' => 'Hardware ID does not match the one registered with this license.'
    ]);
    $con->close();
    exit();
}

// --- Final Check: Basic Key Match (redundant if HWID matched, but good for clarity) ---
if ($license_data['license_key'] == $license_key && $license_data['license_hwid'] == $client_hwid) {
    // --- Authentication Successful ---
    // Log the successful login with corrected column names
    $log_message = "Login Success";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    } // Ignore log failure for main flow

    // Fetch minimal user info (Placeholder - adjust if you have a users table)
    $username = "User_" . $license_data['license_user_id'];
    $account_type = "License Holder";

    // Send success response with relevant data
    // Include the correctly formatted expiry date
    echo json_encode([
        'success' => true,
        'message' => 'Authentication successful.',
        'data' => [
            'username' => $username,
            'account_type' => $account_type,
            'expiry_date' => $license_data['license_expiry'], // Send the raw string (now correctly set)
            'api_key' => '', // Fetch if you have a specific API key column for the user/license
            'license_level' => $license_data['license_level'] ?? '0',
            'program_name' => $license_data['program_name'],
            'license_key' => $license_data['license_key'] // Echo back the key if needed by client
        ]
    ]);
    $con->close();
    exit();
} else {
    // This case should ideally not be reached due to prior checks, but as a safeguard:
    // Log unexpected condition
    $log_message = "Login Failed - Unexpected condition during final check.";
    $log_stmt = $con->prepare("INSERT INTO logs (PROGRAM, USER_ID, log_message, ip_address) VALUES (?, ?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("siss", $license_data['program_name'], $license_data['license_user_id'], $log_message, $client_ip);
        $log_stmt->execute();
        $log_stmt->close();
    }

    echo json_encode([
        'success' => false,
        'error' => 'Authentication failed due to an unexpected condition.'
    ]);
    $con->close();
    exit();
}

// --- 7. Close Database Connection (Explicitly) ---
$con->close();

?>