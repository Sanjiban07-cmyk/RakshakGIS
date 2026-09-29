<?php

session_start();

require_once __DIR__ . "/config/database.php";

/*
|--------------------------------------------------------------------------
| If already logged in
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Login handling
|--------------------------------------------------------------------------
*/

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                name,
                username,
                password,
                email,
                role,
                status
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            if (!$user) {

                $error = "Invalid username or password.";

            } elseif (
                isset($user["status"]) &&
                strtoupper($user["status"]) !== "ACTIVE"
            ) {

                $error = "This account is currently inactive.";

            } elseif (!password_verify($password, $user["password"])) {

                $error = "Invalid username or password.";

            } else {

                /*
                |----------------------------------------------------------
                | Successful login
                |----------------------------------------------------------
                */

                session_regenerate_id(true);

                $_SESSION["user_id"] = (int) $user["id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"] ?? "";
                $_SESSION["role"] = $user["role"] ?? "USER";

                header("Location: index.php");
                exit;
            }

        } else {

            $error = "Unable to process login. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="RakshakGIS - Disaster Risk Assessment and Safe Relocation Planner"
    >

    <title>Login | Rakshak GIS</title>

    <link
        rel="icon"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cpath fill='%232563eb' d='M50 4 90 18v35c0 24-16 37-40 43C26 90 10 77 10 53V18z'/%3E%3Ctext x='50' y='67' text-anchor='middle' font-family='Arial' font-size='50' font-weight='900' fill='white'%3ER%3C/text%3E%3C/svg%3E"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Arial,
                sans-serif;

            background: #f8fafc;
            color: #1e293b;
        }

        button,
        input {
            font: inherit;
        }

        /* =========================================================
           PAGE
        ========================================================= */

        .login-page {

            min-height: 100vh;

            display: grid;

            grid-template-columns: 1.05fr .95fr;

            background: #f8fafc;
        }


        /* =========================================================
           LEFT VISUAL PANEL
        ========================================================= */

        .login-visual {

            position: relative;

            min-height: 100vh;

            overflow: hidden;

            display: flex;

            align-items: center;

            padding: 70px;

            color: white;

            background:
                radial-gradient(
                    circle at 75% 85%,
                    rgba(37,99,235,.55),
                    transparent 38%
                ),
                linear-gradient(
                    145deg,
                    #0b1533 0%,
                    #12245a 55%,
                    #1d4ed8 100%
                );
        }


        /* GRID */

        .login-visual::before {

            content: "";

            position: absolute;

            inset: 0;

            background-image:
                linear-gradient(
                    rgba(255,255,255,.055) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255,255,255,.055) 1px,
                    transparent 1px
                );

            background-size: 34px 34px;

            opacity: .65;
        }


        /* GLOW */

        .login-visual::after {

            content: "";

            position: absolute;

            width: 600px;
            height: 600px;

            right: -260px;
            bottom: -300px;

            border-radius: 50%;

            border: 1px solid rgba(255,255,255,.12);

            box-shadow:
                0 0 0 70px rgba(255,255,255,.025),
                0 0 0 140px rgba(255,255,255,.02);
        }


        .login-visual-content {

            position: relative;

            z-index: 2;

            width: 100%;

            max-width: 650px;

            margin: auto;
        }


        /* =========================================================
           BRAND
        ========================================================= */

        .login-brand {

            display: flex;

            align-items: center;

            gap: 13px;

            margin-bottom: 68px;
        }


        .login-brand-icon {

            width: 48px;
            height: 52px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            color: white;

            background:
                linear-gradient(
                    145deg,
                    #3b82f6,
                    #1d4ed8
                );

            border-radius: 13px;

            clip-path: polygon(
                50% 0%,
                92% 15%,
                92% 55%,
                82% 76%,
                50% 100%,
                18% 76%,
                8% 55%,
                8% 15%
            );

            box-shadow:
                0 10px 25px
                rgba(37,99,235,.35);
        }


        .login-brand-icon span {

            font-size: 23px;

            font-weight: 900;

            font-family: Arial, sans-serif;
        }


        .login-brand-name {

            font-size: 21px;

            line-height: 1;

            font-weight: 900;

            letter-spacing: .7px;

            color: #ffffff;
        }


        .login-brand-name span {

            color: #60a5fa;
        }


        .login-brand-subtitle {

            margin-top: 7px;

            color: #a9c1e8;

            font-size: 8px;

            line-height: 1;

            font-weight: 700;

            letter-spacing: 1.3px;
        }


        /* =========================================================
           HERO TEXT
        ========================================================= */

        .login-hero h1 {

            max-width: 620px;

            font-size: clamp(
                42px,
                4.4vw,
                64px
            );

            line-height: 1.02;

            letter-spacing: -2.5px;

            font-weight: 900;
        }


        .login-hero h1 span {

            color: #60a5fa;
        }


        .login-hero p {

            max-width: 570px;

            margin-top: 25px;

            color: #cbd5e1;

            font-size: 16px;

            line-height: 1.7;
        }


        /* =========================================================
           FEATURE CARDS
        ========================================================= */

        .login-features {

            margin-top: 42px;

            display: grid;

            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 14px;

            max-width: 570px;
        }


        .login-feature {

            min-height: 102px;

            padding: 17px;

            border: 1px solid
                rgba(255,255,255,.13);

            border-radius: 13px;

            background:
                rgba(255,255,255,.075);

            backdrop-filter: blur(10px);

            box-shadow:
                0 10px 25px
                rgba(0,0,0,.08);
        }


        .login-feature-icon {

            width: 31px;
            height: 31px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            color: #bfdbfe;

            background:
                rgba(96,165,250,.17);

            font-size: 16px;

            margin-bottom: 11px;
        }


        .login-feature-title {

            color: white;

            font-size: 12px;

            font-weight: 800;
        }


        .login-feature-text {

            margin-top: 4px;

            color: #9fb1d1;

            font-size: 10px;

            line-height: 1.4;
        }


        /* =========================================================
           RIGHT LOGIN PANEL
        ========================================================= */

        .login-panel {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 55px;

            background:
                linear-gradient(
                    145deg,
                    #ffffff 0%,
                    #f8fafc 60%,
                    #e0f2fe 100%
                );
        }


        .login-box {

            width: 100%;

            max-width: 460px;
        }


        .login-heading {

            margin-bottom: 32px;
        }


        .login-heading h2 {

            font-size: 32px;

            line-height: 1.1;

            font-weight: 900;

            color: #172554;

            letter-spacing: -.8px;
        }


        .login-heading p {

            margin-top: 10px;

            color: #64748b;

            font-size: 13px;

            line-height: 1.5;
        }


        /* =========================================================
           ERROR
        ========================================================= */

        .login-error {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            margin-bottom: 20px;

            padding: 13px 14px;

            color: #991b1b;

            background: #fef2f2;

            border: 1px solid #fecaca;

            border-radius: 10px;

            font-size: 12px;

            line-height: 1.4;
        }


        .login-error-icon {

            flex-shrink: 0;

            font-weight: 900;
        }


        /* =========================================================
           FORM
        ========================================================= */

        .login-form {

            width: 100%;
        }


        .form-group {

            margin-bottom: 21px;
        }


        .form-label {

            display: block;

            margin-bottom: 9px;

            color: #172554;

            font-size: 12px;

            font-weight: 800;
        }


        .input-wrap {

            position: relative;
        }


        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;

            font-size: 17px;

            pointer-events: none;
        }


        .login-input {

            width: 100%;

            height: 53px;

            padding:
                0 16px
                0 47px;

            border: 1px solid #dbe3ee;

            border-radius: 11px;

            outline: none;

            background: white;

            color: #172554;

            font-size: 13px;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .login-input::placeholder {

            color: #9aa9bd;
        }


        .login-input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.10);
        }


        .password-toggle {

            position: absolute;

            right: 13px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #94a3b8;

            cursor: pointer;

            font-size: 16px;

            padding: 5px;
        }


        .password-toggle:hover {

            color: #2563eb;
        }


        /* =========================================================
           OPTIONS
        ========================================================= */

        .login-options {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin: 4px 0 25px;
        }


        .remember {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #64748b;

            font-size: 11px;

            cursor: pointer;
        }


        .remember input {

            width: 16px;
            height: 16px;

            accent-color: #2563eb;

            cursor: pointer;
        }


        .forgot {

            color: #2563eb;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;
        }


        .forgot:hover {

            text-decoration: underline;
        }


        /* =========================================================
           BUTTON
        ========================================================= */

        .login-button {

            width: 100%;

            height: 53px;

            border: none;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color: white;

            font-size: 13px;

            font-weight: 800;

            cursor: pointer;

            box-shadow:
                0 10px 22px
                rgba(37,99,235,.22);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .login-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 13px 28px
                rgba(37,99,235,.28);
        }


        .login-button:active {

            transform: translateY(0);
        }


        /* =========================================================
           SECURITY NOTE
        ========================================================= */

        .security-note {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            margin-top: 17px;

            color: #64748b;

            font-size: 10px;
        }


        .security-icon {

            color: #16a34a;

            font-size: 13px;
        }


        /* =========================================================
           FOOTER
        ========================================================= */

        .login-footer {

            margin-top: 52px;

            text-align: center;

            color: #94a3b8;

            font-size: 10px;

            line-height: 1.6;
        }


        .login-footer strong {

            color: #64748b;

            font-weight: 700;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .login-page {

                grid-template-columns: 1fr;
            }

            .login-visual {

                display: none;
            }

            .login-panel {

                min-height: 100vh;

                padding: 35px 22px;
            }

            .login-box {

                max-width: 440px;
            }
        }


        @media (max-width: 520px) {

            .login-panel {

                padding: 25px 18px;
            }

            .login-heading h2 {

                font-size: 28px;
            }

            .login-features {

                grid-template-columns: 1fr;
            }

            .login-options {

                align-items: flex-start;
            }
        }

    </style>

