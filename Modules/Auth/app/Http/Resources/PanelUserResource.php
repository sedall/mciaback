<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PanelUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mobile' => $this->mobile,

            // نمایش نقش‌ها برای پنل جاری (طبق منطق RoleAssignmentService)
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),

            // فیلتر کردن نقش اصلی بر اساس پنل جاری
            'primary_role' => $this->roles
                ->where('name', 'like', "{$this->panel}-%")
                ->first()?->name,

            'panel' => $this->panel,
        ];
    }
}
