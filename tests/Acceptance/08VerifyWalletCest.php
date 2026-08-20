<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2025 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use PHPUnit\Framework\Assert;

class VerifyWalletCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify-wallet
     */
    public function verify(AcceptanceTester $I)
    {
        // Phải tồn tại bảng nv4_wallet_sepay_logs
        $result = $I->grabFromDatabase('information_schema.tables', 'COUNT(*)', [
            'table_schema' => $_ENV['DB_NAME'],
            'table_name'   => 'nv4_wallet_sepay_logs'
        ]);
        Assert::assertGreaterThan(0, $result, 'Bảng nv4_wallet_sepay_logs không tồn tại!');

        // Bảng nv4_config phải có 1 row lang=vi, module=wallet, config_name=captcha_type
        $result = $I->grabFromDatabase('nv4_config', 'COUNT(*)', [
            'lang'        => 'vi',
            'module'      => 'wallet',
            'config_name' => 'captcha_type'
        ]);
        Assert::assertGreaterThan(0, $result, 'Bảng nv4_config không có row lang=vi, module=wallet, config_name=captcha_type!');
    }
}