</head>


<body>

<div class="login-page">


    <!-- =====================================================
         LEFT SIDE
    ====================================================== -->

    <section class="login-visual">

        <div class="login-visual-content">


            <!-- BRAND -->

            <div class="login-brand">

                <div class="login-brand-icon">
                    <span>R</span>
                </div>

                <div>

                    <div class="login-brand-name">
                        RAKSHAK <span>GIS</span>
                    </div>

                    <div class="login-brand-subtitle">
                        DISASTER RISK INTELLIGENCE
                    </div>

                </div>

            </div>


            <!-- HERO -->

            <div class="login-hero">

                <h1>
                    Safer decisions.
                    <br>
                    <span>Smarter relocation.</span>
                </h1>

                <p>
                    A centralized GIS platform for disaster risk
                    assessment, habitation monitoring and safe
                    relocation planning.
                </p>

            </div>


            <!-- FEATURES -->

            <div class="login-features">


                <div class="login-feature">

                    <div class="login-feature-icon">
                        ⌖
                    </div>

                    <div class="login-feature-title">
                        GIS Risk Mapping
                    </div>

                    <div class="login-feature-text">
                        Visualize risk across habitations.
                    </div>

                </div>


                <div class="login-feature">

                    <div class="login-feature-icon">
                        ◉
                    </div>

                    <div class="login-feature-title">
                        Risk Assessment
                    </div>

                    <div class="login-feature-text">
                        Evaluate multiple disaster factors.
                    </div>

                </div>


                <div class="login-feature">

                    <div class="login-feature-icon">
                        →
                    </div>

                    <div class="login-feature-title">
                        Relocation Planning
                    </div>

                    <div class="login-feature-text">
                        Identify suitable relocation sites.
                    </div>

                </div>


                <div class="login-feature">

                    <div class="login-feature-icon">
                        ✦
                    </div>

                    <div class="login-feature-title">
                        Centralized Monitoring
                    </div>

                    <div class="login-feature-text">
                        Manage disaster information in one place.
                    </div>

                </div>


            </div>

        </div>

    </section>


    <!-- =====================================================
         RIGHT SIDE
    ====================================================== -->

    <section class="login-panel">

        <div class="login-box">


            <!-- HEADING -->

            <div class="login-heading">

                <h2>
                    Welcome back
                </h2>

                <p>
                    Sign in to access the RakshakGIS management dashboard.
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error): ?>

                <div class="login-error">

                    <span class="login-error-icon">
                        !
                    </span>

                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
                method="POST"
                class="login-form"
                autocomplete="on"
            >


                <!-- USERNAME -->

                <div class="form-group">

                    <label
                        for="username"
                        class="form-label"
                    >
                        Email / Username
                    </label>

                    <div class="input-wrap">

                        <span class="input-icon">
                            ♙
                        </span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="login-input"
                            placeholder="Enter your username"
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST["username"] ?? "") ?>"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <div class="input-wrap">

                        <span class="input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >
                            ◉
                        </button>

                    </div>

                </div>


                <!-- OPTIONS -->

                <div class="login-options">

                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                        >

                        <span>
                            Remember me
                        </span>

                    </label>


                    <a
                        href="#"
                        class="forgot"
                        onclick="return false;"
                    >
                        Forgot password?
                    </a>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="login-button"
                >
                    Sign in to RakshakGIS
                </button>


                <!-- SECURITY -->

                <div class="security-note">

                    <span class="security-icon">
                        ♢
                    </span>

                    <span>
                        Secure access to disaster management operations
                    </span>

                </div>


            </form>


            <!-- FOOTER -->

            <div class="login-footer">

                © <?= date("Y") ?> <strong>RakshakGIS</strong>
                <br>

                Disaster Risk Assessment &amp; Safe Relocation Planner

            </div>


        </div>

    </section>

</div>


<script>

    const passwordInput =
        document.getElementById("password");

    const passwordToggle =
        document.getElementById("passwordToggle");


    passwordToggle.addEventListener("click", function () {

        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            passwordToggle.textContent = "◉";

            passwordToggle.setAttribute(
                "aria-label",
                "Hide password"
            );

        } else {

            passwordInput.type = "password";

            passwordToggle.textContent = "◉";

            passwordToggle.setAttribute(
                "aria-label",
                "Show password"
            );
        }

    });

</script>

</body>

</html>