<?php

require "vendor/autoload.php";
require "classes/PhoneLookUp.php";

$phone_number = "0800300466";

$look_obj = new PhoneLookUp($phone_number);
print_r($look_obj->lookup());
