<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - Inventory System</title>

    <style>
    :root{
        --bg: #f8fafc;
        --panel: #ffffff;
        --panel2: #f1f5f9;
        --text: #0f172a;
        --muted: #475569;
        --line: #e2e8f0;
        --blue: #2563eb;
        --blue-soft: #eff6ff;
        --orange: #f97316;
        --red: #dc2626;
    }

    *{ box-sizing:border-box; }

    body{
        margin:0;
        font-family: Arial, Helvetica, sans-serif;
        color: var(--text);
        display:flex;
        justify-content:center;
        align-items:center;
        min-height:100vh;
        padding:20px;
        background-image: url('/images/building.jpg.JPG?v={{ time() }}');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        position: relative;
    }

    /* Global transitions */
    *{
        transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease, transform 0.3s ease;
    }

    body::before{
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.6) 0%, rgba(37, 99, 235, 0.3) 50%, rgba(37, 99, 235, 0) 100%);
        pointer-events: none;
        z-index: 1;
    }

    .login-container{
        width:100%;
        max-width:400px;
        background: rgba(255, 255, 255, 0.75);
        border:1px solid rgba(255, 255, 255, 0.2);
        border-radius:14px;
        padding:40px;
        box-shadow:0 1px 3px rgba(0,0,0,.1);
        position: relative;
        z-index: 2;
        animation: slideUp 0.5s ease;
        backdrop-filter: blur(10px);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .login-logo{
        text-align:center;
        margin-bottom:32px;
    }

    .login-logo img{
        max-width:280px;
        height:auto;
        display:block;
        margin:0 auto;
        transition: transform 0.3s ease;
        animation: fadeIn 0.8s ease 0.2s both;
    }

    .login-logo img:hover{
        transform: scale(1.05);
    }

    .login-logo a{
        text-align:center;
        margin-bottom:32px;
    }

    .logo-label{
        text-align:center;
        font-size:32px;
        font-weight:900;
        color:#0056b3;
        margin-top:12px;
        letter-spacing:3px;
        animation: fadeIn 0.8s ease 0.4s both;
        font-family: Arial, sans-serif;
    }

    .login-header{
        text-align:center;
        margin-bottom:32px;
        animation: fadeIn 0.8s ease 0.6s both;
    }

    .login-header h2{
        margin:0 0 8px 0;
        font-size:28px;
        color: var(--text);
    }

    .login-header p{
        margin:0;
        color: var(--muted);
        font-size:14px;
    }

    .form-group{
        margin-bottom:20px;
    }

    .form-group label{
        display:block;
        margin-bottom:8px;
        color: var(--text);
        font-weight:700;
        font-size:14px;
    }

    .form-group input{
        width:100%;
        padding:12px;
        border:1px solid var(--line);
        border-radius:8px;
        font-size:14px;
        background: white;
        color: var(--text);
        transition:border-color .2s, box-shadow .2s;
    }

    .form-group input:hover{
        border-color: rgba(37,99,235,0.3);
    }

    .form-group input:focus{
        outline:none;
        border-color: var(--blue);
        box-shadow:0 0 0 3px rgba(37,99,235,.1);
    }

    .form-group input::placeholder{
        color: var(--muted);
    }

    .btn-submit{
        width:100%;
        padding:12px;
        border:none;
        border-radius:8px;
        background: var(--blue);
        color:white;
        font-weight:700;
        font-size:14px;
        cursor:pointer;
        transition: all 0.3s ease;
        margin-top:8px;
        position: relative;
        overflow: hidden;
    }

    .btn-submit::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }

    .btn-submit:hover::before {
        left: 100%;
    }

    .btn-submit:hover{
        background:rgba(37,99,235,.9);
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(37,99,235,.2);
    }

    .btn-submit:active{
        background:rgba(37,99,235,.8);
        transform: translateY(0);
    }

    .error-message{
        background:rgba(220,38,38,.1);
        border:1px solid rgba(220,38,38,.3);
        color: var(--red);
        padding:12px;
        border-radius:8px;
        margin-bottom:20px;
        font-size:14px;
        animation: shake 0.5s ease;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .login-footer{
        text-align:center;
        margin-top:20px;
        color: var(--muted);
        font-size:13px;
    }

    .login-footer a{
        color: var(--blue);
        text-decoration:none;
        font-weight:700;
    }
    color: var(--blue);
    
    .login-footer a:hover{
        text-decoration:underline;
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="/images/login-logo.png?v={{ time() }}" alt="Logo">
            <div class="logo-label">SIMS</div>
        </div>



        @if(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" onsubmit="showLoading('Signing in...')">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group" style="position: relative;">
    <label for="password">Password</label>
    
    <input 
        type="password" 
        id="password" 
        name="password" 
        placeholder="Enter your password" 
        required
        style="width: 100%; padding-right: 40px;"
    >

    <!-- Eye Icon -->
    <span 
        onclick="togglePassword()" 
        style="
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
            transition: transform 0.2s ease;
            user-select: none;
        "
        id="eyeIcon"
        onmouseover="this.style.transform='scale(1.2)'"
        onmouseout="this.style.transform='scale(1)'"
    >
        👁️
    </span>
</div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="login-footer">
            <p style="text-align: center;"><a href="{{ route('admin.password-reset-self.form') }}">Forgot your password?</a></p>
        </div>
    </div>
    <!-- Loading overlay for login page -->
    <div id="global-loading" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:transparent; padding:40px; border-radius:12px; display:flex; flex-direction:column; gap:24px; align-items:center; box-shadow:none;">
            <div style="width:60px; height:60px; border-radius:50%; border:4px solid transparent; border-top-color: #2563eb; border-right-color: #2563eb; animation: spin 1.2s linear infinite;"></div>
            <div style="font-weight:700; color: #fff; font-size:16px;" id="loading-message">Signing in...</div>
        </div>
    </div>

    <style>@keyframes spin{ to { transform: rotate(360deg); } }</style>

    <script>
    function showLoading(msg){
        var overlay = document.getElementById('global-loading');
        var m = document.getElementById('loading-message');
        if(m) m.textContent = msg || 'Loading...';
        if(overlay) overlay.style.display = 'flex';
    }
    function hideLoading(){ var overlay = document.getElementById('global-loading'); if(overlay) overlay.style.display = 'none'; }
    window.addEventListener('pageshow', function(){ hideLoading(); });


    function togglePassword() {
    const password = document.getElementById("password");
    const eye = document.getElementById("eyeIcon");

    if (password.type === "password") {
        password.type = "text";
        eye.textContent = "🙈";
    } else {
        password.type = "password";
        eye.textContent = "👁️";
    }
}
    </script>
</body>
</html>
