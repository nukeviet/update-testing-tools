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

class VerifyVideoclipsCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group verify-videoclips
     */
    public function verify(AcceptanceTester $I)
    {
        // Bảng nv4_vi_videoclips_hit phải có cột ratio_w_h
        $result = $I->grabFromDatabase('information_schema.columns', 'COUNT(*)', [
            'table_schema' => $_ENV['DB_NAME'],
            'table_name'   => 'nv4_vi_videoclips_hit',
            'column_name'  => 'ratio_w_h'
        ]);
        Assert::assertGreaterThan(0, $result, 'Bảng nv4_vi_videoclips_hit không có cột ratio_w_h!');
    }
}
