<?php
    session_start([
        'cookie_lifetime' => 3600,
        'cookie_secure' => true, //Apenas if protocol = https
        'cookie_httponly' => true, //Acesso ao cookie apenas via https
        'use_strict_mode' => true, //apenas id de sessão geradoas pelo servidor
        'use_only_cookies' => true, //sem id da sessão via url
        'cookie_samesite' => 'Strict', //não envia o cookie para outros sites
    ]);

    if (!isset($_SESSION['initiated'])){
        session_regenerate_id();
        $_SESSION['initiated'] = true;
    }

    if (!isset($_SESSION['user_agent'])){
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    } elseif ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    }
?>