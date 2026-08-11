<?php

namespace Modules\Customers\Services;

use App\Models\User;
use Modules\CustomerDocuments\Models\CustomerDocument;
use Modules\Customers\Models\CustomerProfile;
use Modules\Customers\Http\Resources\CustomerProfileResource;

class KycStatusService
{
    protected const REQUIRED_DOCUMENTS = [
        'national_card_front',
        'national_card_back',
        'certificate_of_residence',
        'employment_certificate'
    ];

    public function getAccountSnapshot(User $user): array
    {
        $profile = $this->getProfile($user);

        return [
            'user' => [
                'id' => $user->id,
                'mobile' => $user->mobile,
                'roles' => $user->getRoleNames()->values()->all(),
                'primary_role' => $user->getRoleNames()->first(),
                'panel' => null,
            ],
            'profile' => $profile
                ? CustomerProfileResource::make($profile)->resolve()
                : null,
            'profile_completed' => $this->isProfileCompleted($profile),
            'kyc_status' => $this->getKycStatus($user),
            'documents_summary' => $this->getDocumentsSummary($user),
        ];
    }

    public function getCustomerSnapshot(User $user): array
    {
        return $this->getAccountSnapshot($user);
    }

    protected function getProfile(User $user): ?CustomerProfile
    {
        return $user->customerProfile()->first();
    }

    protected function isProfileCompleted(?CustomerProfile $profile): bool
    {
        if (! $profile) {
            return false;
        }

        $requiredFields = [
            'first_name',
            'last_name',
            'father_name',
            'national_code',
            'birth_date',
            'gender',
            'province',
            'city',
            'address',
            'postal_code',
        ];

        foreach ($requiredFields as $field) {
            if (blank($profile->{$field})) {
                return false;
            }
        }

        return true;
    }


    public function getKycStatus(User $user): string
    {
        $profile = $this->getProfile($user);
        if (!$this->isProfileCompleted($profile)) {
            return 'profile_incomplete';
        }

        $documents = $this->getDocumentsCollection($user);

        // ۱. بررسی رد شدن حتی یک سند
        if ($documents->contains('status', 'rejected')) {
            return 'rejected';
        }

        // ۲. بررسی وجود و تأیید ۴ سند اجباری
        $approvedTypes = $documents->where('status', 'approved')->pluck('type')->toArray();

        foreach (self::REQUIRED_DOCUMENTS as $requiredType) {
            if (!in_array($requiredType, $approvedTypes)) {
                // اگر مدرک موجود باشد ولی در انتظار تایید باشد، وضعیت 'pending_review' است
                if ($documents->where('type', $requiredType)->contains('status', 'pending')) {
                    return 'pending_review';
                }
                return 'documents_missing'; // یکی از مدارک الزامی کلاً وجود ندارد یا تأیید نشده
            }
        }

        return 'approved';
    }


    protected function getDocumentsSummary(User $user): array
    {
        $documents = $this->getDocumentsCollection($user);

        return [
            'total' => $documents->count(),
            'approved' => $documents->where('status', 'approved')->count(),
            'pending' => $documents->where('status', 'pending')->count(),
            'rejected' => $documents->where('status', 'rejected')->count(),
            'items' => $documents->map(fn ($document) => [
                'id' => $document->id,
                'type' => $document->type,
                'status' => $document->status,
                'rejection_reason' => $document->rejection_reason,
                'file_url' => $document->file_url,
            ])->values()->all(),
        ];
    }

    protected function getDocumentsCollection(User $user)
    {
        return CustomerDocument::query()
            ->where('user_id', $user->id)
            ->get();
    }
}
