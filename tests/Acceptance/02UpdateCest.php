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

        // Click sang bước thông tin sao lưu
        $I->waitForText('Các bước kiểm tra gói cập nhật và kiểm tra tương thích phiên bản đã hoàn tất, bạn có thể thực hiện cập nhật lên phiên bản mới', 5);
        $I->click('Bước kế tiếp');

        // Click sang bước xem danh sách công việc CSDL + File
        $I->waitForText('Bạn cũng có thể bỏ qua bước này và thực hiện bước tiếp theo', 5);
        $I->click('Bước kế tiếp');

        // Click sang bước thực hiện cập nhật CSDL
        $I->waitForText('Bên dưới là danh sách các công việc sẽ thực hiện', 5);
        $I->click('Bước kế tiếp');

        // Click bắt đầu cập nhật CSDL
        $I->waitForText('Bắt đầu', 5);
        $I->click('Bắt đầu');

        // Click sang bước thực hiện di chuyển file
        $I->waitForText('Bước kế tiếp', 60);
        $I->click('Bước kế tiếp');

        // Click bắt đầu di chuyển file
        $I->waitForText('Nhấp vào đây để tiếp tục', 5);
        $I->click('Nhấp vào đây để tiếp tục');

        // Click sang bước cuối cùng
        $I->waitForText('Bước kế tiếp', 60);
        $I->click('Bước kế tiếp');

        /**
         * Nếu có những dòng chữ này thì thành công, xóa gói cập nhật
         * Nếu không thì bắt đầu quy trình reUpdate
         */
        $arrayTryText = [
            // 'Các bước thực hiện đã hoàn tất',
            'Nâng cấp thành công, dưới đây là những thông tin bạn cần lưu ý',
        ];
        $tryOffset = 0;
        $countTry = count($arrayTryText);
        while ($tryOffset < $countTry) {
            try {
                // Đợi thông báo thành công
                $I->waitForText($arrayTryText[$tryOffset], 5);

                // Click nút xóa gói cập nhật
                $I->click('Xóa gói cập nhật');

                // Alert của javascript bật lên hỏi "Bạn thực sự muốn xóa" thì click ok
                $I->acceptPopup();

                // Có chữ "Gói cập nhật đã được xóa khỏi hệ thống thành công" thì coi như thành công
                $I->waitForText('Gói cập nhật đã được xóa khỏi hệ thống thành công', 5);
                return;
            } catch (\Throwable $e) {
                $tryOffset++;
            }
        }

        // Bị tạm ngừng hoạt động thì cập nhật lại từ bước login admin
        $this->reUpdate($I);
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
