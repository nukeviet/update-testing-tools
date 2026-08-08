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

class Verify46Cest
{
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify4.6
     */
    // public function login(AcceptanceTester $I)
    // {
    //     $I->login();
    // }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify4.6
     */
    public function verify(AcceptanceTester $I)
    {
        // Kiểm tra bảng nv4_config không có dòng nào config_name=proxy_blocker
        $I->dontSeeInDatabase('nv4_config', [
            'lang'        => 'sys',
            'module'      => 'global',
            'config_name' => 'proxy_blocker'
        ]);

        // Bảng nv4_config phải có dòng config_name=trusted_proxy_enable
        $I->seeInDatabase('nv4_config', [
            'lang'        => 'sys',
            'module'      => 'global',
            'config_name' => 'trusted_proxy_enable'
        ]);

        // Bảng nv4_config phải có dòng config_name=trusted_proxies
        $I->seeInDatabase('nv4_config', [
            'lang'        => 'sys',
            'module'      => 'global',
            'config_name' => 'trusted_proxies'
        ]);

        // Dòng config_name=nv_csp, module=site, lang=sys phải có
        // config_value chứa chuỗi " nukeviet.vn" và  " *.nukeviet.vn"
        $configValue = $I->grabFromDatabase('nv4_config', 'config_value', [
            'lang'        => 'sys',
            'module'      => 'site',
            'config_name' => 'nv_csp'
        ]);
        Assert::assertNotEmpty($configValue, 'Dòng nv_csp không tồn tại trong nv4_config');
        Assert::assertStringContainsString(' nukeviet.vn', $configValue, 'config_value không chứa " nukeviet.vn"');
        Assert::assertStringContainsString(' *.nukeviet.vn', $configValue, 'config_value không chứa " *.nukeviet.vn"');
    }
}
