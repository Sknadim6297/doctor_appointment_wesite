<?php

namespace App\Support;

final class DoctorDocumentCatalog
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const CATEGORY_KYC = 'kyc';
    public const CATEGORY_REGISTRATION = 'registration';
    public const CATEGORY_ENROLLMENT_FORM = 'enrollment_form';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_ADDITIONAL = 'additional';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_ENROLLMENT_STEP1 = 'enrollment_step1';
    public const SOURCE_ENROLLMENT_STEP3 = 'enrollment_step3';
    public const SOURCE_ENROLLMENT_STEP4 = 'enrollment_step4';

    /** @return array<string, array{category: string, title: string, type: string}> */
    public static function enrollmentFieldMap(): array
    {
        return [
            'doc_aadhaar_card' => [
                'category' => self::CATEGORY_KYC,
                'type' => 'aadhaar',
                'title' => 'Aadhaar Card',
            ],
            'doc_pan_card' => [
                'category' => self::CATEGORY_KYC,
                'type' => 'pan',
                'title' => 'PAN Card',
            ],
            'doc_medical_registration' => [
                'category' => self::CATEGORY_REGISTRATION,
                'type' => 'medical_registration',
                'title' => 'Medical Registration Certificate',
            ],
            'doc_insurance_form' => [
                'category' => self::CATEGORY_ENROLLMENT_FORM,
                'type' => 'insurance_form',
                'title' => 'Insurance Form',
            ],
            'doc_enrollment_form' => [
                'category' => self::CATEGORY_ENROLLMENT_FORM,
                'type' => 'enrollment_form',
                'title' => 'Enrollment Form',
            ],
            'doc_other_forms' => [
                'category' => self::CATEGORY_ENROLLMENT_FORM,
                'type' => 'other_form',
                'title' => 'Enrollment Form (Other)',
            ],
            'doc_payment_document' => [
                'category' => self::CATEGORY_PAYMENT,
                'type' => 'payment_proof',
                'title' => 'Payment Document',
            ],
            'doc_other_documents' => [
                'category' => self::CATEGORY_ADDITIONAL,
                'type' => 'additional',
                'title' => 'Additional Document',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_KYC => 'KYC Documents',
            self::CATEGORY_REGISTRATION => 'Registration Documents',
            self::CATEGORY_ENROLLMENT_FORM => 'Enrollment Forms',
            self::CATEGORY_PAYMENT => 'Payment Documents',
            self::CATEGORY_ADDITIONAL => 'Additional Uploads',
        ];
    }

    /** @return array<string, string> */
    public static function categoryOrder(): array
    {
        return [
            self::CATEGORY_KYC,
            self::CATEGORY_REGISTRATION,
            self::CATEGORY_ENROLLMENT_FORM,
            self::CATEGORY_PAYMENT,
            self::CATEGORY_ADDITIONAL,
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    public static function legacyTypeToCategory(string $type): string
    {
        return match ($type) {
            '2' => self::CATEGORY_ENROLLMENT_FORM,
            '4' => self::CATEGORY_PAYMENT,
            '6', '7' => self::CATEGORY_ENROLLMENT_FORM,
            default => self::CATEGORY_ADDITIONAL,
        };
    }

    public static function legacyTypeLabel(string $type): string
    {
        return match ($type) {
            '2' => 'Policy',
            '4' => 'Cheque',
            '6' => 'Form',
            '7' => 'Consignment Form',
            '8' => 'Other Documents',
            default => 'Document',
        };
    }
}
