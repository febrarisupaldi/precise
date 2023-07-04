<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "Can't display API";
});
Route::post('test', 'Api\Master\TestController@testBuilder');
Route::post('send-message', 'Api\UtilityController@sendMessageTelegram');
Route::group(
    [
        'middleware' => 'api'
    ],
    function () {
        Route::group(
            ['prefix' => 'auth'],
            function () {
                Route::post('login', 'Api\Auth\AuthController@login');
                Route::post('logout', 'Api\Auth\AuthController@logout');
                Route::post('refresh', 'Api\Auth\AuthController@refresh');
                Route::post('me', 'Api\Auth\AuthController@me');
                Route::post('resend-otp', 'Api\Auth\AuthController@sendMessageTelegram');
            }
        );
        Route::group(
            ['prefix' => 'application', 'middleware' => 'jwt'],
            function () {
                Route::get('setting/version', 'Api\Application\SettingController@version');
                Route::get('setting/logging', 'Api\Application\SettingController@logging');
                Route::post('setting/access', 'Api\Application\SettingController@access');
                Route::post('setting/user', 'Api\Application\SettingController@user');
                Route::post('setting/warehouse', 'Api\Application\SettingController@warehouse');
                Route::post('setting/workcenter', 'Api\Application\SettingController@workcenter');

                Route::post('log/error', 'Api\Application\LogController@error');
                Route::post('log/menu', 'Api\Application\LogController@menu');
                Route::post('log/menu-action', 'Api\Application\LogController@menuAction');

                Route::get('error', 'Api\Application\ApplicationController@error');
                Route::get('time', 'Api\Application\ApplicationController@serverTime');
                Route::post('value-increment', 'Api\Application\ApplicationController@autoIncrement');
                Route::get('variable', 'Api\Application\ApplicationController@globalVariabel');
            }
        );

        Route::group(
            ['prefix' => 'engineering', 'middleware' => 'jwt'],
            function () {
                Route::get('mold-repair-request', 'Api\Engineering\MoldRepairRequestController@index');
                Route::get('mold-repair-request/check', 'Api\Engineering\MoldRepairRequestController@check');
                Route::get('mold-repair-request/{id}', 'Api\Engineering\MoldRepairRequestController@show');
                Route::post('mold-repair-request', 'Api\Engineering\MoldRepairRequestController@create');
                Route::put('mold-repair-request', 'Api\Engineering\MoldRepairRequestController@update');
                Route::delete('mold-repair-request', 'Api\Engineering\MoldRepairRequestController@destroy');

                //Machine Injection Activity
                Route::post('machine-injection-activity', 'Api\Engineering\MachineInjectionActivityController@create');

                //Machine Pressing Activity
                Route::post('machine-pressing-activity', 'Api\Engineering\MachinePressingActivityController@create');
            }
        );

        Route::group(
            ['prefix' => 'hrd', 'middleware' => 'jwt'],
            function () {
                Route::get('leaves/{id}', 'Api\HR\LeavesController@show');
                Route::post('attendance', 'Api\HR\AttendanceController@create');
            }
        );

        Route::group(
            ['prefix' => 'logistic', 'middleware' => 'jwt'],
            function () {
                Route::get('monitoring-sales-order/released', 'Api\Logistic\MonitoringSalesOrderController@getReleasedSO');
                Route::get('monitoring-sales-order/released-item', 'Api\Logistic\MonitoringSalesOrderController@getReleasedItem');
                Route::get('monitoring-sales-order/picker/{id}', 'Api\Logistic\MonitoringSalesOrderController@getPicker');
                Route::get('monitoring-sales-order/packer/{id}', 'Api\Logistic\MonitoringSalesOrderController@getPacker');
                Route::put('monitoring-sales-order/picker', 'Api\Logistic\MonitoringSalesOrderController@updateSOPicker');
                Route::put('monitoring-sales-order/status', 'Api\Logistic\MonitoringSalesOrderController@updatePickUpStatus');

                Route::put('production-storage', 'Api\Logistic\ProductionStorageController@update');

                Route::get('stock-diagram/outstanding-sales-order/warehouse/{whid}/product/{productid}', 'Api\Logistic\StockDiagramController@getOutstandingSO');
                Route::get('stock-diagram/delivery-order-in-process/warehouse/{whid}/product/{productid}', 'Api\Logistic\StockDiagramController@getDOInProcess');
                Route::get('stock-diagram/sales-order-history/warehouse/{whid}/product/{productid}', 'Api\Logistic\StockDiagramController@getSOHistory');
                Route::get('stock-diagram/warehouse/{id}', 'Api\Logistic\StockDiagramController@index');
                Route::get('stock-diagram/2/warehouse/{id}', 'Api\Logistic\StockDiagramController@getStockDiagram2');
                Route::get('stock-diagram/2/item-code', 'Api\Logistic\StockDiagramController@getStockDiagram2ByItemCode');

                Route::get('stock-mutation/card/warehouse/{whid}/product/{productid}', 'Api\Logistic\StockMutationController@getStockCard');
                Route::get('stock-mutation/warehouse/{whid}', 'Api\Logistic\StockMutationController@index');

                Route::get('warehouse-transfer-request/in/warehouse/{id}', 'Api\Logistic\WarehouseTransferRequestController@showRequestIn');
                Route::get('warehouse-transfer-request/out/warehouse/{id}', 'Api\Logistic\WarehouseTransferRequestController@showRequestOut');
                Route::get('warehouse-transfer-request/in/detail/warehouse/{id}', 'Api\Logistic\WarehouseTransferRequestController@showDetailRequestIn');
                Route::get('warehouse-transfer-request/out/detail/warehouse/{id}', 'Api\Logistic\WarehouseTransferRequestController@showDetailRequestOut');
                Route::get('warehouse-transfer-request/out/handed/warehouse/{id}', 'Api\Logistic\WarehouseTransferRequestController@showRequestOutForHanded');
                Route::get('warehouse-transfer-request/out/incoming-request/warehouse/{id}', 'Api\Logistic\WarehouseTransferRequestController@showRequestOutForIncomingRequest');
                Route::get('warehouse-transfer-request/check', 'Api\Logistic\WarehouseTransferRequestController@check');
                Route::get('warehouse-transfer-request/current-stock/product/{product}/warehouse/{warehouse}', 'Api\Logistic\WarehouseTransferRequestController@currentStock');
                Route::get('warehouse-transfer-request/{id}', 'Api\Logistic\WarehouseTransferRequestController@show');
                Route::post('warehouse-transfer-request', 'Api\Logistic\WarehouseTransferRequestController@create');
                Route::put('warehouse-transfer-request', 'Api\Logistic\WarehouseTransferRequestController@update');
                Route::delete('warehouse-transfer-request', 'Api\Logistic\WarehouseTransferRequestController@destroy');
                // Route::get('warehouse-transfer-out/type/{type}/from/{from}', 'Api\Logistic\WarehouseTransferOutController@index');
                // Route::get('warehouse-transfer-out/detail/type/{type}/from/{from}', 'Api\Logistic\WarehouseTransferOutController@index');
                // Route::get('warehouse-transfer-out/check', 'Api\Logistic\WarehouseTransferOutController@check');
                // Route::get('warehouse-transfer-out/{id}', 'Api\Logistic\WarehouseTransferOutController@showDetailOnly');

                Route::get('warehouse-rack/code/{code}', 'Api\Logistic\WarehouseRackController@getByCode');

                Route::get('warehouse-rack-in/cummulative/date/{date}', 'Api\Logistic\WarehouseRackInController@getCummulativeByDate');
                Route::get('warehouse-rack-in/{id}/date/{date}', 'Api\Logistic\WarehouseRackInController@show');
                Route::post('warehouse-rack-in', 'Api\Logistic\WarehouseRackInController@create');
                Route::delete('warehouse-rack-in', 'Api\Logistic\WarehouseRackInController@destroy');
            }
        );
        Route::group(
            ['prefix' => 'master', 'middleware' => 'jwt'],
            function () {

                //Address type
                Route::get('address-type', 'Api\Master\AddressTypeController@index');
                Route::get('address-type/check', 'Api\Master\AddressTypeController@check');
                Route::get('address-type/{id}', 'Api\Master\AddressTypeController@show');
                Route::post('address-type', 'Api\Master\AddressTypeController@create');
                Route::put('address-type', 'Api\Master\AddressTypeController@update');
                Route::delete('address-type', 'Api\Master\AddressTypeController@destroy');

                //Bank
                Route::get('bank', 'Api\Master\BankController@index');

                //Barcode
                Route::get('barcode', 'Api\Master\BarcodeController@index');
                Route::get('barcode/ean13/{ean13}', 'Api\Master\BarcodeController@showByEan13');
                Route::get('barcode/check', 'Api\Master\BarcodeController@check');
                Route::get('barcode/{id}', 'Api\Master\BarcodeController@show');
                Route::post('barcode', 'Api\Master\BarcodeController@create');
                Route::post('barcode/generate', 'Api\Master\BarcodeController@convert');
                Route::put('barcode', 'Api\Master\BarcodeController@update');
                Route::delete('barcode', 'Api\Master\BarcodeController@destroy');

                //BOM
                Route::get('bom', 'Api\Master\BOMController@index');
                Route::get('bom/check', 'Api\Master\BOMController@check');
                Route::get('bom/detail/{id}', 'Api\Master\BOMController@showDetailByBOM');
                Route::get('bom/product/{id}', 'Api\Master\BOMController@showByProduct');
                Route::get('bom/workcenter/detail/{id}', 'Api\Master\BOMController@showDetailByWorkcenter');
                Route::get('bom/workcenter/{id}', 'Api\Master\BOMController@showByWorkcenter');
                Route::get('bom/{id}', 'Api\Master\BOMController@show');
                Route::post('bom', 'Api\Master\BOMController@create');
                Route::put('bom', 'Api\Master\BOMController@update');
                Route::delete('bom', 'Api\Master\BOMController@destroy');

                //city
                Route::get('city', 'Api\Master\CityController@index');
                Route::get('city/check', 'Api\Master\CityController@check');
                Route::get('city/{id}', 'Api\Master\CityController@show');
                Route::post('city', 'Api\Master\CityController@create');
                Route::put('city', 'Api\Master\CityController@update');

                //COA
                Route::get('coa', 'Api\Master\COAController@index');

                //Color Type
                Route::get('color-type', 'Api\Master\ColorTypeController@index');
                Route::get('color-type/check', 'Api\Master\ColorTypeController@check');
                Route::get('color-type/{id}', 'Api\Master\ColorTypeController@show');
                Route::post('color-type', 'Api\Master\ColorTypeController@create');
                Route::put('color-type', 'Api\Master\ColorTypeController@update');
                Route::delete('color-type', 'Api\Master\ColorTypeController@destroy');

                //Company Type
                Route::get('company-type', 'Api\Master\CompanyTypeController@index');
                Route::get('company-type/check', 'Api\Master\CompanyTypeController@check');
                Route::get('company-type/{id}', 'Api\Master\CompanyTypeController@show');
                Route::post('company-type', 'Api\Master\CompanyTypeController@create');
                Route::put('company-type', 'Api\Master\CompanyTypeController@update');

                //Cooling Method
                Route::get('cooling-method', 'Api\Master\CoolingMethodController@index');
                Route::get('cooling-method/check', 'Api\Master\CoolingMethodController@check');
                Route::get('cooling-method/{id}', 'Api\Master\CoolingMethodController@show');
                Route::post('cooling-method', 'Api\Master\CoolingMethodController@create');
                Route::put('cooling-method', 'Api\Master\CoolingMethodController@update');


                //Cost Center
                Route::get('cost-center/{id}', 'Api\Master\CostCenterController@showByMultipleID');

                //country
                Route::get('country', 'Api\Master\CountryController@index');
                Route::get('country/check', 'Api\Master\CountryController@check');
                Route::get('country/{id}', 'Api\Master\CountryController@show');
                Route::post('country', 'Api\Master\CountryController@create');
                Route::put('country', 'Api\Master\CountryController@update');

                //Customer
                Route::get('customer', 'Api\Master\CustomerController@index');
                Route::get('customer/{id}', 'Api\Master\CustomerController@show');
                Route::get('customer/retail-type/{id}', 'Api\Master\CustomerController@showByRetail');

                //Customer Address
                Route::get('customer-address', 'Api\Master\CustomerAddressController@index');
                Route::get('customer-address/customer/{id}', 'Api\Master\CustomerAddressController@showByCustomerID');
                Route::get('customer-address/{id}', 'Api\Master\CustomerAddressController@show');
                Route::post('customer-address', 'Api\Master\CustomerAddressController@create');
                Route::put('customer-address', 'Api\Master\CustomerAddressController@update');
                Route::delete('customer-address', 'Api\Master\CustomerAddressController@destroy');

                //Customer Group
                Route::get('customer-group', 'Api\Master\CustomerGroupController@index');
                Route::get('customer-group/check', 'Api\Master\CustomerGroupController@check');
                Route::get('customer-group/{id}', 'Api\Master\CustomerGroupController@show');
                Route::post('customer-group', 'Api\Master\CustomerGroupController@create');
                Route::put('customer-group', 'Api\Master\CustomerGroupController@update');
                Route::delete('customer-group', 'Api\Master\CustomerGroupController@destroy');

                //Customer Group Member
                Route::get('customer-group-member/{id}', 'Api\Master\CustomerGroupMemberController@show');
                Route::post('customer-group-member', 'Api\Master\CustomerGroupMemberController@create');
                Route::delete('customer-group-member/{id}', 'Api\Master\CustomerGroupMemberController@destroy');
                Route::get('customer-group-member/check', 'Api\Master\CustomerGroupMemberController@check');

                //Downtime
                Route::get('downtime', 'Api\Master\DowntimeController@index');
                Route::get('downtime/check', 'Api\Master\DowntimeController@check');
                Route::get('downtime/{id}', 'Api\Master\DowntimeController@show');
                Route::post('downtime', 'Api\Master\DowntimeController@create');
                Route::put('downtime', 'Api\Master\DowntimeController@update');
                Route::delete('downtime', 'Api\Master\DowntimeController@destroy');

                //Downtime Group
                Route::get('downtime-group', 'Api\Master\DowntimeGroupController@index');
                Route::get('downtime-group/check', 'Api\Master\DowntimeGroupController@check');
                Route::get('downtime-group/{id}', 'Api\Master\DowntimeGroupController@show');
                Route::post('downtime-group', 'Api\Master\DowntimeGroupController@create');
                Route::put('downtime-group', 'Api\Master\DowntimeGroupController@update');
                Route::delete('downtime-group', 'Api\Master\DowntimeGroupController@destroy');

                //Downtime Group
                Route::get('downtime-approval-status', 'Api\Master\DowntimeApprovalStatusController@index');
                Route::get('downtime-approval-status/check', 'Api\Master\DowntimeApprovalStatusController@check');
                Route::get('downtime-approval-status/{id}', 'Api\Master\DowntimeApprovalStatusController@show');
                Route::post('downtime-approval-status', 'Api\Master\DowntimeApprovalStatusController@create');
                Route::put('downtime-approval-status', 'Api\Master\DowntimeApprovalStatusController@update');
                Route::delete('downtime-approval-status', 'Api\Master\DowntimeApprovalStatusController@destroy');

                //Driving License
                Route::get('department', 'Api\Master\DepartmentController@index');

                //Driver
                Route::get('driver', 'Api\Master\DriverController@index');
                Route::get('driver/check', 'Api\Master\DriverController@check');
                Route::get('driver/{id}', 'Api\Master\DriverController@show');
                Route::post('driver', 'Api\Master\DriverController@create');
                Route::put('driver', 'Api\Master\DriverController@update');

                //Driving License
                Route::get('driving-license', 'Api\Master\DrivingLicenseController@index');

                //Employee
                Route::get('employee', 'Api\Master\EmployeeController@index');
                Route::get('employee/{id}', 'Api\Master\EmployeeController@show');
                Route::get('employee/name/{name}', 'Api\Master\EmployeeController@showByName');
                Route::get('employee/superior/{nip}', 'Api\Master\EmployeeController@showSuperByNIP');
                Route::get('employee/check', 'Api\Master\EmployeeController@check');

                //Employment Status
                Route::get('employment-status', 'Api\Master\EmploymentStatusController@index');

                //Gender
                Route::get('gender', 'Api\Master\GenderController@index');

                //Health Insurance
                Route::get('health-insurance', 'Api\Master\HealthInsuranceController@index');


                //machine
                Route::get('machine', 'Api\Master\MachineController@index');
                Route::get('machine/check', 'Api\Master\MachineController@check');
                Route::get('machine/production-type/{type}', 'Api\Master\MachineController@showByProductionType');
                Route::get('machine/workcenter', 'Api\Master\MachineController@showByWorkcenter');
                Route::get('machine/{id}', 'Api\Master\MachineController@show');
                Route::post('machine', 'Api\Master\MachineController@create');
                Route::put('machine', 'Api\Master\MachineController@update');

                //Machine Status
                Route::get('machine-status', 'Api\Master\MachineStatusController@index');
                Route::get('machine-status/check', 'Api\Master\MachineStatusController@check');
                Route::get('machine-status/{id}', 'Api\Master\MachineStatusController@show');
                Route::post('machine-status', 'Api\Master\MachineStatusController@create');
                Route::put('machine-status', 'Api\Master\MachineStatusController@update');

                //Machine Tonnage
                Route::get('machine-tonnage', 'Api\Master\MachineTonnageController@index');
                Route::get('machine-tonnage/check', 'Api\Master\MachineTonnageController@check');
                Route::get('machine-tonnage/{id}', 'Api\Master\MachineTonnageController@show');
                Route::post('machine-tonnage', 'Api\Master\MachineTonnageController@create');
                Route::put('machine-tonnage', 'Api\Master\MachineTonnageController@update');

                //Machine Injection
                Route::get('machine-injection', 'Api\Master\MachineInjectionController@index');
                Route::get('machine-injection/check', 'Api\Master\MachineInjectionController@check');
                Route::get('machine-injection/code/{code}', 'Api\Master\MachineInjectionController@showByCode');
                Route::get('machine-injection/{id}', 'Api\Master\MachineInjectionController@show');
                Route::post('machine-injection', 'Api\Master\MachineInjectionController@create');
                Route::put('machine-injection', 'Api\Master\MachineInjectionController@update');

                //Machine Pressing
                Route::get('machine-pressing', 'Api\Master\MachinePressingController@index');
                Route::get('machine-pressing/check', 'Api\Master\MachinePressingController@check');
                Route::get('machine-pressing/code/{code}', 'Api\Master\MachinePressingController@showByCode');
                Route::get('machine-pressing/{id}', 'Api\Master\MachinePressingController@show');
                Route::post('machine-pressing', 'Api\Master\MachinePressingController@create');
                Route::put('machine-pressing', 'Api\Master\MachinePressingController@update');

                //menu
                Route::get('menu', 'Api\Master\MenuController@index');
                Route::get('menu/check', 'Api\Master\MenuController@check');
                Route::get('menu/{id}', 'Api\Master\MenuController@show');
                Route::post('menu', 'Api\Master\MenuController@create');
                Route::put('menu', 'Api\Master\MenuController@update');
                Route::delete('menu', 'Api\Master\MenuController@destroy');

                //Mold
                Route::get('mold/check', 'Api\Master\MoldController@check');
                Route::get('mold/product-item/{id}', 'Api\Master\MoldController@showByProductItem');
                Route::get('mold/workcenter/full/{id}', 'Api\Master\MoldController@joined');
                Route::get('mold/workcenter/{id}', 'Api\Master\MoldController@index');
                Route::get('mold/{id}', 'Api\Master\MoldController@show');
                Route::post('mold', 'Api\Master\MoldController@create');
                Route::post('mold/detail', 'Api\Master\MoldController@update');

                //Mold Injection

                Route::get('mold-injection', 'Api\Master\MoldInjectionController@index');
                Route::get('mold-injection/detail', 'Api\Master\MoldInjectionController@detail');
                Route::get('mold-injection/number/{number}', 'Api\Master\MoldInjectionController@showByNumber');
                Route::post('mold-injection', 'Api\Master\MoldInjectionController@create');

                //Mold Pressing
                Route::get('mold-pressing', 'Api\Master\MoldPressingController@index');
                Route::get('mold-pressing/check', 'Api\Master\MoldPressingController@check');
                Route::get('mold-pressing/cavity/code/{code}', 'Api\Master\MoldPressingController@showMoldCavityByCode');
                Route::get('mold-pressing/cavity/number/{number}', 'Api\Master\MoldPressingController@showMoldCavityByNumber');
                Route::get('mold-pressing/detail', 'Api\Master\MoldPressingController@full');
                Route::get('mold-pressing/number/{number}', 'Api\Master\MoldPressingController@showByNumber');
                Route::get('mold-pressing/{id}', 'Api\Master\MoldPressingController@show');
                Route::post('mold-pressing', 'Api\Master\MoldPressingController@create');
                Route::put('mold-pressing', 'Api\Master\MoldPressingController@update');
                Route::delete('mold-pressing/{id}', 'Api\Master\MoldPressingController@destroy');

                //Mold Status
                Route::get('mold-status', 'Api\Master\MoldStatusController@index');
                Route::get('mold-status/check', 'Api\Master\MoldStatusController@check');
                Route::get('mold-status/{id}', 'Api\Master\MoldStatusController@show');
                Route::post('mold-status', 'Api\Master\MoldStatusController@create');
                Route::put('mold-status', 'Api\Master\MoldStatusController@update');

                //Packaging
                Route::get('packaging', 'Api\Master\PackagingController@index');
                Route::get('packaging/check', 'Api\Master\PackagingController@check');
                Route::get('packaging/detail', 'Api\Master\PackagingController@detail');
                Route::get('packaging/{id}', 'Api\Master\PackagingController@show');
                Route::post('packaging', 'Api\Master\PackagingController@create');
                Route::put('packaging', 'Api\Master\PackagingController@update');

                //Position
                Route::get('position', 'Api\Master\PositionController@index');

                //Privilege
                Route::get('privilege', 'Api\Master\PrivilegeController@index');
                Route::get('privilege/user', 'Api\Master\PrivilegeController@user');
                Route::get('privilege/user/{id}', 'Api\Master\PrivilegeController@showMenuByUser');
                Route::get('privilege/menu/{id}', 'Api\Master\PrivilegeController@showUserByMenu');
                Route::post('privilege', 'Api\Master\PrivilegeController@create');
                Route::post('privilege/copy', 'Api\Master\PrivilegeController@copy');

                //Privilege Warehouse
                Route::get('privilege-warehouse', 'Api\Master\PrivilegeWarehouseController@index');
                Route::get('privilege-warehouse/check', 'Api\Master\PrivilegeWarehouseController@check');
                Route::get('privilege-warehouse/{id}', 'Api\Master\PrivilegeWarehouseController@show');
                Route::post('privilege-warehouse', 'Api\Master\PrivilegeWarehouseController@create');
                Route::put('privilege-warehouse', 'Api\Master\PrivilegeWarehouseController@update');
                Route::delete('privilege-warehouse', 'Api\Master\PrivilegeWarehouseController@update');

                //Privilege Workcenter
                Route::get('privilege-workcenter', 'Api\Master\PrivilegeWorkcenterController@index');
                Route::get('privilege-workcenter/check', 'Api\Master\PrivilegeWorkcenterController@check');
                Route::get('privilege-workcenter/{id}', 'Api\Master\PrivilegeWorkcenterController@show');
                Route::post('privilege-workcenter', 'Api\Master\PrivilegeWorkcenterController@create');
                Route::put('privilege-workcenter', 'Api\Master\PrivilegeWorkcenterController@update');
                Route::delete('privilege-workcenter', 'Api\Master\PrivilegeWorkcenterController@destroy');

                //Product
                Route::get('product', 'Api\Master\ProductController@index');
                Route::get('product/check', 'Api\Master\ProductController@check');
                Route::get('product/product-group', 'Api\Master\ProductController@showByProductGroup');
                Route::get('product/product-type', 'Api\Master\ProductController@showByProductType');
                Route::get('product/product-type/group', 'Api\Master\ProductController@showByProductTypeWithGroup');
                Route::get('product/warehouse', 'Api\Master\ProductController@showByWarehouse');
                Route::get('product/workcenter', 'Api\Master\ProductController@showByWorkcenter');
                Route::get('product/customer/{id}', 'Api\Master\ProductController@showByCustomer');
                Route::get('product/{id}', 'Api\Master\ProductController@show');
                Route::post('product', 'Api\Master\ProductController@create');
                Route::put('product', 'Api\Master\ProductController@update');
                Route::delete('product', 'Api\Master\ProductController@destroy');

                //Product Appearance
                Route::get('product-appearance', 'Api\Master\ProductAppearanceController@index');
                Route::get('product-appearance/{id}', 'Api\Master\ProductAppearanceController@show');
                Route::post('product-appearance', 'Api\Master\ProductAppearanceController@create');
                Route::put('product-appearance', 'Api\Master\ProductAppearanceController@update');

                //Product Brand
                Route::get('product-brand', 'Api\Master\ProductBrandController@index');
                Route::get('product-brand/check', 'Api\Master\ProductBrandController@check');
                Route::get('product-brand/{id}', 'Api\Master\ProductBrandController@show');
                Route::post('product-brand', 'Api\Master\ProductBrandController@create');
                Route::put('product-brand', 'Api\Master\ProductBrandController@update');

                //Product Customer
                Route::get('product-customer', 'Api\Master\ProductCustomerController@index');
                Route::get('product-customer/check', 'Api\Master\ProductCustomerController@check');
                Route::get('product-customer/customer/{id}', 'Api\Master\ProductCustomerController@showCustomer');
                Route::get('product-customer/{id}', 'Api\Master\ProductCustomerController@show');
                Route::post('product-customer', 'Api\Master\ProductCustomerController@create');
                Route::put('product-customer', 'Api\Master\ProductCustomerController@update');
                Route::delete('product-customer', 'Api\Master\ProductCustomerController@destroy');

                //Product Design
                Route::get('product-design/check', 'Api\Master\ProductDesignController@check');
                Route::get('product-design', 'Api\Master\ProductDesignController@index');
                Route::get('product-design', 'Api\Master\ProductDesignController@index');
                Route::get('product-design/{id}', 'Api\Master\ProductDesignController@show');
                Route::post('product-design', 'Api\Master\ProductDesignController@create');
                Route::put('product-design', 'Api\Master\ProductDesignController@update');
                Route::delete('product-design', 'Api\Master\ProductDesignController@destroy');

                //Product Dictionary
                Route::get('product-dictionary', 'Api\Master\ProductDictionaryController@index');
                Route::get('product-dictionary/product/{id}', 'Api\Master\ProductDictionaryController@showByProductID');
                Route::get('product-dictionary/{id}', 'Api\Master\ProductDictionaryController@show');
                Route::post('product-dictionary', 'Api\Master\ProductDictionaryController@create');
                Route::put('product-dictionary', 'Api\Master\ProductDictionaryController@update');
                Route::delete('product-dictionary', 'Api\Master\ProductDictionaryController@destroy');

                //Product Group
                Route::get('product-group', 'Api\Master\ProductGroupController@index');
                Route::get('product-group/check', 'Api\Master\ProductGroupController@check');
                Route::get('product-group/{id}', 'Api\Master\ProductGroupController@show');
                Route::post('product-group', 'Api\Master\ProductGroupController@create');
                Route::put('product-group', 'Api\Master\ProductGroupController@update');

                //Production Process Type
                Route::get('production-process-type', 'Api\Master\ProductionProcessTypeController@index');
                Route::get('production-process-type/production/{id}', 'Api\Master\ProductionProcessTypeController@showByProductionType');

                //Product Item
                Route::get('product-item', 'Api\Master\ProductItemController@index');
                Route::get('product-item/kind/{id}', 'Api\Master\ProductItemController@showByKindCode');
                Route::get('product-item/check', 'Api\Master\ProductItemController@check');
                Route::get('product-item/{id}', 'Api\Master\ProductItemController@show');
                Route::post('product-item', 'Api\Master\ProductItemController@create');
                Route::put('product-item', 'Api\Master\ProductItemController@update');
                Route::delete('product-item', 'Api\Master\ProductItemController@destroy');

                //Product License Type
                Route::get('product-license-type', 'Api\Master\ProductItemController@index');

                //Product Series
                Route::get('product-series', 'Api\Master\ProductSeriesController@index');
                Route::get('product-series/check', 'Api\Master\ProductSeriesController@check');
                Route::get('product-series/{id}', 'Api\Master\ProductSeriesController@show');
                Route::post('product-series', 'Api\Master\ProductSeriesController@create');
                Route::put('product-series', 'Api\Master\ProductSeriesController@update');
                Route::delete('product-series', 'Api\Master\ProductSeriesController@destroy');

                //Product Type
                Route::get('product-type', 'Api\Master\ProductTypeController@index');
                Route::get('product-type/check', 'Api\Master\ProductTypeController@check');
                Route::get('product-type/{id}', 'Api\Master\ProductTypeController@show');
                Route::post('product-type', 'Api\Master\ProductTypeController@create');
                Route::put('product-type', 'Api\Master\ProductTypeController@update');

                //Product Variant
                Route::get('product-variant', 'Api\Master\ProductVariantController@index');
                Route::get('product-variant/check', 'Api\Master\ProductVariantController@check');
                Route::get('product-variant/{id}', 'Api\Master\ProductVariantController@show');
                Route::post('product-variant', 'Api\Master\ProductVariantController@create');
                Route::put('product-variant', 'Api\Master\ProductVariantController@update');

                //Product Workcenter
                Route::get('product-workcenter/check', 'Api\Master\ProductWorkcenterController@check');
                Route::get('product-workcenter/workcenter/{id}', 'Api\Master\ProductWorkcenterController@index');
                Route::get('product-workcenter/{id}', 'Api\Master\ProductWorkcenterController@show');
                Route::post('product-workcenter', 'Api\Master\ProductWorkcenterController@create');
                Route::put('product-workcenter', 'Api\Master\ProductWorkcenterController@update');
                Route::delete('product-workcenter', 'Api\Master\ProductWorkcenterController@destroy');

                //Purchase Type
                Route::get('purchase-type', 'Api\Master\PurchaseTypeController@index');

                //Reject
                Route::get('reject', 'Api\Master\RejectController@index');
                Route::get('reject/check', 'Api\Master\RejectController@check');
                Route::get('reject/{id}', 'Api\Master\RejectController@show');
                Route::post('reject', 'Api\Master\RejectController@create');
                Route::put('reject', 'Api\Master\RejectController@update');
                Route::delete('reject', 'Api\Master\RejectController@destroy');

                //Reject
                Route::get('reject-group', 'Api\Master\RejectGroupController@index');
                Route::get('reject-group/check', 'Api\Master\RejectGroupController@check');
                Route::get('reject-group/{id}', 'Api\Master\RejectGroupController@show');
                Route::post('reject-group', 'Api\Master\RejectGroupController@create');
                Route::put('reject-group', 'Api\Master\RejectGroupController@update');
                Route::delete('reject-group', 'Api\Master\RejectGroupController@destroy');

                //Religion
                Route::get('religion', 'Api\Master\ReligionController@index');

                //Retail Type
                Route::get('retail-type', 'Api\Master\RetailTypeController@index');
                Route::get('retail-type/check', 'Api\Master\RetailTypeController@check');
                Route::get('retail-type/{id}', 'Api\Master\RetailTypeController@show');
                Route::post('retail-type', 'Api\Master\RetailTypeController@create');
                Route::put('retail-type', 'Api\Master\RetailTypeController@update');

                //Salary Status
                Route::get('salary-status', 'Api\Master\SalaryStatusController@index');

                //Salesman
                Route::get('salesman', 'Api\Master\SalesmanController@index');

                //Section
                Route::get('section/department/{id}', 'Api\Master\SectionController@showByDepartment');

                //State
                Route::get('state', 'Api\Master\StateController@index');
                Route::get('state/check', 'Api\Master\StateController@check');
                Route::get('state/{id}', 'Api\Master\StateController@show');
                Route::post('state', 'Api\Master\StateController@create');
                Route::put('state', 'Api\Master\StateController@update');

                //Steel Type
                Route::get('steel-type', 'Api\Master\SteelTypeController@index');
                Route::get('steel-type/check', 'Api\Master\SteelTypeController@check');
                Route::get('steel-type/{id}', 'Api\Master\SteelTypeController@show');
                Route::post('steel-type', 'Api\Master\SteelTypeController@create');
                Route::put('steel-type', 'Api\Master\SteelTypeController@update');

                //Technical Standard
                Route::get('technical-standard/code/{code}', 'Api\Master\TechnicalStandardController@index');
                Route::get('technical-standard/check', 'Api\Master\TechnicalStandardController@check');
                Route::get('technical-standard/detail/code/{code}', 'Api\Master\TechnicalStandardController@detail');
                Route::get('technical-standard/{id}', 'Api\Master\TechnicalStandardController@show');
                Route::post('technical-standard', 'Api\Master\TechnicalStandardController@create');
                Route::put('technical-standard', 'Api\Master\TechnicalStandardController@update');

                //Unit
                Route::get('unit/section/{id}', 'Api\Master\UnitController@showBySection');

                //uom
                Route::get('uom', 'Api\Master\UOMController@index');
                Route::get('uom/check', 'Api\Master\UOMController@check');
                Route::get('uom/{id}', 'Api\Master\UOMController@show');
                Route::post('uom', 'Api\Master\UOMController@create');
                Route::put('uom', 'Api\Master\UOMController@update');

                //user
                Route::get('user', 'Api\Master\UserController@index');
                Route::get('user/check', 'Api\Master\UserController@check');
                Route::get('user/{id}', 'Api\Master\UserController@show');
                Route::post('user', 'Api\Master\UserController@create');
                Route::put('user', 'Api\Master\UserController@update');
                Route::put('user/reset-password', 'Api\Master\UserController@resetPassword');
                Route::put('user/change-password', 'Api\Master\UserController@changePassword');

                //Vehicle
                Route::get('vehicle', 'Api\Master\VehicleController@index');
                Route::get('vehicle/check', 'Api\Master\VehicleController@check');
                Route::get('vehicle/{id}', 'Api\Master\VehicleController@show');
                Route::post('vehicle', 'Api\Master\VehicleController@create');
                Route::put('vehicle', 'Api\Master\VehicleController@update');

                //warehouse
                Route::get('warehouse', 'Api\Master\WarehouseController@index');
                Route::get('warehouse/group/{id}', 'Api\Master\WarehouseController@showByWarehouseGroup');
                Route::get('warehouse/check', 'Api\Master\WarehouseController@check');
                Route::get('warehouse/{id}', 'Api\Master\WarehouseController@show');
                Route::post('warehouse', 'Api\Master\WarehouseController@create');
                Route::put('warehouse', 'Api\Master\WarehouseController@update');
                Route::delete('warehouse', 'Api\Master\WarehouseController@destroy');

                //warehouse group
                Route::get('warehouse-group', 'Api\Master\WarehouseGroupController@index');
                Route::get('warehouse-group/check', 'Api\Master\WarehouseGroupController@check');
                Route::get('warehouse-group/{id}', 'Api\Master\WarehouseGroupController@show');
                Route::post('warehouse-group', 'Api\Master\WarehouseGroupController@create');
                Route::put('warehouse-group', 'Api\Master\WarehouseGroupController@update');
                Route::delete('warehouse-group', 'Api\Master\WarehouseGroupController@destroy');


                //Warehouse Rack
                Route::get('warehouse-rack', 'Api\Master\WarehouseRackController@index');
                Route::get('warehouse-rack/{zone}/{lane}', 'Api\Master\WarehouseRackController@getByZoneAndLane');
                //workcenter
                Route::get('workcenter', 'Api\Master\WorkcenterController@index');
                Route::get('workcenter/check', 'Api\Master\WorkcenterController@check');
                Route::get('workcenter/code/{id}', 'Api\Master\WorkcenterController@showByCode');
                Route::get('workcenter/{id}', 'Api\Master\WorkcenterController@show');
                Route::post('workcenter', 'Api\Master\WorkcenterController@create');
                Route::put('workcenter', 'Api\Master\WorkcenterController@update');
                Route::delete('workcenter', 'Api\Master\WorkcenterController@destroy');

                //Work Order Status
                Route::get('work-order-status', 'Api\Master\WorkOrderStatusController@index');

                Route::get('work-order-type', 'Api\Master\WorkOrderTypeController@index');
            }
        );
        Route::group(
            ['prefix' => 'production', 'middleware' => 'jwt'],
            function () {
                Route::prefix('plastic')->group(function () {
                    Route::get('packaging-usage/current/{number}', 'Api\Production\Plastic\PackagingUsageController@showCurrentProductStatusByNumber');
                    Route::get('packaging-usage/history/{number}', 'Api\Production\Plastic\PackagingUsageController@showHistoryProductStatusByNumber');
                    Route::post('packaging-usage', 'Api\Production\Plastic\PackagingUsageController@create');

                    Route::get('packaging-return', 'Api\Production\Plastic\PackagingReturnController@index');
                    Route::get('packaging-return/detail/{id}/inspected-date/{date}', 'Api\Production\Plastic\PackagingReturnController@showDetail');
                    Route::get('packaging-return/cummulative/{id}', 'Api\Production\Plastic\PackagingReturnController@getCummulativeByID');
                    Route::post('packaging-return', 'Api\Production\Plastic\PackagingReturnController@create');
                    Route::post('packaging-return/header', 'Api\Production\Plastic\PackagingReturnController@createHeader');
                    Route::post('packaging-return/detail', 'Api\Production\Plastic\PackagingReturnController@createDetail');
                    Route::put('packaging-return/closed', 'Api\Production\Plastic\PackagingReturnController@createReturnClosed');
                });

                Route::prefix('melamine')->group(function () {
                    Route::get('bstb', 'Api\Production\Melamine\BSTBController@showBSTBByDateShiftMachine');
                    Route::get('bstb/number/{number}', 'Api\Production\Melamine\BSTBController@showByBSTBNumber');
                    Route::get('bstb/machine', 'Api\Production\Melamine\BSTBController@showMachineByDateShiftSetter');
                    Route::get('bstb/machine/date/{date}/shift/{shift}', 'Api\Production\Melamine\BSTBController@showMachineByDateShift');
                    Route::post('bstb', 'Api\Production\Melamine\BSTBController@create');
                    Route::put('bstb/material', 'Api\Production\Melamine\BSTBController@updateMaterial');

                    Route::get('bstb-setting-result/check', 'Api\Production\Melamine\BSTBSettingResultController@check');
                    Route::get('bstb-setting-result/number/{number}', 'Api\Production\Melamine\BSTBSettingResultController@showByBSTBNumber');
                    Route::post('bstb-setting-result', 'Api\Production\Melamine\BSTBSettingResultController@create');
                    Route::put('bstb-setting-result', 'Api\Production\Melamine\BSTBSettingResultController@update');
                    Route::put('bstb-setting-result/production-result', 'Api\Production\Melamine\BSTBSettingResultController@updateResultProduction');
                    Route::delete('bstb-setting-result', 'Api\Production\Melamine\BSTBSettingResultController@destroy');

                    Route::get('bstb-inspection/number/{number}', 'Api\Production\Melamine\BSTBInspectionController@showByBSTBNumber');
                    Route::get('bstb-inspection/lot-cumulative/{id}', 'Api\Production\Melamine\BSTBInspectionController@showLotCummulativeByBSTBID');
                    Route::get('bstb-inspection/{id}', 'Api\Production\Melamine\BSTBInspectionController@show');
                    Route::post('bstb-inspection', 'Api\Production\Melamine\BSTBInspectionController@create');
                    Route::put('bstb-inspection', 'Api\Production\Melamine\BSTBInspectionController@update');
                    Route::put('bstb-inspection/close-qc-check', 'Api\Production\Melamine\BSTBInspectionController@closedQCChecked');

                    Route::get('bstb-process/number/{number}', 'Api\Production\Melamine\BSTBProcessController@getByNumberAndProcessId');
                });
            }
        );
        Route::group(
            ['prefix' => 'sales-marketing', 'middleware' => 'jwt'],
            function () {
                Route::get('booking-stock/salesman/{id}', 'Api\Sales\BookingStockController@getBySalesmanCustomerAndStatus');
                Route::post('booking-stock', 'Api\Sales\BookingStockController@create');

                Route::get('service-level/{id}', 'Api\Sales\ServiceLevelController@show');

                Route::get('sales-order', 'Api\Sales\SalesOrderController@index');
                Route::get('sales-order/close/{id}', 'Api\Sales\SalesOrderController@close');
                Route::get('sales-order/outstanding/{id}', 'Api\Sales\SalesOrderController@outstanding');
                Route::get('sales-order/process/{id}', 'Api\Sales\SalesOrderController@process');

                Route::get('sales-order/detail', 'Api\Sales\SalesOrderController@detail');
                Route::get('sales-order/detail/full', 'Api\Sales\SalesOrderController@joined');
                Route::get('sales-order/detail/{id}', 'Api\Sales\SalesOrderController@detailShow');
                Route::get('sales-order/{id}', 'Api\Sales\SalesOrderController@show');
                Route::post('sales-order/release', 'Api\Sales\SalesOrderController@process');

                Route::get('so-tracker', 'Api\Sales\SalesOrderTrackerController@index');
            }
        );
        Route::group(
            ['prefix' => 'procurement', 'middleware' => 'jwt'],
            function () {
                Route::get('purchase-order', 'Api\Procurement\PurchaseOrderController@index');
                Route::get('purchase-order/outstanding', 'Api\Procurement\PurchaseOrderController@outstanding');
                Route::get('purchase-order/outstanding-detail', 'Api\Procurement\PurchaseOrderController@outstandingDetail');
                Route::get('purchase-order/full', 'Api\Procurement\PurchaseOrderController@joined');

                Route::get('purchase-order/{id}', 'Api\Procurement\PurchaseOrderController@show');
                Route::post('purchase-order', 'Api\Procurement\PurchaseOrderController@create');

                Route::get('purchase-request', 'Api\Procurement\PurchaseRequestController@index');
                Route::get('purchase-request/full', 'Api\Procurement\PurchaseRequestController@joined');
                Route::get('purchase-request/{id}', 'Api\Procurement\PurchaseRequestController@show');
            }
        );

        Route::group(
            ['prefix' => 'ppic', 'middleware' => 'jwt'],
            function () {
                Route::get('work-order', 'Api\PPIC\WorkOrderController@index');
                Route::get('work-order/import/bom-product', 'Api\PPIC\WorkOrderController@showImportCheckBOMAndProduct');
                Route::get('work-order/import/bom-mixing', 'Api\PPIC\WorkOrderController@showImportCheckBOMMixing');
                Route::get('work-order/import/check-product-workcenter', 'Api\PPIC\WorkOrderController@showImportCheckProductAndWorkcenter');
                Route::get('work-order/workcenter/{id}', 'Api\PPIC\WorkOrderController@showByWorkcenter');
                Route::get('work-order/number/{number}/ng/{isNg}', 'Api\PPIC\WorkOrderController@getByWONumber');
                Route::get('work-order/check', 'Api\PPIC\WorkOrderController@check');
                Route::get('work-order/{id}', 'Api\PPIC\WorkOrderController@show');
                Route::post('work-order', 'Api\PPIC\WorkOrderController@create');
                Route::put('work-order', 'Api\PPIC\WorkOrderController@update');
                Route::delete('work-order', 'Api\PPIC\WorkOrderController@destroy');

                Route::get('production-result', 'Api\PPIC\ProductionResultController@index');
                Route::get('production-result/approval', 'Api\PPIC\ProductionResultController@showProductionClone');
                Route::get('production-result/check', 'Api\PPIC\ProductionResultController@check');
                Route::get('production-result/{id}', 'Api\PPIC\ProductionResultController@show');
                Route::post('production-result', 'Api\PPIC\ProductionResultController@create');
                Route::put('production-result/detail', 'Api\PPIC\ProductionResultController@update');
                Route::put('production-result/approval/detail', 'Api\PPIC\ProductionResultController@approve');
                Route::delete('production-result', 'Api\PPIC\ProductionResultController@destroy');

                Route::get('material-usage', 'Api\PPIC\MaterialUsageController@index');
                Route::get('material-usage/{id}', 'Api\PPIC\MaterialUsageController@show');
                Route::get('material-usage/work-order/{id}', 'Api\PPIC\MaterialUsageController@showByWorkOrderID');
                Route::post('material-usage', 'Api\PPIC\MaterialUsageController@create');
                Route::put('material-usage/detail', 'Api\PPIC\MaterialUsageController@update');
                Route::delete('material-usage', 'Api\PPIC\MaterialUsageController@destroy');
            }
        );

        Route::group(['prefix' => 'oem', 'middleware' => 'jwt'], function () {
            Route::get('delivery-order', 'Api\OEM\DeliveryOrderController@index');
            Route::get('delivery-order/check', 'Api\OEM\DeliveryOrderController@check');
            Route::get('delivery-order/detail', 'Api\OEM\DeliveryOrderController@joined');
            Route::get('delivery-order/purchase-order/history', 'Api\OEM\DeliveryOrderController@getHistoryDOByPOAndDate');
            Route::get('delivery-order/purchase-order/validate/{headerID}/{detailID}', 'Api\OEM\DeliveryOrderController@getDOForValidateProduct');
            Route::get('delivery-order/purchase-order/history/{id}', 'Api\OEM\DeliveryOrderController@getHistoryDOByPO');
            Route::get('delivery-order/purchase-order/{id}', 'Api\OEM\DeliveryOrderController@getDOByPO');
            Route::get('delivery-order/purchase-order/{id}/status/{status}', 'Api\OEM\DeliveryOrderController@getDOForPO');
            Route::get('delivery-order/purchase-order-oustanding/schedule', 'Api\OEM\DeliveryOrderController@getOutstandingPOofDeliveryScheduleByDate');
            Route::get('delivery-order/{id}', 'Api\OEM\DeliveryOrderController@show');
            Route::post('delivery-order', 'Api\OEM\DeliveryOrderController@create');
            Route::post('delivery-order/detail', 'Api\OEM\DeliveryOrderController@update');
            Route::put('delivery-order/cancel', 'Api\OEM\DeliveryOrderController@cancel');
            Route::put('delivery-order/print-counter', 'Api\OEM\DeliveryOrderController@printCounter');
            Route::delete('delivery-order', 'Api\OEM\DeliveryOrderController@destroy');

            Route::get('delivery-schedule', 'Api\OEM\DeliveryScheduleController@index');
            Route::get('delivery-schedule/show', 'Api\OEM\DeliveryScheduleController@show');
            Route::get('delivery-schedule/check', 'Api\OEM\DeliveryScheduleController@check');
            Route::post('delivery-schedule', 'Api\OEM\DeliveryScheduleController@create');
            Route::put('delivery-schedule', 'Api\OEM\DeliveryScheduleController@update');
            Route::delete('delivery-schedule', 'Api\OEM\DeliveryScheduleController@destroy');

            Route::get('material-balance/stock-card', 'Api\OEM\MaterialBalanceController@stockCard');
            Route::get('material-balance/stock-mutation', 'Api\OEM\MaterialBalanceController@stockMutation');

            Route::get('material-customer', 'Api\OEM\MaterialCustomerController@index');
            Route::get('material-customer/full', 'Api\OEM\MaterialCustomerController@joined');
            Route::get('material-customer/material/{id}', 'Api\OEM\MaterialCustomerController@showMaterial');
            Route::get('material-customer/customer/{id}', 'Api\OEM\MaterialCustomerController@showCustomer');
            Route::get('material-customer/product-customer/{id}', 'Api\OEM\MaterialCustomerController@showProductCustomer');
            Route::get('material-customer/{id}', 'Api\OEM\MaterialCustomerController@show');
            Route::post('material-customer', 'Api\OEM\MaterialCustomerController@create');
            Route::post('material-customer/detail', 'Api\OEM\MaterialCustomerController@update');

            Route::get('material-incoming', 'Api\OEM\MaterialIncomingController@index');
            Route::get('material-incoming/unallocated', 'Api\OEM\MaterialIncomingController@getMaterialIncommingUnallocated');
            Route::get('material-incoming/detail', 'Api\OEM\MaterialIncomingController@joined');
            Route::get('material-incoming/{id}', 'Api\OEM\MaterialIncomingController@show');
            Route::post('material-incoming', 'Api\OEM\MaterialIncomingController@create');
            Route::post('material-incoming/detail', 'Api\OEM\MaterialIncomingController@update');
            Route::delete('material-incoming', 'Api\OEM\MaterialIncomingController@destroy');

            Route::get('purchase-order', 'Api\OEM\PurchaseOrderController@index');
            Route::get('purchase-order/check', 'Api\OEM\PurchaseOrderController@check');
            Route::get('purchase-order/full', 'Api\OEM\PurchaseOrderController@joined');
            Route::get('purchase-order/outstanding/lookup', 'Api\OEM\PurchaseOrderController@outstandingLookup');
            Route::get('purchase-order/outstanding/schedule', 'Api\OEM\PurchaseOrderController@outstandingSchedule');
            Route::get('purchase-order/outstanding/validating', 'Api\OEM\PurchaseOrderController@outstandingValidating');
            Route::get('purchase-order/remaining/{id}', 'Api\OEM\PurchaseOrderController@remaining');
            Route::get('purchase-order/{id}', 'Api\OEM\PurchaseOrderController@show');
            Route::post('purchase-order', 'Api\OEM\PurchaseOrderController@create');
            Route::post('purchase-order/detail', 'Api\OEM\PurchaseOrderController@update');
            Route::delete('purchase-order', 'Api\OEM\PurchaseOrderController@destroyPurchaseOrder');
            Route::delete('purchase-order/{id}', 'Api\OEM\PurchaseOrderController@destroy');
        });

        Route::group(['prefix' => 'utility', 'middleware' => 'jwt'], function () {
            Route::get('approval/user/{id}', 'Api\Utility\ApprovalController@showByUserID');
            Route::get('approval/{id}', 'Api\Utility\ApprovalController@show');
            Route::get('approval/{id}/user/{user}', 'Api\Utility\ApprovalController@checkApprovalPrivilege');
            Route::put('approval', 'Api\Utility\ApprovalController@update');
            Route::post('approval', 'Api\Utility\ApprovalController@create');

            Route::post('log-scan', 'Api\Utility\LogScanController@create');

            Route::get('transaction-number/{type}', 'Api\UtilityController@getTransactionNumber');

            Route::post("product-temporary", 'Api\UtilityController@createTemporaryProduct');
        });

        Route::group(
            ['prefix' => 'stok', 'middleware' => 'jwt'],
            function () {
                Route::get('customer/sales/{nik}/name/{name?}', 'Api\Master\CustomerController@getCustomerInStock');
                Route::get('purchase-order/sales/{nik}/status/{status?}', 'Api\Stok\PurchaseOrderController@getBySalesmanAndStatus');
                Route::get('purchase-order/number/{number}', 'Api\Stok\PurchaseOrderController@getLastNumber');
                Route::get('purchase-order/cummulative/sales/{sales}/date/{date}', 'Api\Stok\PurchaseOrderController@getCummulativeByDateAndSalesman');
                Route::get('purchase-order/{id}', 'Api\Stok\PurchaseOrderController@show');
                Route::post('purchase-order', 'Api\Stok\PurchaseOrderController@createHeader');
                Route::post('purchase-order/detail', 'Api\Stok\PurchaseOrderController@createDetail');
            }
        );
    }
);
Route::fallback(function () {
    return response()->json(["status" => "error", "message" => "url not found"], 404);
});
