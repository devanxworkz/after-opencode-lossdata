<?php
session_start();
$correctUsername = "rivotcommandcenter";
$correctPassword = "898944";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === $correctUsername && $password === $correctPassword) {
        $_SESSION['loggedin'] = true;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid username or password'); window.location.href='login.html';</script>";
        exit();
    }
} else {
    header("Location: login.html");
    exit();
}
?>
