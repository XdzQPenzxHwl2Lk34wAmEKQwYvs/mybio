<?php
// License.php
include ("server.php");

if (checkLogin() == false) {
    header("location: login.php");
    exit();
}

function fnx($page) {
    header("location: " . $page);
    exit();
}

// Security check - ensure program is set in session
if (empty($_SESSION['program'])) {
    fnx("index.php");
}

// Fetch program details
$check_program = mysqli_query($con, "SELECT * FROM programs WHERE PROGRAM_NAME='" . mysqli_real_escape_string($con, $_SESSION['program']) . "'");
$row = $check_program->fetch_assoc();

// Fetch user details
$check_everything = mysqli_query($con, "SELECT * FROM users WHERE USERNAME='" . mysqli_real_escape_string($con, $_SESSION['username']) . "'");
$row1 = mysqli_fetch_array($check_everything);

// Fetch licenses for this program and user
$check_license = mysqli_query($con, "SELECT * FROM license WHERE PROGRAM='" . mysqli_real_escape_string($con, $_SESSION['program']) . "' AND USER_ID='" . $_SESSION['ID'] . "'");
$res_b = array();
while($license_row = mysqli_fetch_assoc($check_license)) {
    $res_b[] = $license_row;
}

// Handle license generation with automatic expiry calculation
if (isset($_POST["generate_license"])) {
    // --- Get form data ---
    $quantity = intval($_POST['quantity'] ?? 1);
    $mask = $_POST['mask'] ?? 'XXXX-XXXX-XXXX-XXXX'; // Default mask
    $duration_days = intval($_POST['duration'] ?? 1); // Get duration in days

    // Ensure quantity is reasonable
    if ($quantity < 1) $quantity = 1;
    if ($quantity > 1000) $quantity = 1000; // Prevent abuse

    // --- Generate multiple licenses ---
    for ($i = 0; $i < $quantity; $i++) {
        // --- Generate license key based on mask ---
        $license_key = '';
        for ($j = 0; $j < strlen($mask); $j++) {
            if ($mask[$j] === '*') {
                // Replace * with random character
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $license_key .= $chars[rand(0, strlen($chars) - 1)];
            } else {
                // Keep the mask character (like '-')
                $license_key .= $mask[$j];
            }
        }

        // --- Calculate Expiry Date Automatically ---
        // Escape the license key for database insertion
        $license_key_esc = mysqli_real_escape_string($con, $license_key);

        // Handle "Lifetime" duration (e.g., 3650000 days = 10000 years)
        if ($duration_days >= 3650000) {
            // Set a very far future date (10,000 years)
            $future_date = new DateTime();
            $future_date->add(new DateInterval('P10000Y')); // Add 10,000 years
            $expiry_date_formatted = $future_date->format('Y-m-d H:i:s');
        } else if ($duration_days > 0) {
            // Calculate expiry date based on duration in days
            $future_date = new DateTime();
            $future_date->add(new DateInterval("P{$duration_days}D")); // Add specified number of days
            $expiry_date_formatted = $future_date->format('Y-m-d H:i:s');
        } else {
            // If duration is 0 or negative, set expiry to 1 year from now as a fallback
            $future_date = new DateTime();
            $future_date->add(new DateInterval("P365D"));
            $expiry_date_formatted = $future_date->format('Y-m-d H:i:s');
        }
        // --- End of Expiry Calculation ---

        // Escape the calculated expiry date
        $expiry_esc = mysqli_real_escape_string($con, $expiry_date_formatted);

        // Set default values for new license
        $hwid = "Waiting For User";
        $level = "0";
        $banned = "No";

        // Insert into database with calculated expiry date
        $insert_query = "INSERT INTO `license` (`KEY`, `HWID`, `EXPIRY`, `LEVEL`, `DURATION`, `PROGRAM`, `USER_ID`, `BANNED`)
                         VALUES ('$license_key_esc', '$hwid', '$expiry_esc', '$level', '$duration_days', '" . mysqli_real_escape_string($con, $_SESSION['program']) . "', '" . $_SESSION['ID'] . "', '$banned')";

        if (!mysqli_query($con, $insert_query)) {
            // Log the error but continue generating other licenses
            error_log("Error generating license: " . mysqli_error($con));
        }
    }

    // Refresh the page to show new licenses
    fnx("License.php");
}

