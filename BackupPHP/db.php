<?php
$pdo = new PDO(
  "pgsql:host=103.174.148.224;dbname=rivotcommandcenterdb",
  "rivotcommandcenter",
  "123",
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]
);
