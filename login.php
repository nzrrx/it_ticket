<?php
session_start();
include 'includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Prepared statement
    $stmt = $conn->prepare("SELECT id, email, password, role, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password hash
        if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];   // ✅ WAJIB
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role']; // jika ada role
    $_SESSION['login']   = true;

    session_regenerate_id(true);
    header("Location: index.php");
    exit;
}

    }

    $error = "Email atau password salah!";
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login | IT Ticketing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #1e68cfff,  #0284c7ff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0,0,0,.2);
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card .card-header {
            background: none;
            border: none;
            text-align: center;
        }

        .login-card .card-header h4 {
            font-weight: 600;
            color: #1449daff;
        }

        .form-control {
            border-radius: 10px;
        }

        .btn-login {
            background-color: #2d68e8ff;
            border: none;
            border-radius: 10px;
        }

        .btn-login:hover {
            background-color: #1d83dcff;
        }
        
        /* Loading button animation */
.btn-login.loading {
    pointer-events: none;
    background: linear-gradient(135deg, #0284c7, #0ea5e9);
}

.btn-login.loading span {
    display: none;
}

.btn-login.loading::after {
    content: "";
    width: 20px;
    height: 20px;
    border: 3px solid #ffffff;
    border-top: 3px solid transparent;
    border-radius: 50%;
    display: inline-block;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Form submit animation */
form.submitting {
    animation: formFade 0.4s ease forwards;
}

@keyframes formFade {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0.95;
        transform: scale(0.98);
    }
}
    </style>
</head>
<body>

<div class="card login-card p-4">
    <div class="card-header">
        <h4>🔐 Login Account</h4>
        <p class="text-muted mb-0">MICSTIX Ticketing System</p>
    </div>

    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger text-center">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control" placeholder="Masukkan email" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100 text-white mt-3" id="loginBtn">
    <span>Login</span>
</button>

        </form>
    </div>

    <div class="text-center mt-3">
        <small class="text-muted">
            Belum punya akun?
            <a class="text-decoration-none fw-semibold">Hubungi Administrator</a>
        </small>
    </div>
</div>
<script>
    const form = document.querySelector("form");
    const btn = document.getElementById("loginBtn");

    form.addEventListener("submit", function () {
        btn.classList.add("loading");
        form.classList.add("submitting");
    });
</script>

</body>
</html>
