<?php
//
//  logout.php - SoftNAS Session Logout
//
//  Copyright (c) SoftNAS Inc.  All Rights Reserved.
//
include_once ('session_functions.php');
//db_session('segment_user_id', false);
logout();
header("Location: login.php");
?>