// Handle license deletion
if (isset($_POST['delete'])){
    mysqli_query($con, "DELETE FROM `license` WHERE `USER_ID` = '" . $_SESSION['ID'] . "' AND `PROGRAM` = '" . mysqli_real_escape_string($con, $_SESSION['program']) . "' AND `ID` = '" . mysqli_real_escape_string($con, $_POST['delete']) . "'");
    fnx("License.php");
}

// Handle HWID reset
if (isset($_POST['reset'])){
    mysqli_query($con, "UPDATE `license` SET `HWID` = 'Waiting For User' WHERE `ID` = '" . mysqli_real_escape_string($con, $_POST['reset']) . "' AND `USER_ID` = '" . $_SESSION['ID'] . "'");
    fnx("License.php");
}

// Handle Purge Unused Licenses
// Note: Logic seems to check for 'Not Set' expiry, but new licenses have calculated expiry. Assuming logic checks for specific 'Not Set' value.
if (isset($_POST['PurgeUnused'])){
    mysqli_query($con, "DELETE FROM `license` WHERE `USER_ID` = '" . $_SESSION['ID'] . "' AND `PROGRAM` = '" . mysqli_real_escape_string($con, $_SESSION['program']) . "' AND `EXPIRY` = 'Not Set'");
    fnx("License.php");
}

// Handle Purge Used Licenses
// Note: Logic seems to check for 'Not Set' expiry, but new licenses have calculated expiry. Assuming logic checks for specific 'Not Set' value.
if (isset($_POST['PurgeUsed'])){
    mysqli_query($con, "DELETE FROM `license` WHERE `USER_ID` = '" . $_SESSION['ID'] . "' AND `PROGRAM` = '" . mysqli_real_escape_string($con, $_SESSION['program']) . "' AND `EXPIRY` != 'Not Set'");
    fnx("License.php");
}

// Handle Purge All Licenses
if (isset($_POST['PurgeAll'])){
    mysqli_query($con, "DELETE FROM `license` WHERE `USER_ID` = '" . $_SESSION['ID'] . "' AND `PROGRAM` = '" . mysqli_real_escape_string($con, $_SESSION['program']) . "'");
    fnx("License.php");
}

// Handle Reset All HWIDs
if (isset($_POST['ResetAll'])){
    mysqli_query($con, "UPDATE `license` SET `HWID` = 'Waiting For User' WHERE `USER_ID` = '" . $_SESSION['ID'] . "'");
    fnx("License.php");
}

