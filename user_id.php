<?php
session_start();
echo json_encode(['user_id' => isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : -1]);
