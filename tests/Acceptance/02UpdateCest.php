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

class UpdateCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group login
     * @group update
     */
    public function login(AcceptanceTester $I)
    {
        $I->login();
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group login
     * @group update
     */
    public function update(AcceptanceTester $I)
    {
        $I->login();
        $I->amOnUrl($I->getDomain() . '/admin/');
        $I->see('Thực hiện cập nhật');
        $I->click('Thực hiện cập nhật');

        $I->waitForText('Các bước kiểm tra gói cập nhật và kiểm tra tương thích phiên bản đã hoàn tất, bạn có thể thực hiện cập nhật lên phiên bản mới', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Bạn cũng có thể bỏ qua bước này và thực hiện bước tiếp theo', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Bên dưới là danh sách các công việc sẽ thực hiện', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Bắt đầu', 5);
        $I->click('Bắt đầu');

        $I->waitForText('Bước kế tiếp', 60);
        $I->click('Bước kế tiếp');

        $I->waitForText('Nhấp vào đây để tiếp tục', 5);
        $I->click('Nhấp vào đây để tiếp tục');

        $I->waitForText('Bước kế tiếp', 60);
        $I->click('Bước kế tiếp');

        /**
         * Đợi text "Các bước thực hiện đã hoàn tất" xuất hiện trong 5s
         * quá thời điểm mà có text "Website tạm ngưng hoạt động" xuất hiện
         * Thì thực hiện lại 'update' một lần nữa
         */
        try {
            $I->waitForText('Các bước thực hiện đã hoàn tất', 2);
        } catch (\Throwable $e) {
            $this->reUpdate($I);
        }
    }

    // Xử lý riêng trường hợp bị out ra tại bản 4.5.09 do luật cookie thay đổi
    private function reUpdate(AcceptanceTester $I)
    {
        // Kiểm tra có dòng chữ "Website tạm ngưng hoạt động" xuất hiện hay không
        $I->waitForText('Website tạm ngưng hoạt động', 5);

        $I->deleteSessionSnapshot('adminLogin');

        // deleteSessionSnapshot không xóa cookie thật trong trình duyệt,
        // phải xóa hết cookie cũ (luật cookie trước 4.5.09) rồi mới đăng nhập lại
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver) {
            $webDriver->manage()->deleteAllCookies();
        });

        $I->login();
        $I->amOnUrl($I->getDomain() . '/admin/');
        $I->see('Thực hiện cập nhật');
        $I->click('Thực hiện cập nhật');

        $I->waitForText('Các bước kiểm tra gói cập nhật và kiểm tra tương thích phiên bản đã hoàn tất, bạn có thể thực hiện cập nhật lên phiên bản mới', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Bạn cũng có thể bỏ qua bước này và thực hiện bước tiếp theo', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Bên dưới là danh sách các công việc sẽ thực hiện', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Các công việc đã hoàn tất, bạn có thể chuyển sang bước tiếp theo', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('files đã được di chuyển', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Các bước thực hiện đã hoàn tất', 5);
        $I->click('Bước kế tiếp');

        $I->waitForText('Nâng cấp thành công, dưới đây là những thông tin bạn cần lưu ý', 5);
    }
}
