<?php
require __DIR__ . "/vendor/autoload.php";
echo "Autoload OK" . PHP_EOL;
if (class_exists("Illuminate\\Foundation\\Application")) { echo "Laravel loaded" . PHP_EOL; } else { echo "Laravel NOT loaded" . PHP_EOL; }
