<?php
// rprofile.php
include ("rserver.php");

if (checkLogin() == false) {
    header("location: login.php");
    exit();
}

// Fetch reseller data
$check_everything = mysqli_query($con, "SELECT * FROM resellers WHERE USERNAME='" . mysqli_real_escape_string($con, $_SESSION['username']) . "'");
$row = mysqli_fetch_array($check_everything);

// Handle profile picture upload
if (isset($_POST['update_profile'])) {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif");

        if (in_array($file_extension, $allowed_extensions)) {
            // Create unique filename
            $new_filename = $_SESSION['ID'] . '_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                // Update database with new profile picture path
                $update_query = "UPDATE resellers SET profile_pic = '$target_file' WHERE ID = '" . $_SESSION['ID'] . "'";
                if (mysqli_query($con, $update_query)) {
                    $success_message = "Profile picture updated successfully!";
                    // Refresh reseller data
                    $check_everything = mysqli_query($con, "SELECT * FROM resellers WHERE USERNAME='" . mysqli_real_escape_string($con, $_SESSION['username']) . "'");
                    $row = mysqli_fetch_array($check_everything);
                } else {
                    $error_message = "Database error: Could not update profile picture.";
                }
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Profile - undetect.space</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Content Area */
        .content {
            flex: 1;
            padding: 30px;
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

        .profile-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-pic-container {
            position: relative;
            margin-bottom: 30px;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary);
            box-shadow: var(--shadow-lg);
        }

        .edit-overlay {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: var(--primary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }

        .edit-overlay:hover {
            background-color: var(--primary-dark);
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            width: 100%;
            max-width: 400px;
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

        .form-control[readonly] {
            background-color: #f1f5f9; /* Slightly different for readonly fields */
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            cursor: pointer;
            width: 100%;
            text-align: center;
            transition: all 0.2s;
        }

        .file-input-button:hover {
            background-color: #e2e8f0;
            border-color: #94a3b8;
        }

        .preview-container {
            margin-top: 20px;
            display: none;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            border: 2px dashed var(--border);
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--success);
        }

        .alert-danger {
            background-color: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: var(--danger);
        }

        /* Footer */
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

            .user-profile .username,
            .user-profile .user-id {
                display: none;
            }

            .main-content {
                margin-left: 70px;
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
         }
    </style>
</head>

<body>
    <!-- Playful Background Elements -->
    <div class="animated-background" id="animatedBg"></div>
    <div class="cute-elements" id="cuteElements"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo"><span>undetect</span><span class="primary"></span></div>
        </div>
        <div class="nav-links">
            <div class="nav-item">
                <a href="rindex.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-category">User</div>
            <div class="nav-item">
                <a href="rprofile.php" class="nav-link active">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>
        <div class="user-profile">
            <img src="<?php echo !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : '../assets/images/faces/face15.jpg'; ?>" alt="Profile">
            <div class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <div class="user-id">User ID: <?php echo $_SESSION['ID']; ?></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="page-title">Reseller Profile</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <img src="<?php echo !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : '../assets/images/faces/face15.jpg'; ?>" alt="Profile">
                <button class="btn btn-danger" id="logout_button">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </div>
        </nav>

        <!-- Content Area -->
        <div class="content">
            <h1 class="page-title">Reseller Profile</h1>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Profile Picture</div>
                    <div>Update your profile information</div>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="profile-container">
                        <div class="profile-pic-container">
                            <img class="profile-pic" src="<?php echo !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : '../assets/images/faces/face15.jpg'; ?>" alt="Profile Picture">
                            <div class="edit-overlay">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>

                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="profile_pic">Choose a new profile picture</label>
                                <div class="file-input-wrapper">
                                    <div class="file-input-button">
                                        <i class="fas fa-upload"></i> Select Image
                                    </div>
                                    <input type="file" name="profile_pic" id="profile_pic" accept="image/*" required>
                                </div>
                            </div>

                            <div class="preview-container" id="previewContainer">
                                <p>Preview:</p>
                                <img class="preview-image" id="previewImage" src="#" alt="Preview">
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> <span>Update Profile Picture</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Account Information</div>
                    <div>Your reseller account details</div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Program</label>
                        <input type="text" class="form-control" value="<?php echo isset($row[3]) ? htmlspecialchars($row[3]) : 'N/A'; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Account Balance</label>
                        <input type="text" class="form-control" value="$<?php echo isset($row[5]) ? htmlspecialchars($row[5]) : '0.00'; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>User ID</label>
                        <input type="text" class="form-control" value="<?php echo $_SESSION['ID']; ?>" readonly>
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

        // Preview image before upload
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Trigger file input when clicking on profile picture
        document.querySelector('.edit-overlay').addEventListener('click', function() {
            document.getElementById('profile_pic').click();
        });

        // --- Initialize Background on Load ---
        window.addEventListener('DOMContentLoaded', (event) => {
             createAnimatedBackground();
             createCuteElements();
         });
    </script>
</body>
</html>