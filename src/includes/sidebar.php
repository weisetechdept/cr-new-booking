<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <div class="navbar-brand-box">
            <a href="/dashboard" class="logo">
                <span>
                    Alpha X
                </span>
            </a>
        </div>

        <div id="sidebar-menu">
            
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">เมนู</li>
                <li>
                    <li><a href="/dashboard" class="waves-effect"><i class="feather-clipboard"></i><span>ข้อมูลการจอง</span></a></li>
                </li>
                
                <?php 
                // Show logs menu only for admin and manager roles
                if (isset($_SESSION['username']) && in_array($_SESSION['username'], ['admin', 'manager', 'crmanager'])): 
                ?>
                <li class="menu-title">ระบบจัดการ</li>
                <li>
                    <li><a href="/logs" class="waves-effect"><i class="feather-activity"></i><span>System Logs</span></a></li>
                </li>
                <?php endif; ?>
                
                <!--
                <li><a href="/admin/extension" class=" waves-effect"><i class="feather-box"></i><span>ตรวจสอบ</span></a></li>
                <li><a href="#" class=" waves-effect"><i class="feather-clipboard"></i><span>แบบสำรวจ</span></a></li>
                -->
            </ul>

            
        </div>
    
    </div>
</div>