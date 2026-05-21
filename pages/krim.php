<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Криминогенность</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../assets/js/compass_state.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
    * { box-sizing:border-box; margin:0; padding:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; }
    body { background-color:#f4f4f4; color:#333; padding-top:20px; display:flex; flex-direction:column; justify-content:flex-start; min-height:100vh; }
    .header { display:flex; justify-content:center; align-items:center; margin-bottom:20px; position:relative; height:60px; }
    .header-content { display:flex; align-items:center; justify-content:center; width:100%; position:relative; }
    h1 { margin:0; text-align:center; color:#4682B4; font-size:36px; font-weight:bold; line-height:1; }
    .container { display:flex; flex-direction:column; align-items:center; gap:.625rem; padding-top:30px; }
    .card { position:relative; cursor:pointer; outline:none; border:2px solid #4682B4; background:#fff; padding:0; width:30rem; height:6rem; font-size:24px; font-family:inherit; border-radius:.8rem; text-decoration:none; margin-bottom:12px; display:block; overflow:hidden; }
    .card .circle { transition:all .45s cubic-bezier(.65,0,.076,1); position:relative; display:block; margin:0; width:3rem; height:6rem; background:#4682B4; border-radius:.6rem; }
    .card .circle .icon { transition:all .45s cubic-bezier(.65,0,.076,1); position:absolute; top:0; bottom:0; margin:auto; background:#fff; }
    .card .circle .icon.arrow { transition:all .45s cubic-bezier(.65,0,.076,1); left:.625rem; width:1.125rem; height:.125rem; background:none; }
    .card .circle .icon.arrow::before { position:absolute; content:''; top:-.25rem; right:.0625rem; width:.625rem; height:.625rem; border-top:.125rem solid #fff; border-right:.125rem solid #fff; transform:rotate(45deg); }
    .card .card-title { transition:all .45s cubic-bezier(.65,0,.076,1); position:absolute; top:0; left:0; right:0; bottom:0; padding:1.5rem 0; margin:0 0 0 1.85rem; color:#4682B4; font-weight:700; line-height:1.6; text-align:center; text-transform:uppercase; }
    .card:hover .circle { width:100%; }
    .card:hover .circle .icon.arrow { background:#fff; transform:translate(1rem,0); }
    .card:hover .card-title { color:#fff; }
    .back-button-container { display:flex; justify-content:center; align-items:center; margin-top:40px; margin-bottom:20px; width:100%; }
    .back-button { width:180px; height:50px; background-color:#4682B4; color:#fff; border:1px solid #4682B4; font-size:18px; font-weight:600; padding:10px; transition:background-color .3s,border-color .3s; border-radius:8px; cursor:pointer; text-transform:uppercase; }
    .back-button:hover { background-color:#fff; color:#4682B4; border:1px solid #4682B4; }
    .footer { text-align:center; margin-top:auto; font-size:14px; color:#777; margin-bottom:35px; }
    @media screen and (max-width:550px) { .card { width:25rem; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>КРИМИНОГЕННОСТЬ И ПРАВОПОРЯДОК</h1>
        </div>
    </div>

    <div class="container">
        <a href="javascript:void(0);" onclick="location.href=CompassState.buildURL('svodki.php')" class="card crime-card">
            <span class="circle"><span class="icon arrow"></span></span>
            <span class="card-title">СВОДКА</span>
        </a>

        <a href="javascript:void(0);" onclick="location.href=CompassState.buildURL('selector.php')" class="card crime-card">
            <span class="circle"><span class="icon arrow"></span></span>
            <span class="card-title">СЕЛЕКТОР</span>
        </a>

        <div class="back-button-container">
            <button class="back-button" onclick="window.location.href=CompassState.buildURL('../index.php')">Назад</button>
        </div>
    </div>

    <div class="footer">
        © <?php echo date('Y'); ?> Компас. Все права защищены.
    </div>
</body>
</html>
