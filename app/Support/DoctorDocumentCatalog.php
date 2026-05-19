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
    public const SOURCE_DOCTOR_PORTAL = 'doctor_portal';
    public const SOURCE_USER_PORTAL = 'user_portal';

    /** @return list<string> */
    public static function externalVerificationSources(): array
    {
        return [
            self::SOURCE_DOCTOR_PORTAL,
            self::SOURCE_USER_PORTAL,
        ];
    }

    /** @return list<string> */
    public static function adminManagedSources(): array
    {
        return [
            self::SOURCE_MANUAL,
            self::SOURCE_ENROLLMENT_STEP1,
            self::SOURCE_ENROLLMENT_STEP3,
            self::SOURCE_ENROLLMENT_STEP4,
        ];
    }

    /** @return array<string, array{category: string, title: string, type: string}> */
    /** @return list<string> */
    public static function requiredEnrollmentDocumentTypes(): array
    {
        return ['aadhaar', 'pan', 'medical_registration'];
    }

    /**
     * Upload slots shown on enrollment create form (grouped for details view).
     *
     * @return list<array{section: string, field: string, title: string, category: string, type: string, required: bool, multiple: bool}>
     */
    public static function enrollmentUploadSlots(): array
    {
        $requiredTypes = self::requiredEnrollmentDocumentTypes();
        $sections = [
            'Form Upload (Optional)' => ['doc_insurance_form', 'doc_enrollment_form', 'doc_other_forms'],
            'Identity & Professional Documents (Required)' => ['doc_aadhaar_card', 'doc_pan_card', 'doc_medical_registration'],
            'Supporting Documents (Optional)' => ['doc_other_documents'],
            'Payment Documents (Optional)' => ['doc_payment_document'],
        ];

        $map = self::enrollmentFieldMap();
        $slots = [];

        foreach ($sections as $section => $fields) {
            foreach ($fields as $field) {
                $meta = $map[$field] ?? null;
                if (!$meta) {
                    continue;
                }

                $slots[] = [
                    'section' => $section,
                    'field' => $field,
                    'title' => $meta['title'],
                    'category' => $meta['category'],
                    'type' => $meta['type'],
                    'required' => in_array($meta['type'], $requiredTypes, true),
                    'multiple' => in_array($field, ['doc_other_forms', 'doc_other_documents'], true),
                ];
            }
        }

        return $slots;
    }

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
                'title' => 'Supporting Document',
            ],
            'doc_payment_document' => [
                'category' => self::CATEGORY_PAYMENT,
                'type' => 'payment_proof',
                'title' => 'Payment Document',
            ],
            'doc_other_documents' => [
                'category' => self::CATEGORY_ADDITIONAL,
                'type' => 'additional',
                'title' => 'Supporting Document',
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
            '2' => 'Policy Certificate',
            '4' => 'Payment Proof',
            '6' => 'Membership Certificate',
            '7' => 'Consignment Note',
            '8' => 'Additional Attachment',
            default => 'Document',
        };
    }

    public static function isExternalSource(?string $source): bool
    {
        return in_array($source, self::externalVerificationSources(), true);
    }

    public static function indexedTitle(string $baseTitle, int $index): string
    {
        return $index > 1 ? $baseTitle . ' ' . $index : $baseTitle;
    }

    public static function humanizeTitle(string $title, string $documentType = '', ?string $sourceKey = null): string
    {
        if (preg_match('/^Enrollment Form \(Other\) #(\d+)$/i', $title, $matches)) {
            return self::indexedTitle('Supporting Document', (int) $matches[1]);
        }

        if (preg_match('/^Additional Document #(\d+)$/i', $title, $matches)) {
            return self::indexedTitle('Additional Attachment', (int) $matches[1]);
        }

        if (preg_match('/^(Supporting Document|Additional Attachment) #(\d+)$/i', $title, $matches)) {
            return self::indexedTitle($matches[1], (int) $matches[2]);
        }

        $fromType = match ($documentType) {
            'other_form' => 'Supporting Document',
            'additional' => 'Additional Attachment',
            'policy' => 'Policy Certificate',
            'consignment' => 'Consignment Note',
            'post_document' => 'Processing Note',
            'payment_proof' => 'Payment Proof',
            'insurance_form' => 'Insurance Form',
            'enrollment_form' => 'Enrollment Form',
            'aadhaar' => 'Aadhaar Card',
            'pan' => 'PAN Card',
            'medical_registration' => 'Medical Registration Certificate',
            default => null,
        };

        if ($fromType !== null && ($title === '' || str_contains($title, '#') || str_contains($title, '('))) {
            if (preg_match('/#(\d+)$/', $title, $matches)) {
                return self::indexedTitle($fromType, (int) $matches[1]);
            }

            return $fromType;
        }

        return $title !== '' ? $title : ($fromType ?? 'Document');
    }
}
