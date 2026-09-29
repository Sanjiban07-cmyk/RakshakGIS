<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

require_once __DIR__ . "/config/database.php";

/*
|--------------------------------------------------------------------------
| Redirect already logged-in users
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Login Processing
|--------------------------------------------------------------------------
*/

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Find user by username OR email
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT
                id,
                name,
                username,
                email,
                password,
                role,
                status
            FROM users
            WHERE username = ?
               OR email = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Unable to process login. Please check the database connection.";

        } else {

            $stmt->bind_param(
                "ss",
                $username,
                $username
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $user = $result->fetch_assoc();


            /*
            |--------------------------------------------------------------------------
            | Validate User
            |--------------------------------------------------------------------------
            */

            if (!$user) {

                $error = "Invalid username or password.";

            } elseif ($user["status"] !== "ACTIVE") {

                $error = "This account is inactive.";

            } elseif (!password_verify($password, $user["password"])) {

                $error = "Invalid username or password.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Login Successful
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];

                $_SESSION["user"] = [
                    "id"       => $user["id"],
                    "name"     => $user["name"],
                    "username" => $user["username"],
                    "email"    => $user["email"],
                    "role"     => $user["role"]
                ];

                header("Location: index.php");
                exit;
            }

            $stmt->close();
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

    <title>Login | RakshakGIS</title>


    <!-- Global RakshakGIS CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<div class="login-page">


    <!-- =====================================================
         LEFT BRANDING PANEL
         ===================================================== -->

    <section class="login-visual">

        <div class="login-visual-content">


            <!-- BRAND -->

            <div class="login-brand">

                <div class="login-brand-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path
                            d="M12 3L20 6V11C20 16.2 16.8 20 12 22C7.2 20 4 16.2 4 11V6L12 3Z"
                        />

                        <path
                            d="M8 12.5L10.5 15L16 9.5"
                        />

                    </svg>

                </div>


                <div>

                    <div class="login-brand-name">
                        RAKSHAKGIS
                    </div>

                    <div class="login-brand-subtitle">
                        Disaster Risk & Safe Relocation
                    </div>

                </div>

            </div>


            <!-- MAIN HEADING -->

            <h1>

                Safer decisions.<br>

                <span>
                    Smarter relocation.
                </span>

            </h1>


            <!-- DESCRIPTION -->

            <p class="login-visual-description">

                A centralized GIS platform for disaster risk
                assessment, habitation monitoring and safe
                relocation planning.

            </p>


            <!-- FEATURES -->

            <div class="login-features">


                <!-- FEATURE 1 -->

                <div class="login-feature">

                    <div class="login-feature-icon">

                        <svg
                            width="17"
                            height="17"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12z"
                            />

                            <circle
                                cx="12"
                                cy="9"
                                r="2"
                            />

                        </svg>

                    </div>


                    <div class="login-feature-title">
                        GIS Risk Mapping
                    </div>


                    <div class="login-feature-text">
                        Visualize risk across habitations.
                    </div>

                </div>


                <!-- FEATURE 2 -->

                <div class="login-feature">

                    <div class="login-feature-icon">

                        <svg
                            width="17"
                            height="17"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path d="M12 3v18"/>

                            <path d="M3 12h18"/>

                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                            />

                        </svg>

                    </div>


                    <div class="login-feature-title">
                        Risk Assessment
                    </div>


                    <div class="login-feature-text">
                        Evaluate multiple disaster factors.
                    </div>

                </div>


                <!-- FEATURE 3 -->

                <div class="login-feature">

                    <div class="login-feature-icon">

                        <svg
                            width="17"
                            height="17"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path d="M5 12h14"/>

                            <path d="M13 6l6 6-6 6"/>

                        </svg>

                    </div>


                    <div class="login-feature-title">
                        Relocation Planning
                    </div>


                    <div class="login-feature-text">
                        Identify suitable relocation sites.
                    </div>

                </div>


                <!-- FEATURE 4 -->

                <div class="login-feature">

                    <div class="login-feature-icon">

                        <svg
                            width="17"
                            height="17"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path d="M12 3v18"/>

                            <path d="M3 12h18"/>

                            <path d="M5 5l14 14"/>

                            <path d="M19 5L5 19"/>

                        </svg>

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
         LOGIN PANEL
         ===================================================== -->

    <main class="login-panel">


        <div class="login-card">


            <!-- LOGIN HEADING -->

            <div class="login-heading">

                <h2>
                    Welcome back
                </h2>


                <p>
                    Sign in to access the RakshakGIS
                    management dashboard.
                </p>

            </div>



            <!-- ERROR MESSAGE -->

            <?php if (!empty($error)): ?>

                <div class="login-error">

                    <svg
                        width="17"
                        height="17"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        />

                        <path d="M12 8v5"/>

                        <path d="M12 16h.01"/>

                    </svg>


                    <span>

                        <?= htmlspecialchars($error) ?>

                    </span>

                </div>

            <?php endif; ?>



            <!-- LOGIN FORM -->

            <form
                class="login-form"
                method="POST"
                action=""
                autocomplete="on"
            >


                <!-- USERNAME -->

                <div class="login-field">

                    <label for="username">
                        Email / Username
                    </label>


                    <div class="login-input-wrap">


                        <svg
                            class="login-input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >

                            <path
                                d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                            />

                            <circle
                                cx="12"
                                cy="7"
                                r="4"
                            />

                        </svg>


                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="login-input"
                            placeholder="Enter your username"
                            required
                            autocomplete="username"
                            autofocus
                        >

                    </div>

                </div>



                <!-- PASSWORD -->

                <div class="login-field">

                    <label for="password">
                        Password
                    </label>


                    <div class="login-input-wrap">


                        <svg
                            class="login-input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >

                            <rect
                                x="4"
                                y="10"
                                width="16"
                                height="11"
                                rx="2"
                            />

                            <path
                                d="M8 10V7a4 4 0 0 1 8 0v3"
                            />

                        </svg>



                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >



                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Show password"
                        >

                            <svg
                                id="eyeIcon"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >

                                <path
                                    d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="2.5"
                                />

                            </svg>

                        </button>

                    </div>

                </div>



                <!-- OPTIONS -->

                <div class="login-options">


                    <label class="remember-me">

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        <span>
                            Remember me
                        </span>

                    </label>



                    <a
                        href="#"
                        class="login-forgot"
                        onclick="return false;"
                    >
                        Forgot password?
                    </a>


                </div>



                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="login-submit"
                >

                    Sign in to RakshakGIS

                </button>


            </form>



            <!-- SECURITY -->

            <div class="login-security">


                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <path
                        d="M12 3l8 3v5c0 5-3.2 8.5-8 10-4.8-1.5-8-5-8-10V6l8-3z"
                    />

                </svg>


                Secure access to disaster management operations


            </div>



            <!-- FOOTER -->

            <div class="login-footer">

                © <?= date('Y') ?> RakshakGIS

                <br>

                Disaster Risk Assessment & Safe Relocation Planner

            </div>


        </div>

    </main>

</div>



<!-- =====================================================
     PASSWORD TOGGLE
     ===================================================== -->

<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const icon =
        document.getElementById("eyeIcon");


    if (password.type === "password") {

        password.type = "text";


        icon.innerHTML = `
            <path d="M3 3l18 18"/>
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
            <path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a17.8 17.8 0 0 1-3.2 4.1"/>
            <path d="M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4.4-.9"/>
        `;

    } else {

        password.type = "password";


        icon.innerHTML = `
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/>
            <circle cx="12" cy="12" r="2.5"/>
        `;

    }

}

</script>


</body>

</html>