<?php

namespace App\Console\Commands;

use App\Models\CommerceRegister;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportCommerceCsv extends Command
{
    protected $signature = 'import:commerce-csv {path}';
    protected $description = 'Import commerce data from CSV file';

    public function handle()
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error('File not found!');
            return Command::FAILURE;
        }

        $file = fopen($path, 'r');

        // إزالة BOM إن وجد
        $firstLine = fgets($file);
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

        // إعادة المؤشر للبداية
        rewind($file);

        // قراءة الهيدر مع تحديد الفاصل صراحة
        fgetcsv($file, 0, ',', '"', '\\');

        DB::beginTransaction();

        $inserted = 0;
        $skipped  = 0;
        $line     = 1;

        try {

            while (($row = fgetcsv($file, 0, ',', '"', '\\')) !== false) {
                $line++;

                // تأكيد عدد الأعمدة (عندك 19 عمود)
                if (count($row) < 19) {
                    $skipped++;
                    continue;
                }

                $data = [
                    'register_serial_number' => $this->cleanString($row[0]),
                    'commerce_number' => $this->cleanString($row[1]),
                    'company_name_ar' => $this->cleanString($row[2]),
                    'company_name_en' => $this->cleanString($row[3]),
                    'main_license_number' => $this->cleanString($row[4]),

                    'commerce_register_type_code' => $this->cleanString($row[5]),
                    'commerce_register_type_desc_ar' => $this->cleanString($row[6]) ?? 'غير متوفر',
                    'commerce_register_type_desc_en' => $this->cleanString($row[7]) ?? 'Not Available',

                    'legal_type_code' => $this->cleanString($row[8]),
                    'legal_type_desc_ar' => $this->cleanString($row[9]) ?? 'غير متوفر',
                    'legal_type_desc_en' => $this->cleanString($row[10]) ?? 'Not Available',

                    'nationality_code' => $this->cleanString($row[11]),
                    'nationality_desc_ar' => $this->cleanString($row[12]) ?? 'غير متوفر',
                    'nationality_desc_en' => $this->cleanString($row[13]) ?? 'Not Available',

                    'issue_date' => $this->formatDate($row[14]),
                    'expiry_date' => $this->formatDate($row[15]),
                    'cancel_date' => $this->formatDate($row[16]),

                    'paid_up_capital' => $this->formatDecimal($row[17]),
                    'nominated_capital' => $this->formatDecimal($row[18]),
                ];

                // الحقول الإلزامية
                $requiredFields = [
                    'company_name_ar',
                    'commerce_register_type_code',
                    'legal_type_code',
                    'nationality_code',
                ];

                $invalid = false;
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        $invalid = true;
                        break;
                    }
                }

                if ($invalid) {
                    $skipped++;
                    continue;
                }

                CommerceRegister::create($data);
                $inserted++;
            }

            DB::commit();
            fclose($file);

            $this->info('✅ CSV imported successfully');
            $this->info("✅ Inserted rows: {$inserted}");
            $this->info("⏭️ Skipped rows: {$skipped}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);

            $this->error("❌ Error on line {$line}: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /* ===================== Helpers ===================== */

    private function cleanString($value)
    {
        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'null' || $value === '?') {
            return null;
        }

        return $value;
    }

    private function formatDate($value)
    {
        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'null' || $value === '?') {
            return null;
        }

        return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
    }

    private function formatDecimal($value)
    {
        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'null' || $value === '?') {
            return null;
        }

        return (float) $value;
    }
}
