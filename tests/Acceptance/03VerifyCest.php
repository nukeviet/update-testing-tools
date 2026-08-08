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
     * @group verify4.5
     */
    // public function login(AcceptanceTester $I)
    // {
    //     $I->login();
    // }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify4.5
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

        // Kiểm tra cdn.plyr.io trong CSP
        $configValue = $I->grabFromDatabase('nv4_config', 'config_value', [
            'lang'        => 'sys',
            'module'      => 'site',
            'config_name' => 'nv_csp'
        ]);
        Assert::assertNotEmpty($configValue, 'Dòng nv_csp không tồn tại trong nv4_config');
        Assert::assertStringContainsString('cdn.plyr.io', $configValue, 'config_value không chứa cdn.plyr.io');

        // Kiểm tra bảng nv4_authors_module, dòng module=zalo cột act_2 phải bằng 1
        $act2Value = $I->grabFromDatabase('nv4_authors_module', 'act_2', [
            'module' => 'zalo'
        ]);
        Assert::assertEquals(1, $act2Value, 'Cột act_2 của module zalo không bằng 1');

        // Kiểm tra các config instant_articles đã bị xóa khỏi nv4_config
        $instantArticlesConfigs = [
            'instant_articles_active',
            'instant_articles_template',
            'instant_articles_httpauth',
            'instant_articles_username',
            'instant_articles_password',
            'instant_articles_livetime',
            'instant_articles_gettime',
            'instant_articles_auto',
        ];
        foreach ($instantArticlesConfigs as $configName) {
            $I->dontSeeInDatabase('nv4_config', [
                'lang'        => 'vi',
                'module'      => 'news',
                'config_name' => $configName,
            ]);
        }

        // Kiểm tra các cột instant_* đã bị xóa khỏi bảng nv4_vi_news_row_histories
        $instantColumns = ['instant_active', 'instant_template', 'instant_creatauto'];
        foreach ($instantColumns as $columnName) {
            $result = $I->grabFromDatabase('information_schema.columns', 'COUNT(*)', [
                'table_schema' => $_ENV['DB_NAME'],
                'table_name'   => 'nv4_vi_news_row_histories',
                'column_name'  => $columnName,
            ]);
            Assert::assertEquals(0, $result, "Bảng nv4_vi_news_row_histories vẫn còn cột {$columnName}!");
        }

        // Kiểm tra func instant-rss của module news đã bị xóa khỏi nv4_vi_modfuncs
        $I->dontSeeInDatabase('nv4_vi_modfuncs', [
            'func_name' => 'instant-rss',
            'in_module' => 'news',
        ]);

        // Kiểm tra các cột instant_* đã bị xóa khỏi bảng nv4_vi_news_rows
        $instantColumns = ['instant_active', 'instant_template', 'instant_creatauto'];
        foreach ($instantColumns as $columnName) {
            $result = $I->grabFromDatabase('information_schema.columns', 'COUNT(*)', [
                'table_schema' => $_ENV['DB_NAME'],
                'table_name'   => 'nv4_vi_news_rows',
                'column_name'  => $columnName,
            ]);
            Assert::assertEquals(0, $result, "Bảng nv4_vi_news_rows vẫn còn cột {$columnName}!");
        }

        // Kiểm tra các cột instant_* đã bị xóa khỏi bảng nv4_vi_news_1 (đại diện bảng cat)
        foreach ($instantColumns as $columnName) {
            $result = $I->grabFromDatabase('information_schema.columns', 'COUNT(*)', [
                'table_schema' => $_ENV['DB_NAME'],
                'table_name'   => 'nv4_vi_news_1',
                'column_name'  => $columnName,
            ]);
            Assert::assertEquals(0, $result, "Bảng nv4_vi_news_1 vẫn còn cột {$columnName}!");
        }
    }
}
