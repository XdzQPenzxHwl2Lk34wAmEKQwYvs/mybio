<?php
ob_start();
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<script src="../assets/js/toastDemo.js"></script>
	<script src="../assets/vendors/jquery-toast-plugin/jquery.toast.min.js"></script>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
	<title>Register - undetect.space</title>
	<link href="../assets/vendors/jquery-toast-plugin/jquery.toast.min.css" rel="stylesheet">
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
            align-items: center;
            justify-content: center;
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
        /* Register Container */
        .register-container {
            width: 100%;
            max-width: 900px;
            margin: 20px;
            display: flex;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
        }
        .register-form-section {
            flex: 1;
            background-color: var(--card-bg);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .register-info-section {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .register-info-section h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .register-info-section p {
            font-size: 1rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 10px;
        }
        .page-subtitle {
            color: var(--text-muted);
            margin-bottom: 30px;
            font-size: 1rem;
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
        .input-group {
            display: flex;
        }
        .input-group-prepend {
            display: flex;
            align-items: center;
            padding: 0 12px;
            background-color: var(--secondary);
            border: 1px solid var(--border);
            border-right: none;
            border-radius: 6px 0 0 6px;
            color: var(--text-muted);
        }
        .form-control.with-prepend {
            border-radius: 0 6px 6px 0;
            border-left: none;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            border: 1px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            width: 100%;
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
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-danger {
            background-color: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: var(--danger);
        }
        .hidden {
            display: none;
        }
        footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
            width: 100%;
            margin-top: 30px;
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
            .register-container {
                flex-direction: column;
            }
            .register-form-section, .register-info-section {
                padding: 30px 20px;
            }
            .page-title {
                font-size: 1.8rem;
            }
        }
	</style>
</head>
<body>
    <!-- Playful Background Elements -->
    <div class="animated-background" id="animatedBg"></div>
    <div class="cute-elements" id="cuteElements"></div>

    <div class="register-container">
        <div class="register-form-section">
            <h1 class="page-title">Sign Up</h1>
            <p class="page-subtitle">Register your account</p>
            
            <form method="post">
                <div class="form-group">
                    <label for="type">Account Type</label>
                    <select name="type" id="type" class="form-control" disabled>
                        <option>Developer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="loginUsername">Username</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <i class="fas fa-user"></i>
                        </div>
                        <input class="form-control with-prepend" type="text" name="loginUsername" id="loginUsername" placeholder="Username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input class="form-control with-prepend" type="password" name="loginPassword" id="loginPassword" placeholder="Password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="loginKey">License Key</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <i class="fas fa-key"></i>
                        </div>
                        <input class="form-control with-prepend" type="text" name="loginKey" id="loginKey" placeholder="License" required>
                    </div>
                </div>
                
                <div id="textn" class="alert alert-danger hidden" role="alert">
                    <i class="fas fa-exclamation-circle"></i> Registration failed. Please check your details.
                </div>
                
                <button name="login" class="btn btn-primary" type="submit">
                    <i class="fas fa-user-plus"></i> Sign Up
                </button>
            </form>
        </div>
        
        <div class="register-info-section">
            <h2>Create Account</h2>
            <p>If you do not have a login you may sign up with your registration key here.</p>
            <p>Registration keys can only be given by administrators.</p>
        </div>
    </div>
    
    <footer>
        <div>Copyright © <?php echo date("Y"); ?> <a href="https://undetect.space/" target="_blank">undetect.space</a>. All rights reserved.</div>
    </footer>

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
        // --- Initialize Background on Load ---
        window.addEventListener('DOMContentLoaded', (event) => {
             createAnimatedBackground();
             createCuteElements();
         });
    </script>
</body>
</html>

<?php
include("server.php");
$error = false;
$error_array = array();

if(isset($_POST['login']))
{
    $key = $_POST['loginKey'];
    $check_everything = mysqli_query($con, "SELECT * FROM kkey WHERE keystring='". mysqli_real_escape_string($con, $key) ."'");
    
    if(mysqli_num_rows($check_everything) == 1){
        $row = mysqli_fetch_array($check_everything);
        $type = $row[2];
        
        // Set expiry date based on plan type
        $Date = date('Y-m-d');
        if ($type == "Unlimited Plan") {
            $new = date('Y-m-d', strtotime($Date. ' + 10 Year'));
        }
        elseif ($type == "Ultimate Plan") {
            $new = date('Y-m-d', strtotime($Date. ' + 180 Days'));
        }
        elseif ($type == "Starter Plan") {
            $new = date('Y-m-d', strtotime($Date. ' + 90 Days'));
        } else {
            $new = date('Y-m-d', strtotime($Date. ' + 30 Days')); // default
        }
        
        // Check if username already exists
        $username_check = mysqli_query($con, "SELECT * FROM users WHERE USERNAME='". mysqli_real_escape_string($con, $_POST['loginUsername']) ."'");
        if(mysqli_num_rows($username_check) > 0) {
            echo '<script>
                document.getElementById("textn").innerHTML = "<i class=\"fas fa-exclamation-circle\"></i> Username already exists!";
                document.getElementById("textn").classList.remove("hidden");
            </script>';
        } else {
            // Register new user
            $register_username = $_POST['loginUsername'];
            $register_password = password_hash($_POST['loginPassword'], PASSWORD_DEFAULT);
            $regkey = $_POST['loginKey'];
            
            $lastquery = mysqli_query($con, "INSERT INTO users (USERNAME, PASSWORD, ACCOUNT_TYPE, ACCOUNT_EXPIRY, REGISTRATION_KEY) VALUES ('".$register_username."', '". mysqli_real_escape_string($con, $register_password) ."', '".$type."', '".$new."', '".$regkey."')");
            
            if($lastquery) {
                // Delete used key
                $anotherlastquery = mysqli_query($con, "DELETE FROM kkey WHERE keystring='".$_POST['loginKey']."'");
                header('location: login.php');
                exit();
            } else {
                echo '<script>
                    document.getElementById("textn").innerHTML = "<i class=\"fas fa-exclamation-circle\"></i> Registration failed. Please try again.";
                    document.getElementById("textn").classList.remove("hidden");
                </script>';
            }
        }
    } else {
        echo '<script>
            document.getElementById("textn").innerHTML = "<i class=\"fas fa-exclamation-circle\"></i> Invalid license key!";
            document.getElementById("textn").classList.remove("hidden");
        </script>';
    }
}
?>