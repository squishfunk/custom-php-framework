<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Config;

Config::load(__DIR__ . '/../config/config.php');
Database::getConnection(true);