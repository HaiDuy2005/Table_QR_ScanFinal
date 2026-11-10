<?php

$u = require_role(['cashier', 'kitchen', 'waiter', 'admin']);
redirect(home_for_role($u['role']));