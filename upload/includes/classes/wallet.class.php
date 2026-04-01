<?php

class CBWallet
{
    /**
     * Ensure wallet row exists for a user.
     */
    function ensure_wallet($userId)
    {
        global $db;

        $userId = (int) $userId;
        if ($userId < 1) {
            return false;
        }

        $wallet = $db->select(tbl('wallets'), '*', "userid = '" . $userId . "'", 1);
        if (!empty($wallet[0])) {
            return $wallet[0];
        }

        $db->insert(tbl('wallets'), array('userid', 'balance', 'date_added', 'last_updated'), array($userId, '0.00', now(), now()));

        $wallet = $db->select(tbl('wallets'), '*', "userid = '" . $userId . "'", 1);
        return !empty($wallet[0]) ? $wallet[0] : false;
    }

    function get_balance($userId)
    {
        $wallet = $this->ensure_wallet($userId);
        if (!$wallet) {
            return 0;
        }

        return (float) $wallet['balance'];
    }

    function credit($userId, $amount, $description = '', $relatedUserId = null)
    {
        return $this->create_transaction($userId, 'credit', $amount, $description, $relatedUserId);
    }

    function debit($userId, $amount, $description = '', $relatedUserId = null)
    {
        return $this->create_transaction($userId, 'debit', $amount, $description, $relatedUserId);
    }

    /**
     * Transfer funds between users atomically.
     */
    function transfer($fromUserId, $toUserId, $amount, $description = '')
    {
        global $db;

        $fromUserId = (int) $fromUserId;
        $toUserId = (int) $toUserId;
        $amount = round((float) $amount, 2);

        if ($fromUserId < 1 || $toUserId < 1) {
            e('Invalid source or destination user.', 'e');
            return false;
        }

        if ($fromUserId === $toUserId) {
            e('You cannot transfer to yourself.', 'e');
            return false;
        }

        if ($amount <= 0) {
            e('Amount must be greater than zero.', 'e');
            return false;
        }

        $this->ensure_wallet($fromUserId);
        $this->ensure_wallet($toUserId);

        $fromBalance = $this->get_balance($fromUserId);
        if ($fromBalance < $amount) {
            e('Insufficient wallet balance.', 'e');
            return false;
        }

        $db->Execute('START TRANSACTION');

        $debit = $this->create_transaction($fromUserId, 'debit', $amount, $description, $toUserId, false);
        $credit = $this->create_transaction($toUserId, 'credit', $amount, $description, $fromUserId, false);

        if (!$debit || !$credit) {
            $db->Execute('ROLLBACK');
            e('Transfer failed, please try again.', 'e');
            return false;
        }

        $db->Execute('COMMIT');
        return true;
    }

    /**
     * @param bool $emitErrors Set false when wrapping in transaction.
     */
    function create_transaction($userId, $type, $amount, $description = '', $relatedUserId = null, $emitErrors = true)
    {
        global $db;

        $userId = (int) $userId;
        $relatedUserId = $relatedUserId !== null ? (int) $relatedUserId : null;
        $type = strtolower(trim($type));
        $amount = round((float) $amount, 2);

        if (!in_array($type, array('credit', 'debit'))) {
            if ($emitErrors) {
                e('Invalid wallet transaction type.', 'e');
            }
            return false;
        }

        if ($userId < 1 || $amount <= 0) {
            if ($emitErrors) {
                e('Invalid wallet transaction details.', 'e');
            }
            return false;
        }

        $wallet = $this->ensure_wallet($userId);
        if (!$wallet) {
            if ($emitErrors) {
                e('Could not initialize wallet.', 'e');
            }
            return false;
        }

        $balance = (float) $wallet['balance'];
        $newBalance = $type === 'credit' ? ($balance + $amount) : ($balance - $amount);

        if ($newBalance < 0) {
            if ($emitErrors) {
                e('Insufficient wallet balance.', 'e');
            }
            return false;
        }

        $db->update(tbl('wallets'), array('balance', 'last_updated'), array(number_format($newBalance, 2, '.', ''), now()), "userid = '" . $userId . "'");

        $db->insert(
            tbl('wallet_transactions'),
            array('userid', 'related_userid', 'transaction_type', 'amount', 'description', 'date_added'),
            array($userId, $relatedUserId, $type, number_format($amount, 2, '.', ''), $description, now())
        );

        return true;
    }

    function get_transactions($userId, $limit = 20)
    {
        global $db;

        $userId = (int) $userId;
        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 20;
        }

        $query = 'SELECT wt.*, u.username AS related_username '
            . ' FROM ' . tbl('wallet_transactions') . ' AS wt '
            . ' LEFT JOIN ' . tbl('users') . ' AS u ON u.userid = wt.related_userid '
            . " WHERE wt.userid = '" . $userId . "'"
            . ' ORDER BY wt.transaction_id DESC '
            . ' LIMIT ' . $limit;

        $rows = $db->Execute($query);
        $transactions = array();

        if ($rows) {
            while ($row = $rows->fetch_assoc()) {
                $transactions[] = $row;
            }
        }

        return $transactions;
    }
}
