<?php

use App\Http\Controllers\MyTest\MyTestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('Security/Auth/OfficeLogin', function (Request $request) {
    return '{
    "result": {
        "Authorization": "NQQ7HKNRQD6ILHPQ2D6U",
        "Email": "Test@nirasoft.ir",
        "UserID": "THR000.Nira",
        "CellPhone": "09100000000",
        "OfficeCode": "THR000",
        "OfficeType": "COMPANY",
        "UserFullName": "تست نيرا",
        "OfficeName": "ايستگاه تهران"
    },
    "description_fa": "عملیات احراز هویت با موفقیت انجام شد",
    "description_en": "Success",
    "errorCode": 0
}';
});

Route::get('get_stock/{id}', [MyTestController::class, 'getStock'])->name('getStock');
Route::get('get_stock_data/{id}', [MyTestController::class, 'getStockData'])->name('getStockData');
