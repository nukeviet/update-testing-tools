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

class SetModuleCest
{
    /**
     * @param AcceptanceTester $I
     * @return void
     */
    public function _before(AcceptanceTester $I)
    {
    }

    /**
     * @param AcceptanceTester $I
     * @param string $moduleName
     * @param int $sample 1: Cài kèm dữ liệu mẫu (nếu module có), 0: chỉ thiết lập module
     * @return void
     */
    private function installModule(AcceptanceTester $I, string $moduleName, int $sample = 1)
    {
        $I->wantTo('Install module ' . $moduleName . ' for testing');

        $I->login();
        $I->amOnUrl($I->getDomain() . '/admin/index.php?language=vi&nv=modules&op=setup');

        // Click vào thẻ a có class "nv-setup-module" và thuộc tính data-title="$moduleName"
        $link = '//a[contains(@class, "nv-setup-module") and @data-title="' . $moduleName . '"]';
        $I->waitForElementVisible($link, 10);
        $I->scrollTo($link);
        $I->click($link);

        // Click ở trên chạy ajax check_sample_data rồi mới xử lý tiếp, kết quả có hai khả năng:
        // - Module không có dữ liệu mẫu: JS chuyển thẳng sang trang thiết lập module
        // - Module có dữ liệu mẫu: hiện modal #modal-setup-module để chọn phương án thiết lập
        // Chờ đến khi một trong hai xảy ra
        $I->waitForElementVisible('#mod_name, #modal-setup-module .submit', 15);

        if (empty($I->executeJS('return document.getElementById("mod_name") ? 1 : 0;'))) {
            // Đợi modal chạy xong hiệu ứng fade in
            $I->wait(0.5);

            // Chọn phương án thiết lập rồi bấm nút "Thực hiện" của modal
            $I->selectOption('#modal-setup-module .option', (string) $sample);
            $I->waitForElementClickable('#modal-setup-module .submit', 10);
            $I->click('#modal-setup-module .submit');
        }

        // Thiết lập xong hệ thống chuyển sang trang cấu hình module
        $I->waitForElementVisible('#mod_name', 15);
        $I->seeInField('#mod_name', $moduleName);
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group install-module-download
     * @group all
     */
    public function installModuleDownload(AcceptanceTester $I)
    {
        $this->installModule($I, 'download');
    }

    /**
     * @param AcceptanceTester $I
     *
     * @group install-module-laws
     * @group all
     */
    public function installModuleLaws(AcceptanceTester $I)
    {
        $this->installModule($I, 'laws');
    }
}
