<?php

// autoload Classes
function classAutoload($class_name) {
    include "classes/class." . $class_name . ".php";
}

spl_autoload_register('classAutoload');