// Handle Compensate All Licenses
if (isset($_POST['CompensateAll'])){
    // Get compensation values
    $compensation_days = intval($_POST['CompensateAllDay'] ?? 0);
    $compensation_hours = intval($_POST['CompensateAllHour'] ?? 0);

    // Select all licenses for this program and user that have a set expiry
    $check_lic1 = mysqli_query($con, "SELECT * FROM license WHERE PROGRAM='" . mysqli_real_escape_string($con, $_SESSION['program']) . "' AND `USER_ID` = '" . $_SESSION['ID'] . "' AND `EXPIRY` != 'Not Set'");

    if ($check_lic1) {
        while ($rowlics = mysqli_fetch_assoc($check_lic1)) {
            $currentid = $rowlics['ID'];
            // Parse the current expiry date
            $time = new DateTime($rowlics['EXPIRY']);
            // Modify the date by adding compensation
            if ($compensation_days != 0) {
                $time->modify("+{$compensation_days} days");
            }
            if ($compensation_hours != 0) {
                $time->modify("+{$compensation_hours} hours");
            }
            // Format the new expiry date
            $result = $time->format('Y-m-d H:i:s');
            // Update the license in the database
            mysqli_query($con, "UPDATE `license` SET `EXPIRY` = '" . mysqli_real_escape_string($con, $result) . "' WHERE ID = '" . mysqli_real_escape_string($con, $currentid) . "'");
        }
    }
    fnx("License.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
    <title>All Licenses - undetect.space</title>
    <meta name="robots" content="noindex">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400;500;600;700&display=swap');

        :root {
            /* Light Theme Colors */
            --primary: #2563eb; /* Blue */
            --primary-dark: #1d4ed8;
            --secondary: #f8fafc; /* Very Light Gray/Blue */
            --card-bg: #ffffff;
            --border: #cbd5e1; /* Light Gray/Blue */
            --text: #1e293b; /* Dark Gray/Blue */
            --text-muted: #64748b; /* Muted Gray */
            --success: #22c55e; /* Green */
            --danger: #dc2626; /* Red */
            --warning: #f59e0b; /* Amber */
            --info: #0ea5e9; /* Sky Blue */

            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

            /* Playful Elements */
            --cute-element-color-1: var(--primary);
            --cute-element-color-2: var(--danger);
            --cute-element-color-3: var(--success);
            --grid-color: rgba(37, 99, 235, 0.1); /* Blue with low opacity */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Pixelify Sans', monospace; /* Changed font */
        }

        body {
            background-color: #f1f5f9; /* Light background */
            color: var(--text);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden; /* Prevent horizontal scrollbar from animation */
            position: relative; /* For absolute positioning of background elements */
        }

        /* Playful Background Elements */
        .animated-background {
            position: fixed; /* Cover entire viewport */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* Behind content */
            overflow: hidden;
            pointer-events: none;
            background-image:
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 30s linear infinite;
        }

        @keyframes gridMove {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(50px, 50px);
            }
        }

        .cute-elements {
            position: fixed; /* Cover entire viewport */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1; /* Behind content, in front of grid */
        }

        .cute-face {
            position: absolute;
            font-size: 24px;
            color: var(--cute-element-color-1);
            animation: bounce 3s infinite ease-in-out;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
        }

        .cute-face:nth-child(even) {
            animation-delay: 1.5s;
            color: var(--cute-element-color-2);
        }

        .cute-face:nth-child(3n) {
            animation-delay: 3s;
            color: var(--cute-element-color-3);
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0px) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.1);
            }
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--card-bg);
            height: 100vh;
            position: fixed;
            border-right: 1px solid var(--border);
            z-index: 100;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-md);
        }

        .logo-container {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
        }

        .logo span.primary {
            color: var(--primary);
        }

        .nav-links {
            padding: 20px 0;
            flex: 1;
        }

        .nav-category {
            padding: 10px 20px 5px;
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-item {
            margin: 5px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            border-radius: 0 4px 4px 0;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(37, 99, 235, 0.1); /* Light blue highlight */
            color: var(--primary);
            border-left: 3px solid var(--primary);
        }

        .nav-link i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .user-profile {
            padding: 20px;
            text-align: center;
            border-top: 1px solid var(--border);
        }

        .user-profile img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            margin-bottom: 10px;
        }

        .user-profile .username {
            color: var(--text);
            font-weight: 600;
        }

        .user-profile .user-id {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px; /* Match sidebar width */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: relative; /* For z-index */
            z-index: 10; /* Above background elements */
        }

        /* Top Navigation */
        .top-nav {
            background-color: var(--card-bg);
            padding: 15px 30px;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
             font-weight: 500;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px; /* Slightly more rounded */
            border: 1px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit; /* Use Pixelify Sans */
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
            border-color: var(--success);
        }

        .btn-success:hover {
            background-color: #16a34a;
            border-color: #16a34a;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-warning {
            background-color: var(--warning);
            color: white;
            border-color: var(--warning);
        }

        .btn-warning:hover {
            background-color: #d97706;
            border-color: #d97706;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-info {
            background-color: var(--info);
            color: white;
            border-color: var(--info);
        }

        .btn-info:hover {
            background-color: #0284c7;
            border-color: #0284c7;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-light {
            background-color: var(--secondary);
            color: var(--text);
            border-color: var(--border);
        }

        .btn-light:hover {
            background-color: #e2e8f0;
            border-color: #94a3b8;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
             transform: translateY(-5px);
             box-shadow: var(--shadow-lg);
         }

        .stat-title {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
        }

        .stat-icon {
            float: right;
            font-size: 2.5rem;
            opacity: 0.3;
            color: var(--primary);
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            margin-bottom: 30px;
            overflow: hidden; /* Contain children */
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(241, 245, 249, 0.5); /* Slight background */
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text);
        }

        .card-body {
            padding: 25px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            background-color: rgba(241, 245, 249, 0.3); /* Very subtle header background */
        }

        td {
            color: var(--text);
        }

        tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.05); /* Light blue hover */
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: rgba(34, 197, 94, 0.15);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .badge-danger {
            background-color: rgba(220, 38, 38, 0.15);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .actions {
            display: flex;
            gap: 8px; /* Reduced gap */
            flex-wrap: wrap;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5); /* Darker overlay */
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
            animation: modalAppear 0.3s ease-out;
        }

        @keyframes modalAppear {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--text);
            font-weight: 600;
        }

        .close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.8rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .close:hover {
            background-color: var(--secondary);
            color: var(--text);
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .form-text {
             color: var(--text-muted);
             font-size: 0.875rem;
         }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
            border-top: 1px solid var(--border);
            margin-top: auto;
            background-color: var(--card-bg);
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden; /* Hide text */
            }

            .logo span:not(.primary) {
                display: none;
            }

            .logo span.primary {
                display: inline;
            }

            .nav-link span, .nav-category {
                display: none;
            }

            .nav-category {
                display: none;
            }

            .user-profile .username,
            .user-profile .user-id {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
                align-items: flex-start; /* Align buttons to start */
            }

            .top-nav {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

             .content {
                 padding: 20px 15px; /* Reduce padding on mobile */
             }

             .card-header {
                 flex-direction: column;
                 align-items: flex-start;
                 gap: 10px;
             }

             .modal-content {
                 width: 95%;
                 margin: 20px;
             }
        }

         @media (max-width: 480px) {
             .btn span { /* Hide text on very small screens if icon is present */
                 display: none;
             }
             .btn i {
                 margin-right: 0;
             }
             .actions .btn {
                 width: 100%;
                 justify-content: center;
             }
         }
    </style>
