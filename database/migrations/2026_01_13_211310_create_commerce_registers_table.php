<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
            if (!Schema::hasTable('commerce_registers')) {

        Schema::create('commerce_registers', function (Blueprint $table) {
            $table->id();

            $table->string('register_serial_number')->nullable();
            $table->string('commerce_number')->nullable();
            $table->string('company_name_ar')->nullable();
            $table->string('company_name_en')->nullable();
            $table->string('main_license_number')->nullable();

            $table->string('commerce_register_type_code')->nullable();
            $table->string('commerce_register_type_desc_ar')->nullable();
            $table->string('commerce_register_type_desc_en')->nullable();

            $table->string('legal_type_code')->nullable();
            $table->string('legal_type_desc_ar')->nullable();
            $table->string('legal_type_desc_en')->nullable();

            $table->string('nationality_code')->nullable();
            $table->string('nationality_desc_ar')->nullable();
            $table->string('nationality_desc_en')->nullable();

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('cancel_date')->nullable();

            $table->decimal('paid_up_capital', 15, 2)->nullable();
            $table->decimal('nominated_capital', 15, 2)->nullable();

            $table->timestamps();
        });
            }
    }

    public function down()
    {
        Schema::dropIfExists('commerce_registers');
    }
};
