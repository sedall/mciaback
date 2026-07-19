<?php

namespace Modules\Customers\Services;

use App\Models\User;
use Modules\CustomerDocuments\Models\CustomerDocument;
use Modules\Customers\Http\Resources\CustomerProfileResource;
use Modules\Customers\Models\CustomerProfile;

class KycStatusService
{
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
        if ($user->relationLoaded('customerProfile')) {
            return $user->customerProfile;
        }

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

    protected function getKycStatus(User $user): string
    {
        $profileCompleted = $this->isProfileCompleted($this->getProfile($user));
        $documents = $this->getDocumentsCollection($user);

        if (! $profileCompleted) {
            return 'profile_incomplete';
        }

        if ($documents->isEmpty()) {
            return 'documents_missing';
        }

        $statuses = $documents->pluck('status')->filter();

        if ($statuses->contains('rejected')) {
            return 'rejected';
        }

        if ($statuses->isNotEmpty() && $statuses->every(fn ($status) => $status === 'approved')) {
            return 'approved';
        }

        if ($statuses->contains('pending')) {
            return 'pending_review';
        }

        return 'under_review';
    }

    protected function getDocumentsSummary(User $user): array
    {
        $documents = $this->getDocumentsCollection($user);

        return [
            'total' => $documents->count(),
            'approved' => $documents->where('status', 'approved')->count(),
            'pending' => $documents->where('status', 'pending')->count(),
            'rejected' => $documents->where('status', 'rejected')->count(),
            'items' => $documents->map(function (CustomerDocument $document) {
                return [
                    'id' => $document->id,
                    'type' => $document->type,
                    'status' => $document->status,
                    'rejection_reason' => $document->rejection_reason,
                    'file_url' => $document->file_path ? asset('storage/' . $document->file_path) : null,
                    'submitted_at' => optional($document->created_at)?->toISOString(),
                    'reviewed_at' => optional($document->reviewed_at)?->toISOString(),
                ];
            })->values()->all(),
        ];
    }

    protected function getDocumentsCollection(User $user)
    {
        if (method_exists($user, 'customerDocuments')) {
            if ($user->relationLoaded('customerDocuments')) {
                return $user->customerDocuments;
            }

            return $user->customerDocuments()->get();
        }

        return CustomerDocument::query()
            ->where('user_id', $user->id)
            ->get();
    }
}
