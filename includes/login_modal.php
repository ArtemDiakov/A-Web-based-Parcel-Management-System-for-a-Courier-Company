<div class="modal fade" id="loginModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4">

            <!-- STEP 1 -->

            <div id="loginStep1">

                <h4 class="text-center mb-4">Login or Sign-Up</h4>

                <div class="d-grid gap-2 mb-3">

                    <button class="btn btn-outline-dark">Continue with Google</button>
                    <button class="btn btn-outline-dark">Continue with Apple</button>
                    <button class="btn btn-outline-dark">Continue with Microsoft</button>

                </div>

                <div class="text-center my-3">
                    <hr>
                    <span class="position-relative px-2 bg-white" style="top:-22px;">OR</span>
                </div>

                <input type="email" class="form-control mb-3" id="loginEmail" placeholder="Email Address">

                <button class="btn btn-primary w-100" onclick="checkEmail()">Continue</button>

                <p class="text-center small mt-3">
                    By continuing, you agree to Terms & Conditions.
                </p>

            </div>


            <!-- STEP 2 PASSWORD -->

            <div id="loginStep2" style="display:none;">

                <h4 class="text-center mb-4">Enter Your Password</h4>

                <form method="POST" action="/auth/login.php">

                    <input type="hidden" name="email" id="loginEmailHidden">

                    <input type="password" name="password" class="form-control mb-3" placeholder="Password">

                    <div class="text-end mb-3">
                        <a href="#" class="small">Forgot your password?</a>
                    </div>

                    <button class="btn btn-primary w-100">Login</button>

                </form>

            </div>


            <!-- STEP 3 REGISTER -->

            <div id="loginStep3" style="display:none;">

                <h4 class="text-center mb-4">Create Your Account</h4>

                <form method="POST" action="/auth/register.php">

                    <input type="hidden" name="email" id="registerEmailHidden">

                    <input class="form-control mb-3" name="full_name" placeholder="Full Name*" required>

                    <input class="form-control mb-3" name="phone" placeholder="Phone Number*" required>

                    <input type="password" class="form-control mb-3" name="password" placeholder="Password*" required>

                    <input type="password" class="form-control mb-3" name="confirm_password"
                        placeholder="Confirm Password*" required>

                    <button class="btn btn-primary w-100">Continue</button>

                </form>

            </div>

        </div>
    </div>
</div>