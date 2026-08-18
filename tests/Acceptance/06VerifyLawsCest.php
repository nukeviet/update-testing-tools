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

class VerifyLawsCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify-laws
     */
    public function verify(AcceptanceTester $I)
    {
        // Bảng nv4_vi_laws_row, trường bodytext phải có kiểu MEDIUMTEXT
        $dataType = $I->grabFromDatabase('information_schema.columns', 'DATA_TYPE', [
            'table_schema' => $_ENV['DB_NAME'],
            'table_name'   => 'nv4_vi_laws_row',
            'column_name'  => 'bodytext'
        ]);
        Assert::assertNotEmpty($dataType, 'Bảng nv4_vi_laws_row không có cột bodytext!');
        Assert::assertEquals('mediumtext', strtolower($dataType), 'Cột bodytext của bảng nv4_vi_laws_row không có kiểu MEDIUMTEXT!');

        // Bảng nv4_vi_laws_row phải có cột effective_status
        $result = $I->grabFromDatabase('information_schema.columns', 'COUNT(*)', [
            'table_schema' => $_ENV['DB_NAME'],
            'table_name'   => 'nv4_vi_laws_row',
            'column_name'  => 'effective_status'
        ]);
        Assert::assertGreaterThan(0, $result, 'Bảng nv4_vi_laws_row không có cột effective_status!');

        // Bảng nv4_vi_laws_config phải không có dòng nào có config_name=detail_pdf_quick_view
        $I->dontSeeInDatabase('nv4_vi_laws_config', [
            'config_name' => 'detail_pdf_quick_view'
        ]);

        // Bảng nv4_vi_laws_config phải có dòng config_name=quickview
        $I->seeInDatabase('nv4_vi_laws_config', [
            'config_name' => 'quickview'
        ]);
    }
}
