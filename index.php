<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Facebook\Facebook;

session_start();
$showModal = isset($_SESSION['show_modal']) ? $_SESSION['show_modal'] : '';
unset($_SESSION['show_modal']);

// Rest of your code...

// Google Authentication URL
$googleClient = new Client();
$googleClient->setClientId('354317098133-d85d21dcgjvudtusnensac7997n0gimi.apps.googleusercontent.com');
$googleClient->setClientSecret('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');
$googleClient->setRedirectUri('http://localhost/GYS%20Resources/google-callback.php');
$googleClient->addScope('email');
$googleClient->addScope('profile');
$google_login_url = $googleClient->createAuthUrl();

// Facebook Authentication URL
$fb = new \Facebook\Facebook([
    'app_id' => '961804599422002',
    'app_secret' => '589a2e996d6515f1821b4d3f5f3705f1',
    'default_graph_version' => 'v12.0',
]);
$helper = $fb->getRedirectLoginHelper();
$permissions = ['email']; // Optional permissions
$facebook_login_url = $helper->getLoginUrl('http://localhost/GYS%20Resources/facebook-callback.php', $permissions);

?>

<!DOCTYPE html>
<html>

<head>
    <title>GYS Resources</title>
    <meta charset="UTF-8">
    <meta name="keywords" content="HTML,CSS,PHP">
    <meta name="author" content="Vinnie">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <header class="sticky-top" style="display:flex; align-items:center; padding:10px;">
        <!-- Logo Section -->
        <div class="logo me-auto">
            <img src="img/gys.png" alt="GYS Logo" height="100" width="120">
        </div>

        <!-- Button Icons -->
        <div class="buttons d-flex align-items-center gap-3">
            <button class="signupbtn" onclick="openSignupModal()">Sign Up</button>
            <button class="signinbtn" onclick="openSigninModal()">Sign In</button>
        </div>
    </header>

    <main>
        <section class="intro">
            <div class="words">
                <h3>Fully Online</h3>
                <h1>E-Bidding Platform</h1>
                <p>
                    Welcome to our e-bidding platform, where clients post projects and contractors submit bids
                    seamlessly.
                    We offer real-time notifications, secure bidding, and customizable management tools to enhance
                    efficiency
                    and transparency in construction projects.
                </p>
            </div>
        </section>

        <section class="what-is-ebidding">
            <div class="whattitle">
                <h2>What is E-Bidding?</h2>
                <img src="img/e-bidding.png" alt="e-bidding" height="80%" width="90%">
            </div>
            <div class="feature">
                <h3><i class="fa-solid fa-hammer"></i>&ensp; Digital Bidding Process:</h3>
                <p>Streamlines traditional bidding by allowing contractors to submit bids for projects online.</p>

                <br>
                <h3><i class="fa-solid fa-hammer"></i>&ensp; Convenience:</h3>
                <p>Clients can post their project requirements, and contractors can submit bids from any location,
                    promoting flexibility and efficiency.</p>

                <br>
                <h3><i class="fa-solid fa-hammer"></i>&ensp; Enhanced Competition:</h3>
                <p>Encourages competitive bidding, ensuring clients get the best value for their projects.</p>
            </div>
        </section>

        <section id="how-to-bid">
            <h2>HOW &nbsp;TO&nbsp; BID?</h2>
            <div class="bid-options">
                <button class="clientbtn" onclick="showClientSteps()">Client</button>
                <button class="contbtn" onclick="showContractorSteps()">Contractor</button>
            </div>
            <div id="client-steps" class="steps active">
                <div class="steps-container1">
                    <p>
                    <div class="number">1</div><b>&nbsp;&nbsp;Create an Account:</b><br> Register your account to post
                    new projects and track bids.</p>
                    <p><br>
                    <div class="number">3</div><b>&nbsp;&nbsp;Review Bids:</b><br> After contractors submit their bids,
                    review each proposal.</p>
                </div>
                <div class="steps-container2">
                    <p>
                    <div class="number">2</div><b>&nbsp;&nbsp;Post a Project:</b><br> Start posting projects by entering
                    project details.</p>
                    <p><br>
                    <div class="number">4</div><b>&nbsp;&nbsp;Select a Contractor:</b><br> Choose the best bid that
                    meets your project requirements.</p>
                </div>
                <img src="img/comp.png" alt="Computer" height="35%" width="30%" style="margin-left:3%;margin-top:2%;">
            </div>
            <div id="contractor-steps" class="steps">
                <img src="img/comp.png" alt="Computer" height="35%" width="30%" style="margin-right:3%;margin-top:2%;">
                <div class="steps-container1">
                    <p>
                    <div class="number">1</div><b>&nbsp;&nbsp;Create an Account:</b><br> Register and view available
                    projects.</p>
                    <p><br>
                    <div class="number">3</div><b>&nbsp;&nbsp;Submit a Bid:</b><br> Provide your proposal and quote for
                    the project.</p>
                </div>
                <div class="steps-container2">
                    <p>
                    <div class="number">2</div><b>&nbsp;&nbsp;Browse Projects:</b><br> Look through posted projects
                    relevant to your expertise.</p>
                    <p><br>
                    <div class="number">4</div><b>&nbsp;&nbsp;Get Notified:</b><br> Receive notifications if your bid is
                    selected.</p>
                </div>
            </div>
        </section>
    </main>

    <div class="wrapper">
        <div id="signupModal" class="modal">
            <div class="modal-content">
                <div id="register" class="signup-form">
                    <div class="left-side">
                        <img src="img/logregbck.png" alt="bck" height="75%" width="90%">
                    </div>
                    <form action="signup.php" method="post">
                        <h2 style="margin-top:1rem;">SIGN UP<span class="close"
                                onclick="closeSignupModal()">&times;</span></h2>

                        <?php
                        if (isset($_SESSION['signup_success_message'])):
                            echo $_SESSION['signup_success_message'];
                            unset($_SESSION['signup_success_message']);
                        endif;
                        if (isset($_SESSION['signup_error_message'])):
                            echo $_SESSION['signup_error_message'];
                            unset($_SESSION['signup_error_message']);
                        endif;
                        ?>

                        <select name="user_type" required style="width:100%;height:2rem;">
                            <option value="" disabled selected>I'm a ...</option>
                            <option value="client">Client</option>
                            <option value="contractor">Contractor</option>
                        </select>

                        <div class="input-group" style="margin-top:1.5rem;width:100%;height:2rem;">
                            <input type="tel" name="contactNumber" required pattern="[0-9]{10,15}">
                            <label>Contact Number</label>
                            <i class="fa-solid fa-phone"></i>
                        </div>

                        <div class="input-group" style="margin-top:1.2rem;width:100%;height:2rem;">
                            <input type="text" name="username" required>
                            <label>Username</label>
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div class="input-group" style="margin-top:1.2rem;width:100%;height:2rem;">
                            <input type="email" name="email" required>
                            <label>Email</label>
                            <i class="fa-solid fa-envelope"></i>
                        </div>

                        <div class="input-group" style="margin-top:1.2rem;width:100%;height:2rem;">
                            <input type="password" name="password" required>
                            <label>Password</label>
                            <i class="fa-solid fa-lock"></i>
                        </div>

                        <div class="input-group" style="margin-top:1.2rem;width:100%;height:2rem;">
                            <input type="password" name="confirmPassword" required>
                            <label>Confirm Password</label>
                            <i class="fa-solid fa-lock"></i>
                        </div>

                        <button type="submit" name="submit" value="Register" class="regbtn animation">Register</button>
                        <div class="logreg-link animation">
                            <p>Already have an account?<a href="javascript:void(0);" onclick="openSigninModal()"
                                    class="login-link"> Login</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="signinModal" class="modal">
            <div class="modal-content">
                <div id="login" class="signin-form">
                    <div class="left-side">
                        <img src="img/logregbck.png" alt="bck" height="75%" width="90%">
                    </div>
                    <form action="signin.php" method="post" class="right-form">
                        <h2 style="margin-top:2rem;">SIGN IN<span class="close" onclick="closeSigninModal()">×</span>
                        </h2>

                        <?php
                        if (isset($_SESSION['signup_success_message'])):
                            echo $_SESSION['signup_success_message'];
                            unset($_SESSION['signup_success_message']);
                        endif;
                        if (isset($_SESSION['signin_error_message'])):
                            echo $_SESSION['signin_error_message'];
                            unset($_SESSION['signin_error_message']);
                        endif;
                        ?>

                        <div class="input-group2" style="margin-top:1.2rem;width:100%;height:2rem;">
                            <input type="email" name="email" required>
                            <label>Email</label>
                            <i class="fa-solid fa-envelope"></i>
                        </div>

                        <div class="input-group2" style="margin-top:1.2rem;width:100%;height:2rem;">
                            <input type="password" name="password" required>
                            <label>Password</label>
                            <i class="fa-solid fa-lock"></i>
                        </div>

                        <button type="submit" name="submit" value="Login" class="lgnbtn animation">Login</button>
                        <div class="logreg-link animation">
                            <p>Don't have an account?<a href="javascript:void(0);" onclick="openSignupModal()"
                                    class="login-link"> Register</a></p>
                            <p><a href="forgot-password.php">Forgot Password?</a></p>
                        </div>
                        <div class="or-divider">
                            <span class="or-text">OR</span>
                        </div>
                        <a href="<?= $google_login_url ?>" class="btn btn-danger">Login with Google</a>
                        <a href="<?= $facebook_login_url ?>" class="btn btn-primary">Login with Facebook</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <button onclick="topFunction()" id="myBtn" title="Go to top">
        <b>TO TOP</b>
    </button>

    <footer class="footer">
        <div class="offinfo">
            <h2>Office Information</h1>
                <h4>GYS Resources</h3>
                    <p>25, Taman Sentosa, <br>Batu 10, Jalan Kapar, <br>Kapar, Malaysia.</p>
        </div>
        <div class="contact">
            <div class="us">
                <ul>
                    <h4>Contact Us</h4>
                    <li><i class="fa fa-phone"></i> 012-655 7817</li>
                    <li><i class="fa fa-envelope"></i> enquiry.gys@gmail.com</li>
                </ul>
            </div>
            <div class="icons">
                <a href="https://www.facebook.com/gysresources/" class="fa-brands fa-facebook" target="_blank"></a>
                <a href="https://www.instagram.com/gysresources/" class="fa-brands fa-instagram" target="_blank"></a>
            </div>
        </div>
        <div id="footer_logo">
            <img src="img/gys.png" alt="GYS Logo" height="100" width="120">
        </div>
    </footer>
    <div class="copyright">
        <p>Copyright 2020 All Rights Reserved Company Name</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="js/howtobid.js"></script>
    <script src="js/totop.js"></script>
    <script src="js/signinup.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const showModal = "<?php echo $showModal; ?>";
            if (showModal === "signupModal") {
                var signupModal = new bootstrap.Modal(document.getElementById('signupModal'), {
                    backdrop: false
                });
                signupModal.show();
            } else if (showModal === "signinModal") {
                var signinModal = new bootstrap.Modal(document.getElementById('signinModal'), {
                    backdrop: false
                });
                signinModal.show();
            }
        });
    </script>

