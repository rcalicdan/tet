<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function searchUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query()->where('is_active', true);

        $this->applyFilters($query, $filters);
        $this->applyFullTextSearch($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    protected function applyFullTextSearch(Builder $query, array $filters): void
    {
        if (empty($filters['query'])) {
            return;
        }

        $searchTerm = $filters['query'];
        $searchMode = $filters['search_mode'] ?? 'websearch';

        $tsQuery = match($searchMode) {
            'phrase' => "phraseto_tsquery('english', ?)",
            'plainto' => "plainto_tsquery('english', ?)",
            default => "websearch_to_tsquery('english', ?)",
        };

        $query->selectRaw("
            users.*,
            ts_rank(searchable, {$tsQuery}) as search_rank
        ", [$searchTerm])
        ->whereRaw("searchable @@ {$tsQuery}", [$searchTerm])
        ->orderByDesc('search_rank');
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        if (!empty($filters['city'])) {
            $query->where(function($q) use ($filters) {
                $q->where('city', 'ILIKE', '%' . $filters['city'] . '%')
                  ->orWhereRaw('city % ?', [$filters['city']]);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['has_listings']) && $filters['has_listings']) {
            $query->whereHas('listings');
        }
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'relevance';

        match($sortBy) {
            'name' => $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            'city' => $query->orderBy('city', 'asc'),
            'relevance' => null,
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    public function getContractors(array $filters = []): LengthAwarePaginator
    {
        $filters['user_type'] = 'contractor';
        return $this->searchUsers($filters);
    }

    public function getClients(array $filters = []): LengthAwarePaginator
    {
        $filters['user_type'] = 'client';
        return $this->searchUsers($filters);
    }

    public function getUserById(string $userId): User
    {
        return User::with(['listings.photos'])->findOrFail($userId);
    }

    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function updateProfilePhoto(User $user, $photo): User
    {
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $photo->store('profiles', 'public');
        $user->update(['profile_photo' => $path]);

        return $user->fresh();
    }

    public function deleteProfilePhoto(User $user): User
    {
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        return $user->fresh();
    }

    public function deactivateUser(User $user): User
    {
        $user->update(['is_active' => false]);
        return $user->fresh();
    }

    public function activateUser(User $user): User
    {
        $user->update(['is_active' => true]);
        return $user->fresh();
    }

    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            foreach ($user->listings as $listing) {
                foreach ($listing->photos as $photo) {
                    Storage::disk('public')->delete($photo->photo_url);
                }
                $listing->photos()->delete();
                $listing->delete();
            }

            $user->pushTokens()->delete();
            $user->notifications()->delete();
            
            $user->delete();
        });
    }

    public function getUserStats(User $user): array
    {
        if ($user->isContractor()) {
            return [
                'total_listings' => $user->listings()->count(),
                'active_listings' => $user->listings()->where('status', 'active')->count(),
                'total_conversations' => $user->contractorConversations()->count(),
                'total_inquiries' => DB::table('inquiries')
                    ->where('contractor_id', $user->id)
                    ->count(),
            ];
        }

        return [
            'total_conversations' => $user->clientConversations()->count(),
            'total_inquiries' => DB::table('inquiries')
                ->where('client_id', $user->id)
                ->count(),
        ];
    }

    public function autocompleteUsers(string $query, ?string $userType = null, int $limit = 10): array
    {
        $queryBuilder = User::query()
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                $q->whereRaw("(first_name || ' ' || last_name) % ?", [$query])
                  ->orWhereRaw("(first_name || ' ' || last_name) ILIKE ?", [$query . '%']);
            });

        if ($userType) {
            $queryBuilder->where('user_type', $userType);
        }

        return $queryBuilder
            ->selectRaw("
                id,
                first_name,
                last_name,
                (first_name || ' ' || last_name) as full_name,
                profile_photo,
                city,
                similarity((first_name || ' ' || last_name), ?) as similarity_score
            ", [$query])
            ->orderByDesc('similarity_score')
            ->limit($limit)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'full_name' => trim($user->full_name),
                'profile_photo' => $user->profile_photo 
                    ? url('storage/' . $user->profile_photo) 
                    : null,
                'city' => $user->city,
            ])
            ->toArray();
    }

    public function getPopularCities(int $limit = 10): array
    {
        return User::query()
            ->whereNotNull('city')
            ->where('is_active', true)
            ->selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'city')
            ->toArray();
    }
}