</head>
<body>
    <!-- Playful Background Elements -->
    <div class="animated-background" id="animatedBg"></div>
    <div class="cute-elements" id="cuteElements"></div>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo-container">
             <div class="logo"><span></span><span class="primary">undetect</span></div>
        </div>
        <div class="nav-links">


            <div class="nav-category">Main</div>
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="downloads.php" class="nav-link">
                    <i class="fas fa-download"></i>
                    <span>Downloads</span>
                </a>
            </div>

            <div class="nav-category">Management</div>
            <div class="nav-item">
                <a href="Settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="License.php" class="nav-link active">
                    <i class="fas fa-key"></i>
                    <span>All Licenses</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Resellerlicense.php" class="nav-link">
                    <i class="fas fa-key"></i>
                    <span>Reseller Licenses</span>
                </a>
            </div>

            <div class="nav-category">Security</div>
            <div class="nav-item">
                <a href="Bans.php" class="nav-link">
                    <i class="fas fa-ban"></i>
                    <span>Bans</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Logs.php" class="nav-link">
                    <i class="fas fa-server"></i>
                    <span>Logs</span>
                </a>
            </div>

            <div class="nav-category">User</div>
            <div class="nav-item">
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Resellers.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Resellers</span>
                </a>
            </div>
        </div>
        <div class="user-profile">
            <img src="<?php echo !empty($row1['profile_pic']) ? htmlspecialchars($row1['profile_pic']) : '../assets/images/faces/face15.jpg'; ?>" alt="Profile">
            <div class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <div class="user-id">User ID: <?php echo $_SESSION['ID']; ?></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="page-title">All Licenses</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <img src="<?php echo !empty($row1['profile_pic']) ? htmlspecialchars($row1['profile_pic']) : '../assets/images/faces/face15.jpg'; ?>" alt="User">
                <button class="btn btn-danger" id="logout_button">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </div>
        </nav>

        <div class="content">
            <h1 class="page-title">License Management</h1>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Account Package</div>
                    <div class="stat-value"><?php echo htmlspecialchars($row1[3]);?></div>
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Program</div>
                    <div class="stat-value"><?php echo htmlspecialchars($_SESSION['program']); ?></div>
                    <div class="stat-icon">
                        <i class="fas fa-server"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Total Licenses</div>
                    <div class="stat-value"><?php echo count($res_b); ?></div>
                    <div class="stat-icon">
                        <i class="fas fa-key"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Account Expiry</div>
                    <div class="stat-value"><?php echo htmlspecialchars($row1[4]);?></div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Generate License Button -->
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-primary" id="generateLicenseBtn">
                        <i class="fas fa-plus-circle"></i> Generate New License
                    </button>
                </div>
            </div>

            <!-- Licenses Table -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Licenses</div>
                    <div>Manage your program licenses</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>License Key</th>
                                    <th>HWID</th>
                                    <th>Expiry</th>
                                    <th>Level</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($res_b)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; color: var(--text-muted);">No licenses found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($res_b as $license): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($license['id'] ?? ''); ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($license['KEY'] ?? ''); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($license['HWID'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($license['EXPIRY'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($license['LEVEL'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($license['DURATION'] ?? ''); ?></td>
                                            <td>
                                                <?php if(isset($license['BANNED']) && $license['BANNED'] == "Yes"): ?>
                                                    <span class="badge badge-danger">Banned</span>
                                                <?php elseif(isset($license['EXPIRY']) && $license['EXPIRY'] != "Not Set"): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Unused</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <?php if (isset($license['HWID']) && $license['HWID'] != "Waiting For User"): ?>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="reset" value="<?php echo htmlspecialchars($license['id'] ?? ''); ?>">
                                                            <button type="submit" class="btn btn-info btn-sm">
                                                                <i class="fas fa-redo"></i> <span>Reset HWID</span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this license?')">
                                                        <input type="hidden" name="delete" value="<?php echo htmlspecialchars($license['id'] ?? ''); ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> <span>Delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate License Modal -->
        <div id="generateLicenseModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">Generate License</div>
                    <button class="close">&times;</button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="mask">Mask (use * for random characters)</label>
                            <input type="text" name="mask" id="mask" class="form-control" value="****-****-****-****" placeholder="e.g., ****-****-****-****">
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration (Days)</label>
                            <input type="number" name="duration" id="duration" class="form-control" min="1" max="3650000" value="365" placeholder="e.g., 365 for 1 year">
                            <small class="form-text text-muted">Enter duration in days. Use 3650000 for lifetime (10,000 years).</small>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" max="100" value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light close-btn">Cancel</button>
                        <button type="submit" name="generate_license" class="btn btn-primary">Generate</button>
                    </div>
                </form>
            </div>
        </div>

        <footer>
            <div>Copyright © <?php echo date("Y"); ?> <a href="https://undetect.space/" target="_blank">{ undetect.space }</a>. All rights reserved.</div>
        </footer>
    </div>

    <script>
        // --- Background Animation Scripts ---
        function createAnimatedBackground() {
            const bg = document.getElementById('animatedBg');
            if (!bg) return;

            // Mouse move effect for parallax
            document.addEventListener('mousemove', (e) => {
                const parallaxX = (e.clientX / window.innerWidth) * 10;
                const parallaxY = (e.clientY / window.innerHeight) * 10;
                bg.style.backgroundPosition = `${-parallaxX}px ${-parallaxY}px, ${50 - parallaxX/10}px ${50 - parallaxY/10}px`;
            });
        }

        function createCuteElements() {
            const container = document.getElementById('cuteElements');
            if (!container) return;

            container.innerHTML = ''; // Clear existing

            const faces = [':3', '<3', '^_^', ':D', 'uwu', '>:3', ':P', ':)', 'o.o', '^.^', '*.*', '&hearts;'];

            for (let i = 0; i < 20; i++) { // Increased number of elements
                const face = document.createElement('div');
                face.className = 'cute-face';
                face.textContent = faces[Math.floor(Math.random() * faces.length)];
                face.style.left = Math.random() * 95 + '%'; // Slightly less than 100%
                face.style.top = Math.random() * 95 + '%';
                face.style.fontSize = (Math.random() * 10 + 18) + 'px'; // Random size
                face.style.animationDelay = (Math.random() * 3) + 's';
                container.appendChild(face);
            }
        }

        // --- Modal Functionality ---
        const modal = document.getElementById('generateLicenseModal');
        const btn = document.getElementById('generateLicenseBtn');
        const closeButtons = document.querySelectorAll('.close, .close-btn');

        function openModal() {
            if (modal) modal.style.display = 'flex';
        }

        function closeModal() {
            if (modal) modal.style.display = 'none';
        }

        if (btn) btn.onclick = openModal;
        closeButtons.forEach(button => {
            if (button) button.onclick = closeModal;
        });

        // Close modal if clicked outside content
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        };

        // --- Logout Functionality ---
        document.getElementById('logout_button').onclick = function() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        };

        // --- Initialize Background on Load ---
        window.addEventListener('DOMContentLoaded', (event) => {
             createAnimatedBackground();
             createCuteElements();
         });
    </script>
</body>
</html>