</body>

</html>

<style>
    /* Assuming you have a class for the right-form and the buttons */
    .right-form {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-bottom: 20px;
        /* Space below the form for buttons */
    }

    .right-form form {
        width: 100%;
        /* Ensure form takes full width */
    }

    /* Styling for Google and Facebook buttons */
    .right-form .btn {
        margin-top: 10px;
        /* Space above each button */
        width: 80%;
        /* Width of the buttons */
        padding: 10px;
        /* Padding for touch-friendly interaction */
        text-align: center;
        /* Center text */
        border-radius: 25px;
        /* Rounded corners for a modern look */
        font-weight: bold;
        /* Bold text for emphasis */
        transition: all 0.3s ease;
        /* Smooth transition for hover effects */
        display: inline-block;
        /* Ensure they display inline */
    }

    /* Social Login Buttons */
    .btn-danger {
        background-color: #DB4437;
        /* Google's red */
        border-color: #DB4437;
        color: white;
        width: 100%;
        padding: 10px;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    .btn-danger:hover {
        background-color: #C63B2E;
        /* Darker shade on hover */
        border-color: #C63B2E;
    }

    .btn-primary {
        background-color: #3B5998;
        /* Facebook's blue */
        border-color: #3B5998;
        color: white;
        width: 100%;
        padding: 10px;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    .btn-primary:hover {
        background-color: #364F8A;
        /* Darker shade on hover */
        border-color: #364F8A;
    }

    /* OR Divider */
    .or-divider {
        position: relative;
        text-align: center;
        margin: 10px 0;
    }

    .or-divider::before,
    .or-divider::after {
        content: '';
        position: absolute;
        top: 30%;
        width: 45%;
        height: 1px;
        background-color: #ccc;
    }

    .or-divider::before {
        left: 0;
    }

    .or-divider::after {
        right: 0;
    }

    .or-text {
        display: inline-block;
        padding: 0 10px;
        background-color: white;
        color: #666;
        font-size: 14px;
        position: relative;
        z-index: 1;
    }

    .signinModal {
        text-align: center;
        justify-content: center;
    }

    .signin-form {
        display: flex;
        min-height: 100vh;
        width: 100%;
        max-width: 100%;
    }

    .right-form {
        width: 40%;
        max-width: 39.7%;
        margin-right: 3%;
    }

    .signin-form h2 {
        text-align: center;
        font-family: 'Jura';
        font-weight: bold;
    }

    .signin-form h2 .close {
        position: absolute; /* Positions the close button absolutely within the h2 */
        right: 50px; /* Aligns to the right with some padding */
        top: 65px;
        transform: translateY(-50%); /* Adjusts for perfect vertical centering */
        cursor: pointer; /* Changes cursor to indicate clickable */
        font-size: 24px; 
    }

    .signin-form select,
    .signin-form input {
        width: 100%;
        height: 100%;
        background: transparent;
        border: none;
        outline: none;
        border-bottom: 2px solid black;
        font-size: 0.9rem;
        color: black;
        font-weight: 500;
        transition: .5s;
        font-family: 'Karma';
    }

    .signin-form select:focus,
    .signin-form input:focus,
    .signin-form select:valid,
    .signin-form input:valid {
        border-bottom-color: #b2444f;
    }

    .input-group2 {
        width: 100%;
        position: relative;
        margin-bottom: 1.5rem;
    }

    .input-group2 label {
        position: relative;
        left: 0.4rem;
        top: -60%;
        transform: translateY(-50%);
        font-size: 0.9rem;
        color: #666;
        pointer-events: none;
        transition: 0.3s ease;
    }

    .input-group2 input:focus~label,
    .input-group2 input:valid~label,
    .input-group2 select:focus~label,
    .input-group2 select:valid~label {
        top: -2rem;
        transform: translateY(-50%);
        font-size: 0.9rem;
        color: #b2444f;
    }

    .signin-form .input-group2 input {
        padding-left: 0.4rem;
        padding-top: 0.8rem;
    }

    .input-group2 i {
        position: absolute;
        top: 50%;
        right: 2%;
        transform: translateY(-50%);
        font-size: 18px;
        color: black;
        transition: .5s;
    }

    .input-group2 input:focus~i,
    .input-group2 input:valid~i {
        color: #b2444f;
    }

    .lgnbtn {
        position: relative;
        width: 100%;
        height: 45px;
        padding: 12px;
        background-color: #b2444f;
        border: none;
        outline: none;
        border-radius: 10px;
        cursor: pointer;
        font-size: 16px;
        color: #fff;
        font-size: 1.2rem;
        z-index: 1;
        overflow: hidden;
        transition: background-color 0.3s ease;
    }

    .lgnbtn::before {
        content: '';
        position: absolute;
        top: -100%;
        left: 0;
        width: 100%;
        height: 300%;
        background: linear-gradient(#081b29, #b2444fcc, #081b29, #b2444fcc);
        z-index: -1;
    }

    .lgnbtn:hover::before {
        top: 0;
    }

    .signin-form .logreg-link,
    .signin-form .forgot-link {
        font-size: 0.9rem;
        color: black;
        text-align: center;
        margin: 10px 0 2px;
    }

    .logreg-link p a,
    .forgot-link {
        color: #b2444f;
        text-decoration: none;
        font-weight: 600;
    }

    .logreg-link p a:hover,
    .forgot-link:hover {
        text-decoration: underline;
    }

    .alert {
        font-size: 0.8rem;
    }

    @media (max-width: 100%) {
        .intro h1 {
            font-size: 2.5rem;
        }

        .intro h3 {
            font-size: 1.5rem;
        }

        .intro p {
            font-size: 1rem;
        }

        .bid-options button {
            width: 6rem;
            font-size: 0.9rem;
        }

        .steps-container1,
        .steps-container2 {
            width: 90%;
            margin: 1rem auto;
        }
    }
</style>