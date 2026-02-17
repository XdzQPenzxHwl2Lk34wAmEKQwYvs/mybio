<?php
include ("server.php");
if (checkLogin() == false)
{
    header("location: login.php");
    exit();
}

function fnx($page)
{
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

// Fetch licenses for this program that belong to resellers of this user
$check_license = mysqli_query($con, "SELECT * FROM license WHERE PROGRAM='" . mysqli_real_escape_string($con, $_SESSION['program']) . "' AND RESELLER_ID IS NOT NULL AND RESELLER_ID != 0");
$res_b = array();
while($license_row = mysqli_fetch_assoc($check_license)) {
    $res_b[] = $license_row;
}

// Handle license deletion
if (isset($_POST['delete'])){
    mysqli_query($con, "DELETE FROM `license` WHERE `RESELLER_ID` = '" . $_SESSION['ID'] . "' AND `PROGRAM` = '" . mysqli_real_escape_string($con, $_SESSION['program']) . "' AND `id` = '" . mysqli_real_escape_string($con, $_POST['delete']) . "'");
    fnx("Resellerlicense.php");
}

// Handle HWID reset
if (isset($_POST['reset'])){
    mysqli_query($con, "UPDATE `license` SET `HWID` = 'Waiting For User' WHERE `id` = '" . mysqli_real_escape_string($con, $_POST['reset']) . "' AND `RESELLER_ID` = '" . $_SESSION['ID'] . "'");
    fnx("Resellerlicense.php");
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
    <title>Reseller Licenses - undetect.space</title>
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

        .logo span {
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }

        .btn-outline-primary {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border);
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

        code {
            background-color: var(--secondary);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            border: 1px solid var(--border);
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
            <div class="logo"><span>undetect</span><span class="primary"></span></div>
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
                <a href="License.php" class="nav-link">
                    <i class="fas fa-key"></i>
                    <span>All Licenses</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Resellerlicense.php" class="nav-link active">
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
            <div class="page-title">Reseller Licenses</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <img src="<?php echo !empty($row1['profile_pic']) ? htmlspecialchars($row1['profile_pic']) : '../assets/images/faces/face15.jpg'; ?>" alt="User">
                <button class="btn btn-danger" id="logout_button">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </div>
        </nav>

        <div class="content">
            <h1 class="page-title">Reseller License Management</h1>

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
                    <div class="stat-title">Total Reseller Licenses</div>
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

            <!-- Reseller Licenses Table -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Reseller Licenses</div>
                    <div>Manage licenses created by your resellers</div>
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
                                    <th>Banned</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($res_b)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; color: var(--text-muted);">No reseller licenses found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($res_b as $license): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($license['id']); ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($license['KEY']); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($license['HWID']); ?></td>
                                            <td><?php echo htmlspecialchars($license['EXPIRY']); ?></td>
                                            <td><?php echo htmlspecialchars($license['LEVEL']); ?></td>
                                            <td><?php echo htmlspecialchars($license['DURATION']); ?></td>
                                            <td>
                                                <?php if (isset($license['BANNED']) && $license['BANNED'] == "Yes"): ?>
                                                    <span class="badge badge-danger">Banned</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <?php if (isset($license['HWID']) && $license['HWID'] != "Waiting For User"): ?>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="reset" value="<?php echo htmlspecialchars($license['id']); ?>">
                                                            <button type="submit" class="btn btn-info btn-sm">
                                                                <i class="fas fa-redo"></i> <span>Reset HWID</span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this license?')">
                                                        <input type="hidden" name="delete" value="<?php echo htmlspecialchars($license['id']); ?>">
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

        <footer>
            <div>Copyright © <?php echo date("Y"); ?> <a href="https://undetect.space/" target="_blank">undetect.space</a>. All rights reserved.</div>
        </footer>
    </div>

    <script>
        // --- Background Animation Scripts ---
        function createAnimatedBackground() {
            const bg = document.getElementById('animatedBg');
            if (!bg) return;
            // Mouse move effect for radial gradient
            document.addEventListener('mousemove', (e) => {
                const x = (e.clientX / window.innerWidth) * 100;
                const y = (e.clientY / window.innerHeight) * 100;
                // Use pseudo-element via CSS variables (requires adjustment in CSS)
                // Or create a separate element for the radial effect
                // Simpler: Just move the grid slightly with mouse for parallax effect
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
                face.style.left = Math.random() * 95 + '%'; // Slightly less than 100% to avoid scrollbar
                face.style.top = Math.random() * 95 + '%';
                face.style.fontSize = (Math.random() * 10 + 18) + 'px'; // Random size
                face.style.animationDelay = (Math.random() * 3) + 's';
                container.appendChild(face);
            }
        }
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