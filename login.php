<?php
require 'db.php';

$err = '';

// Where to go after successful login
$next = $_GET['next'] ?? 'index.php';

// Only allow local PHP pages
if (!preg_match('/^[a-zA-Z0-9_-]+\.php$/', $next)) {
    $next = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Find user by email
    $st = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $st->execute([$email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    // Check password
    if ($u && password_verify($password, $u['password_hash'])) {

        // Save login session
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['user_name'] = $u['full_name'];
        $_SESSION['role'] = $u['role'];

        // Redirect
        $go = $_POST['next'] ?? 'index.php';

        if (!preg_match('/^[a-zA-Z0-9_-]+\.php$/', $go)) {
            $go = 'index.php';
        }

        header('Location: ' . $go);
        exit;

    } else {
        $err = 'Wrong email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Login – Myat Noe Fashion</title>

    <link rel="stylesheet" href="auth.css">

    <style>

        /* =========================
           PAGE ANIMATION
        ========================= */

        .sp-left {
            animation: slideL .8s cubic-bezier(.2,.8,.2,1) both;
        }

        .sp-right {
            animation: slideR .8s cubic-bezier(.2,.8,.2,1) both;
        }

        @keyframes slideL {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes slideR {
            from {
                opacity: 0;
                transform: translateX(50px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }


        /* =========================
           LEFT TEXT ANIMATION
        ========================= */

        .sp-logo {
            animation: fadeUp .7s ease .3s both;
        }

        .sp-left h1 {
            animation: fadeUp .7s ease .5s both;
        }

        .sp-left p {
            animation: fadeUp .7s ease .7s both;
        }

        @keyframes fadeUp {

            from {
                opacity: 0;
                transform: translateY(22px);
            }

            to {
                opacity: 1;
                transform: none;
            }

        }


        /* =========================
           CLOTH RACK ANIMATION
        ========================= */

        .rack i {

            transform-origin: bottom center;

            animation:
                grow 1s cubic-bezier(.2,.9,.3,1.2) both,
                floaty 4s ease-in-out 1.6s infinite;

        }

        .rack i:nth-child(1) {
            animation-delay: .5s, 1.6s;
        }

        .rack i:nth-child(2) {
            animation-delay: .65s, 2.0s;
        }

        .rack i:nth-child(3) {
            animation-delay: .8s, 2.4s;
        }

        .rack i:nth-child(4) {
            animation-delay: .95s, 2.8s;
        }

        .rack i:nth-child(5) {
            animation-delay: 1.1s, 3.2s;
        }

        @keyframes grow {

            from {
                scale: 1 0;
                opacity: 0;
            }

            to {
                scale: 1 1;
                opacity: 1;
            }

        }

        @keyframes floaty {

            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }

        }


        /* =========================
           FORM ANIMATION
        ========================= */

        .sp-form > * {
            animation: fadeUp .6s ease both;
        }

        .sp-form > *:nth-child(1) {
            animation-delay: .4s;
        }

        .sp-form > *:nth-child(2) {
            animation-delay: .5s;
        }

        .sp-form > *:nth-child(3) {
            animation-delay: .6s;
        }

        .sp-form > *:nth-child(4) {
            animation-delay: .7s;
        }

        .sp-form > *:nth-child(5) {
            animation-delay: .8s;
        }

        .sp-form > *:nth-child(6) {
            animation-delay: .9s;
        }

        .sp-form > *:nth-child(7) {
            animation-delay: 1s;
        }

        .sp-form > *:nth-child(8) {
            animation-delay: 1.1s;
        }

        .sp-form > *:nth-child(9) {
            animation-delay: 1.2s;
        }

        .sp-form > *:nth-child(10) {
            animation-delay: 1.3s;
        }


        /* =========================
           ERROR MESSAGE
        ========================= */

        .sp-form > .err {
            animation: shake .5s ease both;
            animation-delay: 0s;
        }

        @keyframes shake {

            0%, 100% {
                transform: translateX(0);
            }

            20%, 60% {
                transform: translateX(-8px);
            }

            40%, 80% {
                transform: translateX(8px);
            }

        }


        /* =========================
           INPUT
        ========================= */

        .in {
            transition:
                transform .25s,
                box-shadow .25s;
        }

        .in:focus-within {

            transform: translateY(-2px);

            box-shadow:
                0 6px 18px
                rgba(220,60,100,.25);

        }


        /* =========================
           BUTTONS
        ========================= */

        .btn-main,
        .btn-g {

            transition:
                transform .2s,
                box-shadow .2s,
                filter .2s;

        }

        .btn-main:hover,
        .btn-g:hover {

            transform: translateY(-3px);

            box-shadow:
                0 10px 22px
                rgba(0,0,0,.15);

            filter: brightness(1.06);

        }

        .btn-main:active,
        .btn-g:active {

            transform: scale(.97);

        }


        /* =========================
           GOOGLE BUTTON
        ========================= */

        .btn-g {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            width: 100%;

            box-sizing: border-box;

            text-decoration: none;

            cursor: pointer;

        }


        /* =========================
           LINK UNDERLINE
        ========================= */

        .alt a,
        .row a {

            position: relative;

            text-decoration: none;

        }

        .alt a::after,
        .row a::after {

            content: "";

            position: absolute;

            left: 0;

            bottom: -2px;

            width: 0;

            height: 2px;

            background: currentColor;

            transition: width .3s;

        }

        .alt a:hover::after,
        .row a:hover::after {

            width: 100%;

        }


        /* =========================
           REDUCED MOTION
        ========================= */

        @media (prefers-reduced-motion: reduce) {

            .rack i {
                animation: grow 1s both;
            }

        }

    </style>

</head>


<body>

<div class="sp">


    <!-- =========================
         LEFT SIDE
    ========================= -->

    <div class="sp-left">

        <a
            class="sp-logo"
            href="index.php"
        >
            Myat Noe Fashion
        </a>


        <h1>
            Good style<br>
            Good mood
        </h1>


        <p>
            Fashion is not just about clothes,
            it's about how you feel.
        </p>


        <div class="rack">

            <i></i>
            <i></i>
            <i></i>
            <i></i>
            <i></i>

        </div>

    </div>


    <!-- =========================
         RIGHT SIDE
    ========================= -->

    <div class="sp-right">

        <form
            method="post"
            class="sp-form"
        >

            <h2>Login</h2>


            <!-- Error message -->

            <?php if ($err): ?>

                <p class="err">
                    <?= htmlspecialchars($err) ?>
                </p>

            <?php endif; ?>


            <!-- Next page -->

            <input
                type="hidden"
                name="next"
                value="<?= htmlspecialchars($next) ?>"
            >


            <!-- Email -->

            <label>
                Email
            </label>

            <div class="in">

                <span>✉</span>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required
                >

            </div>


            <!-- Password -->

            <label>
                Password
            </label>

            <div class="in">

                <span>🔒</span>

                <input
                    type="password"
                    name="password"
                    id="pw"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    onclick="togglePassword()"
                    aria-label="Show or hide password"
                >
                    👁
                </button>

            </div>


            <!-- Remember + Forgot -->

            <div class="row">

                <label
                    style="margin:0;font-weight:400"
                >

                    <input
                        type="checkbox"
                        name="remember"
                    >

                    Remember me

                </label>


                <a href="forgot_password.php">
                    Forgot Password?
                </a>

            </div>


            <!-- Login -->

            <button
                type="submit"
                class="btn-main"
            >
                Login
            </button>


            <!-- OR -->

            <p class="or">
                or
            </p>


            <!-- Google Login -->

            <a
                class="btn-g"
                href="google_login.php"
            >
                <span>G</span>
                Continue with Google
            </a>


            <!-- Register -->

            <p class="alt">

                Don't have an account?

                <a href="register.php">
                    Create one
                </a>

            </p>


        </form>

    </div>

</div>


<!-- =========================
     PASSWORD TOGGLE
========================= -->

<script>

function togglePassword() {

    const pw = document.getElementById('pw');

    if (pw.type === 'password') {

        pw.type = 'text';

    } else {

        pw.type = 'password';

    }

}

</script>

</body>

</html>