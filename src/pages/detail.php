<?php
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://code.highcharts.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';");

// Authentication check
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: /login");
    exit;
}

// Session timeout check (30 minutes)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 1800) {
    // Load auth functions for logout logging
    require_once __DIR__ . '/../../config/env.php';
    require_once __DIR__ . '/../../config/auth.php';
    
    // Log timeout logout
    if (isset($_SESSION['username'])) {
        logLogout($_SESSION['username'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', 'timeout');
    }
    
    session_destroy();
    header("Location: /login?expired=1");
    exit;
}

// Validate parameter
if (!isset($_GET['p']) || $_GET['p'] !== 'crl1') {
    header("Location: /login");
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="A77" name="description" />
    <meta content="A77" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="/../../assets/images/favicon.ico">

    <!-- Plugins css -->
    <link href="/../../assets/plugins/datatables/dataTables.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="/../../assets/plugins/datatables/responsive.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="/../../assets/plugins/datatables/buttons.bootstrap4.css" rel="stylesheet" type="text/css" />
    <link href="/../../assets/plugins/datatables/select.bootstrap4.css" rel="stylesheet" type="text/css" />

    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@100;200;300;400;500;600;700;800&family=Kanit:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- App css -->
    <link href="/../../assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="/../../assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="/../../assets/css/theme.min.css" rel="stylesheet" type="text/css" />
    <style>
        body {
            font-family: 'Chakra Petch', sans-serif;
        }
        .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {
            font-family: 'Kanit', sans-serif;
            font-weight: 400;
        }
        .page-content {
            padding: calc(70px + 24px) calc(5px / 2) 70px calc(5px / 2);
        }
        .table {
            width: 100% !important;
        }
        .dtr-details {
            width: 100%;
        }
        .card-body {
            padding: 1rem;
        }
        .card {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div id="layout-wrapper">
        <?php 
                include_once('../includes/nav.php');
                include_once('../includes/sidebar.php');
        ?>
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0 font-size-18">ข้อมูลการจอง</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Alpha X</a></li>
                                        <li class="breadcrumb-item active">ข้อมูลการจอง</li>
                                    </ol>
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>ตั้งแต่วันที่</label>
                                        <input type="date" class="form-control" id="fmdate" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>ถึงวันที่</label>
                                        <input type="date" class="form-control" id="todate" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <button class="btn btn-primary" id="search">ปรับใช้</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-3">

                                <div class="card bg-success border-success">
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <h5 class="card-title mb-0 text-white">จองทั้งหมด</h5>
                                        </div>
                                        <div class="row d-flex align-items-center">
                                            <div class="col-8">
                                                <h2 class="d-flex align-items-center text-white mb-0">
                                                    <span id="data-count">0</span>
                                                </h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        
                        </div>
                    </div>
               

                    <div id="detail">
                        <div class="row mt-1">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="mb-2 font-size-18">ข้อมูลการจองทั้งหมด</h4>

                                        <table id="datatable" class="table">
                                            <thead>
                                                <tr>
                                                    <th width="85px">วันที่จอง</th>
                                                    <th width="100px">ลูกค้า</th>
                                                    <th>เบอร์โทร</th>
                                                    <th width="85px">เซลล์</th>
                                                    <th width="50px">ทีม</th>
                                                    
                                                    <th width="85px">ราคาขาย</th>
                                                    <th width="85px">เงินดาวน์</th>
                                                    <th width="50px">เงินจอง</th>
                                                    
                                                    <th width="185px">รุ่นรถ</th>
                                                    <th width="85px">แบบรถ</th>
                                                    <th width="85px">สี</th>
                                                    <th width="85px">สถานะ</th>
                                                    
                                                    
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>

                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            2023 © Weise Tech.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-right d-none d-sm-block">
                                Design & Develop by Weise Tech
                            </div>
                        </div>
                    </div>
                </div>
            </footer>

        </div>
      

    </div>
 

  
    <div class="menu-overlay"></div>

    <!-- jQuery  -->
    <script src="/../../assets/js/jquery.min.js"></script>
    <script src="/../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="/../../assets/js/metismenu.min.js"></script>
    <script src="/../../assets/js/waves.js"></script>
    <script src="/../../assets/js/simplebar.min.js"></script>

    <!-- third party js -->
    <script src="/../../assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.bootstrap4.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="/../../assets/plugins/datatables/responsive.bootstrap4.min.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.buttons.min.js"></script>
    <script src="/../../assets/plugins/datatables/buttons.bootstrap4.min.js"></script>
    <script src="/../../assets/plugins/datatables/buttons.html5.min.js"></script>
    <script src="/../../assets/plugins/datatables/buttons.flash.min.js"></script>
    <script src="/../../assets/plugins/datatables/buttons.print.min.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.keyTable.min.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.select.min.js"></script>
    <script src="/../../assets/plugins/datatables/pdfmake.min.js"></script>
    <script src="/../../assets/plugins/datatables/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.1/axios.min.js"></script>
	<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script src="https://code.highcharts.com/maps/highmaps.js"></script>
    <script src="https://code.highcharts.com/maps/modules/exporting.js"></script>
    <script src="/../../assets/js/th-th-all.js"></script>

    <script>
        $('#datatable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'print'
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='mdi mdi-chevron-left'>",
                    "next": "<i class='mdi mdi-chevron-right'>"
                },
                "lengthMenu": "แสดง _MENU_ รายชื่อ",
                "zeroRecords": "ขออภัย ไม่มีข้อมูล",
                "info": "หน้า _PAGE_ ของ _PAGES_",
                "infoEmpty": "ไม่มีข้อมูล", 
                "search": "ค้นหา:",
            },
            "drawCallback": function () {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            },
            ajax: '/api/secure.php?fmdate=<?php echo date("Y-m-d"); ?>&todate=<?php echo date("Y-m-d"); ?>',
            "columns" : [
                {'data':'0'},
                {'data':'1'},
                {'data':'2'},
                {'data':'3'},
                {'data':'4'},
                {'data':'5'},
                {'data':'6'},
                {'data':'7'},
                {'data':'8'},
                {'data':'9'},
                {'data':'10'},
                {'data':'11'}
            ],
        });

        var count_data = $('#datatable').DataTable();
        count_data.ajax.url('/api/secure.php?fmdate=<?php echo date("Y-m-d"); ?>&todate=<?php echo date("Y-m-d"); ?>').load(function() {
            var count = count_data.rows().count();
            $('#data-count').text(count);
        });

        $('#search').click(function(){
            var fmdate = $('#fmdate').val();
            var todate = $('#todate').val();
            
            // Display loading message
            swal({
                title: "กำลังโหลดข้อมูล...",
                text: "โปรดรอสักครู่..",
                icon: "info",
                buttons: false,
                closeOnClickOutside: false,
                closeOnEsc: false,
                showLoading: true
            });

            var table = $('#datatable').DataTable();
            table.ajax.url('/api/secure.php?fmdate='+fmdate+'&todate='+todate).load(function() {
                // Hide loading message
                swal.close();
                var count = table.rows().count();
                $('#data-count').text(count);
            });
            
        });

    </script>

    <!-- App js -->
    <script src="/../../assets/js/theme.js"></script>

</body>

</html> 