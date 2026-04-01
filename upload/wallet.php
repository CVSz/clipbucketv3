<?php

define('THIS_PAGE', 'wallet');
define('PARENT_PAGE', 'home');

require 'includes/config.inc.php';
$userquery->logincheck();

$currentUserId = userid();

if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
    $action = isset($_POST['wallet_action']) ? mysql_clean($_POST['wallet_action']) : '';
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    $description = isset($_POST['description']) ? mysql_clean($_POST['description']) : '';

    switch ($action) {
        case 'add_funds':
            if ($cbwallet->credit($currentUserId, $amount, $description ? $description : 'Wallet top-up')) {
                e('Funds added successfully.', 'm');
            }
            break;

        case 'withdraw_funds':
            if ($cbwallet->debit($currentUserId, $amount, $description ? $description : 'Wallet withdrawal')) {
                e('Funds withdrawn successfully.', 'm');
            }
            break;

        case 'transfer_funds':
            $toUsername = isset($_POST['to_username']) ? mysql_clean($_POST['to_username']) : '';
            if (empty($toUsername)) {
                e('Please provide a recipient username.', 'e');
                break;
            }

            $toUser = $userquery->get_user_details($toUsername);
            if (empty($toUser)) {
                e('Recipient user does not exist.', 'e');
                break;
            }

            if ($cbwallet->transfer($currentUserId, $toUser['userid'], $amount, $description ? $description : 'Wallet transfer')) {
                e('Transfer completed successfully.', 'm');
            }
            break;
    }
}

$walletBalance = $cbwallet->get_balance($currentUserId);
$walletTransactions = $cbwallet->get_transactions($currentUserId, 25);

assign('wallet_balance', $walletBalance);
assign('wallet_transactions', $walletTransactions);
subtitle('My Wallet');
template_files('wallet.html');
display_it();
