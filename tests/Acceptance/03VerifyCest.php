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

class VerifyCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify
     */
    // public function login(AcceptanceTester $I)
    // {
    //     $I->login();
    // }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify
     */
    public function verify(AcceptanceTester $I)
    {
        // Kiểm tra trình soạn thảo phải là ckeditor5-classic
        $I->seeInDatabase('nv4_authors', [
            'admin_id' => 1,
            'editor'   => 'ckeditor5-classic'
        ]);

        // Kiểm tra bảng nv4_vi_modblocks tồn tại
        $result = $I->grabFromDatabase('information_schema.tables', 'COUNT(*)', [
            'table_schema' => $_ENV['DB_NAME'],
            'table_name' => 'nv4_vi_modblocks'
        ]);
        Assert::assertGreaterThan(0, $result, "Bảng nv4_vi_modblocks không tồn tại trong database!");

        // Bảng nv4_vi_news_detail phải có cột reject_reason
        $result = $I->grabFromDatabase('information_schema.columns', 'COUNT(*)', [
            'table_schema' => $_ENV['DB_NAME'],
            'table_name' => 'nv4_vi_news_detail',
            'column_name' => 'reject_reason'
        ]);
        Assert::assertGreaterThan(0, $result, "Bảng nv4_vi_news_detail không có cột reject_reason!");

        // Kiểm tra *.ckeditor.com trong CSP
        $configValue = $I->grabFromDatabase('nv4_config', 'config_value', [
            'lang'        => 'sys',
            'module'      => 'site',
            'config_name' => 'nv_csp'
        ]);
        Assert::assertNotEmpty($configValue, 'Dòng nv_csp không tồn tại trong nv4_config');
        Assert::assertStringContainsString('*.ckeditor.com', $configValue, 'config_value không chứa *.ckeditor.com');

        // Kiểm tra bảng nv4_authors_module, dòng module=zalo cột act_2 phải bằng 1
        $act2Value = $I->grabFromDatabase('nv4_authors_module', 'act_2', [
            'module' => 'zalo'
        ]);
        Assert::assertEquals(1, $act2Value, 'Cột act_2 của module zalo không bằng 1');
    }
}
