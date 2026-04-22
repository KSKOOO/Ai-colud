$(document).ready(function() {
    // 登录对话框控制
    const loginBtn = document.getElementById('loginBtn');
    const loginOverlay = document.getElementById('loginOverlay');
    const cancelBtn = document.getElementById('cancelBtn');
    const submitBtn = document.getElementById('submitBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    // 显示登录对话框
    loginBtn.addEventListener('click', () => {
        loginOverlay.style.display = 'flex';
        usernameInput.focus();
    });

    // 隐藏登录对话框
    cancelBtn.addEventListener('click', () => {
        loginOverlay.style.display = 'none';
    });

    // 登录功能
    submitBtn.addEventListener('click', () => {
        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();

        if (username && password) {
            // 模拟登录成功
            loginUser(username);
            loginOverlay.style.display = 'none';
            
            // 清空输入框
            usernameInput.value = '';
            passwordInput.value = '';
        } else {
            alert('请填写用户名和密码');
        }
    });

    // 登出功能
    logoutBtn.addEventListener('click', () => {
        logoutUser();
    });

    // 登录用户
    function loginUser(username) {
        // 显示用户信息
        document.getElementById('userInfo').style.display = 'flex';
        document.getElementById('userName').textContent = username;
        document.getElementById('userAvatar').textContent = username.charAt(0).toUpperCase();
        
        // 隐藏登录按钮
        loginBtn.style.display = 'none';
    }

    // 登出用户
    function logoutUser() {
        // 隐藏用户信息
        document.getElementById('userInfo').style.display = 'none';
        
        // 显示登录按钮
        loginBtn.style.display = 'inline-block';
    }

    // 注册链接
    document.getElementById('registerLink').addEventListener('click', (e) => {
        e.preventDefault();
        showRegisterForm();
    });

    // 显示注册表单
    function showRegisterForm() {
        const formHtml = `
            <div class="form-group">
                <label for="regUsername">用户名</label>
                <input type="text" id="regUsername" placeholder="请输入用户名">
            </div>
            <div class="form-group">
                <label for="regPassword">密码</label>
                <input type="password" id="regPassword" placeholder="请输入密码">
            </div>
            <div class="form-group">
                <label for="regEmail">邮箱</label>
                <input type="email" id="regEmail" placeholder="请输入邮箱">
            </div>
            <div class="login-actions">
                <button class="login-btn" id="registerBtn">注册</button>
                <button class="cancel-btn" id="backToLoginBtn">返回登录</button>
            </div>
        `;
        
        // 替换登录表单为注册表单
        const loginBody = document.querySelector('.login-body');
        loginBody.innerHTML = formHtml;
        
        // 隐藏取消按钮，只显示返回登录按钮
        cancelBtn.style.display = 'none';
        
        // 绑定注册按钮事件
        document.getElementById('registerBtn').addEventListener('click', handleRegister);
        document.getElementById('backToLoginBtn').addEventListener('click', showLoginForm);
    }

    // 显示登录表单
    function showLoginForm() {
        // 恢复原始登录表单
        loginBody.innerHTML = `
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" placeholder="请输入用户名">
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" placeholder="请输入密码">
            </div>
            <div class="login-actions">
                <button class="login-btn" id="submitBtn">登录</button>
                <button class="cancel-btn" id="cancelBtn">取消</button>
            </div>
        `;
        
        // 恢复事件绑定
        submitBtn = document.getElementById('submitBtn');
        cancelBtn = document.getElementById('cancelBtn');
        
        submitBtn.addEventListener('click', () => {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (username && password) {
                loginUser(username);
                loginOverlay.style.display = 'none';
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
            } else {
                alert('请填写用户名和密码');
            }
        });
        
        cancelBtn.addEventListener('click', () => {
            loginOverlay.style.display = 'none';
        });
        
        document.getElementById('registerLink').addEventListener('click', (e) => {
            e.preventDefault();
            showRegisterForm();
        });
        
        // 显示取消按钮
        cancelBtn.style.display = 'inline-block';
    }

    // 处理注册
    function handleRegister() {
        const username = document.getElementById('regUsername').value.trim();
        const password = document.getElementById('regPassword').value.trim();
        const email = document.getElementById('regEmail').value.trim();

        if (username && password && email) {
            // 模拟注册成功
            alert(`注册成功！欢迎 ${username} 加入巨神兵AI平台！`);
            showLoginForm();
        } else {
            alert('请填写所有必填字段');
        }
    }
});