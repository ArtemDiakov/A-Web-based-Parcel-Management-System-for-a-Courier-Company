<nav class="navbar navbar-expand-lg bg-white border-bottom">

    <div class="container">

        <a class="navbar-brand fw-bold" href="/index.php">ParcelPro</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navmenu">

            <!-- CENTER LINKS -->

            <ul class="navbar-nav mx-auto">

                <li class="nav-item">
                    <a class="nav-link" href="/send.php">Send Parcel</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/track.php">Track Parcel</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/help.php">Help / FAQ</a>
                </li>

                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'staff' || $_SESSION['role'] == 'admin')): ?>

                    <li class="nav-item">
                        <a class="nav-link" href="/staff/dashboard.php">Staff Dashboard</a>
                    </li>

                <?php endif; ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>

                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard.php">Admin Dashboard</a>
                    </li>

                <?php endif; ?>

            </ul>

            <!-- RIGHT SIDE -->

            <div>

                <?php if (isset($_SESSION['user_id'])): ?>

                    <!-- ACCOUNT DROPDOWN -->

                    <div class="dropdown">

                        <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">

                            <?= htmlspecialchars($_SESSION['full_name']) ?>

                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">

                            <li>
                                <a class="dropdown-item" href="/pages/account.php">
                                    Manage Account
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="/pages/order_history.php">
                                    Order History
                                </a>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <a class="dropdown-item" href="/logout.php">
                                    Logout
                                </a>
                            </li>

                        </ul>

                    </div>

                <?php else: ?>

                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">

                        Login / Register

                    </button>

                <?php endif; ?>

            </div>

        </div>

    </div>

</nav>