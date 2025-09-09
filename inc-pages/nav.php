<header id="page-topbar">
    <div id="nav" class="navbar-header">
        <div class="d-flex align-items-left">
            <button type="button" class="btn btn-sm mr-2 d-lg-none px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
            <div class="dropdown d-none d-sm-inline-block"></div>
        </div>
        <div class="d-flex align-items-center">
            <div class="dropdown d-none d-sm-inline-block ml-2">
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0" aria-labelledby="page-header-search-dropdown"></div>
            </div>
            <div class="dropdown d-inline-block">
                <div class="dropdown-menu dropdown-menu-right"></div>
            </div>
            <div class="dropdown d-inline-block">
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0" aria-labelledby="page-header-notifications-dropdown"></div>
            </div>
            <div class="dropdown d-inline-block ml-2">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQBa3l1hvRYZZyYQstNZUL96bRpx-LJsU4mAYqzkTrgB_BTU1WME5z6B8GOXIld8cItRak&usqp=CAU" class="rounded-circle header-profile-user" alt="Avatar">
                    <span class="d-none d-sm-inline-block ml-1"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    <i class="mdi mdi-chevron-down d-none d-sm-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="logout.php" class="dropdown-item d-flex align-items-center justify-content-between">
                        <span>ออกจากระบบ</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>