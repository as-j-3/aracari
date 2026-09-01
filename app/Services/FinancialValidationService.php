<?php

namespace App\Services;

use App\Services\BookingCalculationService;
use App\Services\CommissionService;
use App\Models\Coupon;
use App\Models\Trip;

/**
 * خدمة التحقق من الدقة المالية والتطابق في جميع أنحاء النظام
 */
class FinancialValidationService
{
    public function __construct(
        private BookingCalculationService $bookingCalculationService,
        private CommissionService $commissionService
    ) {}

    /**
     * اختبار ذاتي للتأكد من تطابق النتائج المالية
     */
    public function validateFinancialConsistency(): array
    {
    if (!app()->environment('local', 'testing')) {
    abort(403);
    }
        $results = [];
        
        // اختبار 1: حجز رحلة بـ 10 ركاب مع ضرائب 5% وعمولة 2$
        $results['test_1'] = $this->testBookingCalculation();
        
        // اختبار 2: التحقق من تطابق العمولات
        $results['test_2'] = $this->testCommissionConsistency();
        
        // اختبار 3: التحقق من الدقة في التقارير
        $results['test_3'] = $this->testReportAccuracy();
        
        return $results;
    }
    
    /**
     * اختبار حسابات الحجز
     */
    private function testBookingCalculation(): array
    {
        // محاكاة رحلة بسعر 100 ريال للبالغ و 50 ريال للطفل
        $mockTrip = new \stdClass();
        $mockTrip->price_for_adult = 100.00;
        $mockTrip->price_for_child = 50.00;
        
        $trips = [$mockTrip];
        $adults = 8;
        $children = 2;
        
        // حساب المبلغ الأساسي: (8 * 100) + (2 * 50) = 900 ريال
        $baseAmount = (8 * 100.00) + (2 * 50.00); // 900.00
        
        // استخدام BookingCalculationService
        $calculation = $this->bookingCalculationService->calculateTotalAmount(
            $trips,
            $adults,
            $children,
            null,
            1
        );
        
        // التحقق من النتائج
        $expectedBase = 900.00;
        $expectedTax = 900.00 * 0.05; // 45.00 (5% ضرائب)
        $expectedTotal = 900.00 + 45.00; // 945.00
        
        $isConsistent = (
            abs($calculation['base_amount'] - $expectedBase) < 0.01 &&
            abs($calculation['tax_total'] - $expectedTax) < 0.01 &&
            abs($calculation['total_amount'] - $expectedTotal) < 0.01
        );
        
        return [
            'test_name' => 'Booking Calculation Test (10 passengers, 5% tax)',
            'expected' => [
                'base_amount' => $expectedBase,
                'tax_total' => $expectedTax,
                'total_amount' => $expectedTotal
            ],
            'actual' => [
                'base_amount' => $calculation['base_amount'],
                'tax_total' => $calculation['tax_total'],
                'total_amount' => $calculation['total_amount']
            ],
            'is_consistent' => $isConsistent,
            'differences' => [
                'base_diff' => abs($calculation['base_amount'] - $expectedBase),
                'tax_diff' => abs($calculation['tax_total'] - $expectedTax),
                'total_diff' => abs($calculation['total_amount'] - $expectedTotal)
            ]
        ];
    }
    
    /**
     * اختبار اتساق العمولات
     */
    private function testCommissionConsistency(): array
    {
        $amount = 1000.00;
        $branchId = 1;
        
        // حساب العمولة باستخدام CommissionService
        $commission = $this->commissionService->calculateBookingCommission($amount, $branchId);
        
        // التحقق من أن العمولة لا تتجاوز المبلغ الأصلي
        $isValid = $commission['amount'] <= $amount;
        
        return [
            'test_name' => 'Commission Consistency Test',
            'amount' => $amount,
            'commission' => $commission,
            'is_valid' => $isValid
        ];
    }
    
    /**
     * اختبار دقة التقارير
     */
    private function testReportAccuracy(): array
    {
        // هذا الاختبار يتطلب بيانات فعلية من قاعدة البيانات
        // سنقوم بفحص تطابق المبالغ في التقارير
        
        return [
            'test_name' => 'Report Accuracy Test',
            'status' => 'pending_database_data',
            'message' => 'يتطلب بيانات فعلية من قاعدة البيانات'
        ];
    }
    
    /**
     * التحقق من تطابق الكسور العشرية
     */
    public function validateDecimalPrecision(): array
    {
        $testAmounts = [100.50, 999.99, 0.01, 1234.56];
        $results = [];
        
        foreach ($testAmounts as $amount) {
            $calculation = $this->bookingCalculationService->calculateBookingCosts(
                $amount,
                null,
                []
            );
            
            $results[] = [
                'input' => $amount,
                'output' => $calculation['total_amount'],
                'precision_ok' => strlen(substr(strrchr((string)$calculation['total_amount'], "."), 1)) <= 2
            ];
        }
        
        return $results;
    }
}
