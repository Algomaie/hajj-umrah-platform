<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سَكِينَة | Sakinah</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            overflow: hidden;
        }
        
        .splash-screen {
            text-align: center;
            max-width: 500px;
            padding: 2rem;
            border-radius: 1rem;
            background-color: white;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            transition: all 0.5s ease;
        }
        
        .splash-logo {
            width: 150px;
            height: auto;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        
        .language-btn {
            display: inline-block;
            width: 150px;
            margin: 10px;
            padding: 1rem;
            font-size: 1.2rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            background-color: #5D5CDE;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        
        .language-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        
        .language-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            font-weight: 600;
            color: #333;
        }
        
        .hidden {
            display: none;
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Dark mode styles */
        .dark {
            background-color: #181818;
        }
        
        .dark .splash-screen {
            background-color: #333;
            color: #f8f9fa;
        }
        
        .dark .language-title {
            color: #f8f9fa;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #5D5CDE;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loader Screen -->
    <div id="loader" class="splash-screen fade-in">
        <img class="splash-logo" src="assets/images/logo.ico" alt="Sakinah Logo">
        <div class="spinner"></div>
        <p id="loading-text" class="mt-3">جاري التحميل... / Loading...</p>
    </div>
    
    <!-- Language Selection -->
    <div id="language-selection" class="splash-screen hidden">
        <img class="splash-logo" src="assets/images/logo.ico" alt="Sakinah Logo">
        <h1 class="language-title">اللغة / Language</h1>
        <div>
        <div>
    <button class="language-btn" onclick="selectLanguage('ar')">العربية</button>
    <button class="language-btn" onclick="selectLanguage('en')">English</button>
</div>

        </div>
    </div>
    
    <script>
    // تفعيل الوضع الداكن حسب تفضيل الجهاز
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('dark');
    }

    // بعد 2 ثانية، أخفي شاشة التحميل وأظهر اختيار اللغة
    window.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            document.getElementById('loader').classList.add('hidden');
            document.getElementById('language-selection').classList.remove('hidden');
            document.getElementById('language-selection').classList.add('fade-in');
        }, 2000);
    });

    // عند الضغط على زر اللغة، يتم تخزينها وتحويل المستخدم
    function selectLanguage(lang) {
        localStorage.setItem('selectedLang', lang);
        window.location.href = "index.php?lang=" + lang;
    }
</script>

</body>
</html>