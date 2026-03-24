<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4">

            <!-- STEP 1 -->
            <form id="emailCheckForm" novalidate>
                <div id="loginStep1">
                    <h4 class="text-center mb-4">Login or Sign-Up</h4>

                    <div class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-outline-dark">Continue with Google</button>
                        <button type="button" class="btn btn-outline-dark">Continue with Apple</button>
                        <button type="button" class="btn btn-outline-dark">Continue with Microsoft</button>
                    </div>

                    <div class="text-center my-3">
                        <hr>
                        <span class="position-relative px-2 bg-white" style="top:-22px;">OR</span>
                    </div>

                    <div class="mb-3">
                        <input type="email" class="form-control" id="loginEmail" placeholder="Email Address" required>
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Continue</button>

                    <p class="text-center small mt-3">
                        By continuing, you agree to Terms & Conditions.
                    </p>
                </div>
            </form>

            <!-- STEP 2 PASSWORD -->
            <div id="loginStep2" style="display:none;">
                <h4 class="text-center mb-4">Enter Your Password</h4>

                <form method="POST" action="/auth/login.php" id="loginForm" novalidate>
                    <input type="hidden" name="email" id="loginEmailHidden">

                    <div class="mb-3">
                        <div class="input-group">
                            <input type="password" id="loginPassword" name="password" class="form-control" placeholder="Password" required>

                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('loginPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>

                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3">
                        <a href="#" class="small">Forgot your password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>

            <!-- STEP 3 REGISTER -->
            <div id="loginStep3" style="display:none;">
                <h4 class="text-center mb-4">Create Your Account</h4>

                <form method="POST" action="/auth/register.php" id="registerForm" novalidate>
                    <input type="hidden" name="email" id="registerEmailHidden">

                    <div class="mb-3">
                        <input class="form-control" name="full_name" placeholder="Full Name*" required maxlength="100">
                        <div class="invalid-feedback">
                            Please enter your full name.
                        </div>
                    </div>

                    <div class="mb-3">
                        <input class="form-control" name="phone" placeholder="Phone Number*" required
                            pattern="^(\+44|0)7\d{9}$">
                        <div class="invalid-feedback">
                            Enter a valid UK mobile number (e.g. 07911123456 or +447911123456).
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="input-group has-validation">
                            <input type="password" id="registerPassword" class="form-control" name="password"
                                placeholder="Password*" required minlength="8"
                                pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,72}$">

                            <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePassword('registerPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>

                            <div class="invalid-feedback">
                                Password must be 8-72 characters and include uppercase, lowercase and number.
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="input-group has-validation">
                            <input type="password" id="registerConfirmPassword" class="form-control"
                                name="confirm_password" placeholder="Confirm Password*" required>

                            <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePassword('registerConfirmPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>

                            <div class="invalid-feedback">
                                Please confirm your password.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Continue</button>
                </form>
            </div>

        </div>
    </div>
</div>