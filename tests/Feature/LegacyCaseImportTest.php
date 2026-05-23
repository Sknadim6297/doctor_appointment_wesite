<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\LegalCase;
use App\Models\LegalCaseProceeding;
use App\Models\User;
use App\Services\LegacyCaseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyCaseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_case_category_details_and_links_doctor(): void
    {
        $doctor = Enrollment::query()->create([
            'doctor_name' => 'Dr. Imtiaz Ahmed',
            'legacy_user_id' => 10113,
            'customer_id_no' => 'DR.IMTIAZ10113',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        User::query()->create([
            'name' => 'Legacy Staff',
            'email' => 'legacy-staff@mediforum.test',
            'password' => Hash::make('password'),
            'legacy_user_id' => 10113,
        ]);

        $base = database_path('migrations/2026_05_22_100000_create_legacy_case_import_tables.php');
        $this->assertFileExists($base);

        $import = app(LegacyCaseImportService::class);

        $dir = storage_path('app/testing-case-import');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir . '/category.sql', <<<'SQL'
INSERT INTO `tbl_case_category` (`case_cat_id`, `case_cat_name`) VALUES (1, 'Medical');
SQL);

        file_put_contents($dir . '/link.sql', <<<'SQL'
INSERT INTO `tbl_case_link` (`id`, `case_link_name`, `case_link`) VALUES
(1, 'cms.nic.in', 'http://cms.nic.in/example');
SQL);

        file_put_contents($dir . '/details.sql', <<<'SQL'
INSERT INTO `tbl_case_details` (`case_details_id`, `case_id`, `case_details`, `date`, `created_by`, `edited_by`) VALUES
(9001, 47, 'CC/214/2018 Dr. Imtiaz Ahmed ongoing hearing.', '2019-04-03', 10113, 0);
SQL);

        file_put_contents($dir . '/document.sql', <<<'SQL'
INSERT INTO `tbl_case_document` (`id`, `case_id`, `document_title`, `document_file`) VALUES
(9001, 47, 'DR.IMTIAZ AHMED VAKALATNAMA CASE NO-CC-214-2018', '1554295260-47.pdf');
SQL);

        file_put_contents($dir . '/payment.sql', <<<'SQL'
INSERT INTO `tbl_case_payment` (`case_payment_id`, `case_id`, `cheque_no`, `bank_name`, `amount`, `payment_date`, `acknowledge_reciept`) VALUES
(9001, 47, '348039', 'VIJAYA BANK', '4000', '2019-05-08', 'receipt.pdf');
SQL);

        $import->loadSqlFile('category', $dir . '/category.sql');
        $import->loadSqlFile('link', $dir . '/link.sql');
        $import->loadSqlFile('details', $dir . '/details.sql');
        $import->loadSqlFile('document', $dir . '/document.sql');
        $import->loadSqlFile('payment', $dir . '/payment.sql');

        $stats = $import->syncFromStaging(false);

        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, count($stats['errors']));

        $case = LegalCase::query()->where('legacy_case_id', 47)->first();
        $this->assertNotNull($case);
        $this->assertSame((int) $doctor->id, (int) $case->enrollment_id);
        $this->assertStringContainsString('CC/214/2018', (string) $case->case_number);
        $this->assertSame(1, LegalCaseProceeding::query()->where('legacy_case_id', 47)->count());
        $this->assertTrue(Schema::hasTable('legal_case_documents'));
        $this->assertTrue(DB::table('legal_court_links')->where('id', 1)->exists());
    }

    public function test_imports_doctor_case_linked_by_legacy_user_id(): void
    {
        $doctor = Enrollment::query()->create([
            'doctor_name' => 'DR. IMTIAZ AHMED',
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.IMTIAZ9663',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        DB::table('legal_case_categories')->insert([
            'id' => 1,
            'name' => 'Medical',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $import = app(LegacyCaseImportService::class);
        $import->loadSqlFile('doctor_case', base_path('tests/Fixtures/legacy_doctor_case_sample.sql'));

        $stats = $import->syncFromStaging(false);

        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, count($stats['errors']));

        $case = LegalCase::query()->where('legacy_case_id', 38)->first();
        $this->assertNotNull($case);
        $this->assertSame((int) $doctor->id, (int) $case->enrollment_id);
        $this->assertSame('CC/56/2016 NADIA', $case->case_number);
        $this->assertSame('District', $case->court);
        $this->assertSame('9434163237', $case->doctor_phone);
        $this->assertSame('drimtiazahmed100@gmail.com', $case->doctor_mail);
        $this->assertSame(1, LegalCaseProceeding::query()->where('legacy_case_id', 38)->count());
    }
}
