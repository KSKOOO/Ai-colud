<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}


require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$permManager = new PermissionManager($db);

$isAdmin = $_SESSION['user']['role'] === 'admin';
$userPermissions = $permManager->getUserPermissions($_SESSION['user']['id']);


function canAccessModuleNav($module, $permissions, $isAdmin) {
    if ($isAdmin) return true;
    if (!$permissions) return true;
    foreach ($permissions['modules'] as $perm) {
        if ($perm['module'] === $module) return $perm['allowed'] == 1;
    }
    return true;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>充值中心 - 巨神兵API辅助平台API辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        
        .header {
            background: white;
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #333;
            text-decoration: none;
        }

        .logo img {
            height: 36px;
        }

        .nav {
            display: flex;
            gap: 8px;
        }

        .nav-item {
            padding: 10px 20px;
            border-radius: 10px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .nav-item:hover {; }
            background: #f8fafc;
            color: #64748b;
        }

        .nav-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        
        .main-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 24px;
        }

        @media (max-width: 900px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 32px;
            color: white;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            height: fit-content;
        }

        .balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .balance-amount {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 24px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .balance-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 600;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 4px;
        }

        
        .recharge-section {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #64748b;
        }

        
        .amount-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        @media (max-width: 600px) {
            .amount-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .amount-option {
            position: relative;
            padding: 16px;
            border: 2px solid
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .amount-option:hover {; }
            border-color: transparent;
            transform: translateY(-2px);
        }

        .amount-option.selected {
            border-color: transparent;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .amount-option.selected::after {; }
            content: '✓';
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .amount-value {
            font-size: 24px;
            font-weight: 700;
            color: #64748b;
        }

        .amount-value span {
            font-size: 14px;
            font-weight: 500;
        }

        .amount-gift {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 500;
        }

        
        .payment-methods {
            margin-bottom: 24px;
        }

        .payment-title {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 12px;
        }

        .payment-options {
            display: flex;
            gap: 12px;
        }

        .payment-option {
            flex: 1;
            padding: 16px;
            border: 2px solid
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: white;
        }

        .payment-option:hover {; }
            border-color: transparent;
        }

        .payment-option.selected {
            border-color: transparent;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .payment-option i {
            font-size: 24px;
        }

        .payment-option.alipay i {
            color: #64748b;
        }

        .payment-option.wechat i {
            color: #64748b;
        }

        .payment-option span {
            font-weight: 500;
            color: #64748b;
        }

        
        .recharge-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .recharge-btn:hover {; }
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .recharge-btn:disabled {; }
            background: #f8fafc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        
        .records-section {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-top: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .records-table {
            width: 100%;
            border-collapse: collapse;
        }

        .records-table th,
        .records-table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid
        }

        .records-table th {
            font-weight: 600;
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .records-table td {
            color: #64748b;
            font-size: 14px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-paid {
            background: #f8fafc;
            color: #64748b;
        }

        .status-pending {
            background: #f8fafc;
            color: #64748b;
        }

        .status-failed {
            background: #f8fafc;
            color: #64748b;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 32px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #64748b;
        }

        .qr-container {
            background: #f8fafc;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 20px;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            background: white;
            border-radius: 12px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: #64748b;
            border: 1px solid
        }

        .qr-amount {
            font-size: 28px;
            font-weight: 700;
            color: #64748b;
        }

        .qr-tip {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-secondary {
            flex: 1;
            padding: 12px;
            background: #f8fafc;
            color: #64748b;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover {; }
            background: #f8fafc;
        }

        .btn-primary {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover {; }
            opacity: 0.9;
        }

        
        .rules-section {
            margin-top: 24px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid
        }

        .rules-title {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 12px;
        }

        .rules-list {
            list-style: none;
            font-size: 13px;
            color: #64748b;
            line-height: 1.8;
        }

        .rules-list li {
            padding-left: 16px;
            position: relative;
        }

        .rules-list li::before {; }
            content: '•';
            position: absolute;
            left: 0;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <header class="header">
            <a href="?route=home" class="logo">
                <img src="assets/images/logo.png" alt="巨神兵API辅助平台AI" style="height: 32px; width: auto;" onerror="this.style.display='none'">
                <i class="fas fa-robot" style="display: none;"></i>
                巨神兵API辅助平台API辅助平台
            </a>
            <nav class="nav">
                <a href="?route=home" class="nav-item">
                    <i class="fas fa-home"></i> 首页
                </a>
                <?php if (canAccessModuleNav('chat', $userPermissions, $isAdmin)): ?>
                <a href="?route=chat" class="nav-item">
                    <i class="fas fa-comments"></i> 聊天
                </a>
                <?php endif; ?>
                <?php if (canAccessModuleNav('agents', $userPermissions, $isAdmin)): ?>
                <a href="?route=agents" class="nav-item">
                    <i class="fas fa-robot"></i> 智能体
                </a>
                <?php endif; ?>
                <?php if (canAccessModuleNav('workflows', $userPermissions, $isAdmin)): ?>
                <a href="?route=workflows_comfyui" class="nav-item">
                    <i class="fas fa-project-diagram"></i> 工作流
                </a>
                <?php endif; ?>
                <?php if (canAccessModuleNav('user_center', $userPermissions, $isAdmin)): ?>
                <a href="?route=user_center" class="nav-item">
                    <i class="fas fa-user-circle"></i> 用户中心
                </a>
                <?php endif; ?>
                <a href="?route=recharge" class="nav-item active">
                    <i class="fas fa-coins"></i> 充值
                </a>
                <a href="?route=logout" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> 退出
                </a>
            </nav>
        </header>

        <div class="main-content">
            
            <div class="balance-card">
                <div class="balance-label">当前余额</div>
                <div class="balance-amount">¥<span id="balanceAmount">0.00</span></div>
                <div class="balance-stats">
                    <div class="stat-item">
                        <div class="stat-value" id="totalRecharge">0</div>
                        <div class="stat-label">累计充值(元)</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="totalOrders">0</div>
                        <div class="stat-label">充值次数</div>
                    </div>
                </div>
            </div>

            
            <div>
                <div class="recharge-section">
                    <div class="section-title">
                        <i class="fas fa-wallet"></i>
                        账户充值
                    </div>

                    
                    <div class="amount-grid" id="amountGrid">
                        <div class="amount-option" data-amount="10">
                            <div class="amount-value">10<span>元</span></div>
                        </div>
                        <div class="amount-option" data-amount="20">
                            <div class="amount-value">20<span>元</span></div>
                        </div>
                        <div class="amount-option" data-amount="50">
                            <div class="amount-value">50<span>元</span></div>
                            <div class="amount-gift">送3元</div>
                        </div>
                        <div class="amount-option" data-amount="100">
                            <div class="amount-value">100<span>元</span></div>
                            <div class="amount-gift">送10元</div>
                        </div>
                        <div class="amount-option" data-amount="200">
                            <div class="amount-value">200<span>元</span></div>
                            <div class="amount-gift">送25元</div>
                        </div>
                        <div class="amount-option" data-amount="500">
                            <div class="amount-value">500<span>元</span></div>
                            <div class="amount-gift">送80元</div>
                        </div>
                        <div class="amount-option" data-amount="1000">
                            <div class="amount-value">1000<span>元</span></div>
                            <div class="amount-gift">送200元</div>
                        </div>
                    </div>

                    
                    <div class="payment-methods">
                        <div class="payment-title">选择支付方式</div>
                        <div class="payment-options">
                            <div class="payment-option alipay selected" data-method="alipay">
                                <i class="fab fa-alipay"></i>
                                <span>支付宝</span>
                            </div>
                            <div class="payment-option wechat" data-method="wechat">
                                <i class="fab fa-weixin"></i>
                                <span>微信支付</span>
                            </div>
                        </div>
                    </div>

                    
                    <button class="recharge-btn" id="rechargeBtn" onclick="createRechargeOrder()">
                        <i class="fas fa-credit-card"></i>
                        立即充值
                    </button>

                    
                    <div class="rules-section">
                        <div class="rules-title">充值说明</div>
                        <ul class="rules-list">
                            <li>充值金额即时到账，可用于平台所有付费服务</li>
                            <li>充值满50元赠送3元，满100元赠送10元，多充多送</li>
                            <li>余额不支持提现，仅限本平台消费使用</li>
                            <li>如遇充值问题，请联系客服处理</li>
                        </ul>
                    </div>
                </div>

                
                <div class="records-section">
                    <div class="section-title">
                        <i class="fas fa-history"></i>
                        充值记录
                    </div>
                    <div id="recordsContainer">
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>暂无充值记录</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-qrcode" style="color: #667eea;"></i>
                    扫码支付
                </div>
            </div>
            <div class="qr-container">
                <div class="qr-code" id="qrCode">
                    <i class="fab fa-alipay"></i>
                </div>
                <div class="qr-amount">¥<span id="qrAmount">0.00</span></div>
                <div class="qr-tip">请使用支付宝扫码支付</div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closePaymentModal()">取消</button>
                <button class="btn-primary" onclick="simulatePayment()">
                    <i class="fas fa-check"></i> 已完成支付
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let selectedAmount = 0;
        let selectedMethod = 'alipay';
        let currentOrderNo = '';


        $(document).ready(function() {
            loadBalance();
            loadRechargeRecords();


            $('.amount-option').click(function() {
                $('.amount-option').removeClass('selected');
                $(this).addClass('selected');
                selectedAmount = $(this).data('amount');
            });


            $('.payment-option').click(function() {
                $('.payment-option').removeClass('selected');
                $(this).addClass('selected');
                selectedMethod = $(this).data('method');
                updatePaymentIcon();
            });
        });


        function updatePaymentIcon() {
            const icon = selectedMethod === 'alipay' ? 'fa-alipay' : 'fa-weixin';
            const color = selectedMethod === 'alipay' ? '#1677ff' : '#07c160';
            const name = selectedMethod === 'alipay' ? '支付宝' : '微信';
            $('#qrCode').html('<i class="fab ' + icon + '" style="color: ' + color + '"></i>');
            $('.qr-tip').text('请使用' + name + '扫码支付');
        }


        function loadBalance() {
            $.ajax({
                url: 'api/recharge_handler.php',
                method: 'GET',
                data: { action: 'getBalance' },
                dataType: 'json',
                success: function(response) {; }
                    if (response.status === 'success') {
                        const data = response.data;
                        $('#balanceAmount').text(data.balance.toFixed(2));
                        $('#totalRecharge').text(data.total_recharge.toFixed(2));
                        $('#totalOrders').text(data.total_orders);
                    }
                },
                error: function() {; }
                    console.error('加载余额失败');
                }
            });
        }


        function createRechargeOrder() {
            if (selectedAmount <= 0) {
                alert('请选择充值金额');
                return;
            }

            $('#rechargeBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 处理中...');

            $.ajax({
                url: 'api/recharge_handler.php',
                method: 'POST',
                data: {; }
                    action: 'createOrder',
                    amount: selectedAmount,
                    payment_method: selectedMethod
                },
                dataType: 'json',
                success: function(response) {; }
                    if (response.status === 'success') {
                        const data = response.data;
                        currentOrderNo = data.order_no;
                        $('#qrAmount').text(data.amount.toFixed(2));
                        $('#paymentModal').addClass('show');
                    } else {
                        alert(response.message || '创建订单失败');
                    }
                },
                error: function() {; }
                    alert('网络错误，请稍后重试');
                },
                complete: function() {; }
                    $('#rechargeBtn').prop('disabled', false).html('<i class="fas fa-credit-card"></i> 立即充值');
                }
            });
        }


        function closePaymentModal() {
            $('#paymentModal').removeClass('show');
            currentOrderNo = '';
        }


        function simulatePayment() {
            if (!currentOrderNo) {
                alert('订单号无效');
                return;
            }

            $('.btn-primary').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 确认中...');

            $.ajax({
                url: 'api/recharge_handler.php',
                method: 'POST',
                data: {; }
                    action: 'simulatePayment',
                    order_no: currentOrderNo
                },
                dataType: 'json',
                success: function(response) {; }
                    if (response.status === 'success') {
                        alert('充值成功！金额：¥' + response.data.total_amount.toFixed(2));
                        closePaymentModal();
                        loadBalance();
                        loadRechargeRecords();
                    } else {
                        alert(response.message || '支付确认失败');
                    }
                },
                error: function() {; }
                    alert('网络错误，请稍后重试');
                },
                complete: function() {; }
                    $('.btn-primary').prop('disabled', false).html('<i class="fas fa-check"></i> 已完成支付');
                }
            });
        }


        function loadRechargeRecords() {
            $.ajax({
                url: 'api/recharge_handler.php',
                method: 'GET',
                data: { action: 'getRechargeRecords' },
                dataType: 'json',
                success: function(response) {; }
                    if (response.status === 'success') {
                        renderRechargeRecords(response.data.records);
                    }
                },
                error: function() {; }
                    console.error('加载充值记录失败');
                }
            });
        }


        function renderRechargeRecords(records) {
            const container = $('#recordsContainer');

            if (!records || records.length === 0) {
                container.html(`
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>暂无充值记录</p>
                    </div>
                `);
                return;
            }

            let html = `
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>订单号</th>
                            <th>充值金额</th>
                            <th>赠送金额</th>
                            <th>实际到账</th>
                            <th>支付方式</th>
                            <th>状态</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            records.forEach(function(record) {
                const statusClass = record.payment_status === 'paid' ? 'status-paid' : 
                                   record.payment_status === 'pending' ? 'status-pending' : 'status-failed';
                const statusText = record.payment_status === 'paid' ? '已完成' : 
                                  record.payment_status === 'pending' ? '待支付' : '失败';
                const methodText = record.payment_method === 'alipay' ? '支付宝' : '微信支付';

                html += `
                    <tr>
                        <td>${record.order_no}</td>
                        <td>¥${parseFloat(record.amount).toFixed(2)}</td>
                        <td>¥${parseFloat(record.gift_amount).toFixed(2)}</td>
                        <td style="font-weight: 600; color: #16a34a;">¥${parseFloat(record.total_amount).toFixed(2)}</td>
                        <td>${methodText}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${record.created_at}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.html(html);
        }


        $('#paymentModal').click(function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>
