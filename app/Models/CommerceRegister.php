<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CommerceRegister extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'register_serial_number',
        'commerce_number',
        'company_name_ar',
        'company_name_en',
        'main_license_number',
        'commerce_register_type_code',
        'commerce_register_type_desc_ar',
        'commerce_register_type_desc_en',
        'legal_type_code',
        'legal_type_desc_ar',
        'legal_type_desc_en',
        'nationality_code',
        'nationality_desc_ar',
        'nationality_desc_en',
        'issue_date',
        'expiry_date',
        'cancel_date',
        'paid_up_capital',
        'nominated_capital',
    ];
}
