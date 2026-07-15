<?php

namespace App\Controllers;

use App\Helpers\Helper;

if (!Helper::estaAutenticado()) {
    Helper::redirigir('/?page=Login');
    return;
}

session_destroy();
session_start();
Helper::redirigir('/');
