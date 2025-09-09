<?php
session_start();

// Load environment variables
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';

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
    // Log timeout logout
    if (isset($_SESSION['username'])) {
        logLogout($_SESSION['username'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', 'timeout');
    }
    
    session_destroy();
    header("Location: /login?expired=1");
    exit;
}

// Permission check - only admin and manager can view logs
$username = $_SESSION['username'] ?? '';
$allowedUsers = ['admin', 'crmanager'];
if (!in_array($username, $allowedUsers)) {
    header("Location: /dashboard?error=access_denied");
    exit;
}

// Validate parameter
if (!isset($_GET['p']) || $_GET['p'] !== 'logs') {
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
    <title>System Logs - Admin</title>
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
        .card-body {
            padding: 1rem;
        }
        .card {
            margin-bottom: 10px;
        }
        .log-type-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .log-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-info { background-color: #17a2b8; }
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
                                <h4 class="mb-0 font-size-18">System Logs</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Logs</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">ระบบ Logs และการเข้าใช้งาน</h4>
                                    <p class="card-title-desc">ดูข้อมูล login, logout และการเข้าถึงข้อมูลของ users ทั้งหมด</p>
                                    
                                    <div class="log-type-buttons mb-3">
                                        <button type="button" class="btn btn-primary log-type-btn active" data-type="auth">
                                            <i class="fas fa-sign-in-alt"></i> Authentication Logs
                                        </button>
                                        <button type="button" class="btn btn-outline-primary log-type-btn" data-type="data_access">
                                            <i class="fas fa-database"></i> Data Access Logs
                                        </button>
                                    </div>

                                    <div class="log-stats" id="log-stats">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <h6>Total Records</h6>
                                                <h4 id="total-count">-</h4>
                                            </div>
                                            <div class="col-md-9" id="additional-stats">
                                                <!-- Dynamic stats will be loaded here -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="logs-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead id="table-header">
                                                <!-- Dynamic headers will be loaded here -->
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- jQuery  -->
    <script src="/../../assets/js/jquery.min.js"></script>
    <script src="/../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="/../../assets/js/metismenu.min.js"></script>
    <script src="/../../assets/js/simplebar.min.js"></script>
    <script src="/../../assets/js/waves.js"></script>

    <!-- Plugins js -->
    <script src="/../../assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.bootstrap4.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.buttons.min.js"></script>
    <script src="/../../assets/plugins/datatables/buttons.bootstrap4.min.js"></script>
    <script src="/../../assets/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="/../../assets/plugins/datatables/responsive.bootstrap4.min.js"></script>

    <script>
        let currentLogType = 'auth';
        let logsTable = null;

        const logTypeConfigs = {
            auth: {
                title: 'Authentication Logs',
                headers: ['เวลา', 'ประเภท', 'ผู้ใช้', 'IP Address', 'สถานะ', 'Session ID'],
                columns: [
                    { data: 0, title: 'เวลา' },
                    { data: 1, title: 'ประเภท' },
                    { data: 2, title: 'ผู้ใช้' },
                    { data: 3, title: 'IP Address' },
                    { 
                        data: 4, 
                        title: 'สถานะ',
                        render: function(data, type, row) {
                            if (data === 'SUCCESS') {
                                return '<span class="badge badge-success">SUCCESS</span>';
                            } else if (data === 'FAILED') {
                                return '<span class="badge badge-danger">FAILED</span>';
                            } else if (data === 'manual') {
                                return '<span class="badge badge-info">Manual Logout</span>';
                            } else if (data === 'timeout') {
                                return '<span class="badge badge-warning">Session Timeout</span>';
                            }
                            return data;
                        }
                    },
                    { data: 5, title: 'Session ID' }
                ]
            },
            data_access: {
                title: 'Data Access Logs',
                headers: ['เวลา', 'ผู้ใช้', 'IP Address', 'API Endpoint', 'ช่วงวันที่', 'จำนวน Records', 'Session ID'],
                columns: [
                    { data: 0, title: 'เวลา' },
                    { data: 1, title: 'ผู้ใช้' },
                    { data: 2, title: 'IP Address' },
                    { data: 3, title: 'API Endpoint' },
                    { data: 4, title: 'ช่วงวันที่' },
                    { 
                        data: 5, 
                        title: 'จำนวน Records',
                        render: function(data, type, row) {
                            return '<span class="badge badge-info">' + data + '</span>';
                        }
                    },
                    { data: 6, title: 'Session ID' }
                ]
            }
        };

        function loadLogTable(logType) {
            currentLogType = logType;
            const config = logTypeConfigs[logType];

            // Update active button
            $('.log-type-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(`.log-type-btn[data-type="${logType}"]`).removeClass('btn-outline-primary').addClass('active btn-primary');

            // Destroy existing table
            if (logsTable) {
                logsTable.destroy();
                $('#logs-table').empty();
            }

            // Update table headers
            let headerHtml = '<tr>';
            config.headers.forEach(header => {
                headerHtml += `<th>${header}</th>`;
            });
            headerHtml += '</tr>';
            $('#table-header').html(headerHtml);

            // Initialize DataTable
            logsTable = $('#logs-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: `/api/logs.php?type=${logType}&limit=500`,
                    dataSrc: function(json) {
                        // Update stats
                        $('#total-count').text(json.total || 0);
                        return json.data || [];
                    }
                },
                columns: config.columns,
                order: [[0, 'desc']], // Sort by timestamp desc
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    "paginate": {
                        "previous": "<i class='mdi mdi-chevron-left'>",
                        "next": "<i class='mdi mdi-chevron-right'>"
                    },
                    "lengthMenu": "แสดง _MENU_ รายการ",
                    "zeroRecords": "ไม่มีข้อมูล log",
                    "info": "หน้า _PAGE_ ของ _PAGES_",
                    "infoEmpty": "ไม่มีข้อมูล",
                    "search": "ค้นหา:",
                    "processing": "กำลังโหลด..."
                },
                drawCallback: function () {
                    $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
                }
            });
        }

        $(document).ready(function() {
            // Load default log type
            loadLogTable('auth');

            // Handle log type button clicks
            $('.log-type-btn').on('click', function() {
                const logType = $(this).data('type');
                loadLogTable(logType);
            });

            // Auto refresh every 30 seconds
            setInterval(function() {
                if (logsTable) {
                    logsTable.ajax.reload(null, false);
                }
            }, 30000);
        });
    </script>

    <!-- App js -->
    <script src="/../../assets/js/theme.js"></script>

</body>
</html>