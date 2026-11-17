<?php

namespace App\Services\Auth;

use App\Helpers\FileApps;
use App\Models\Organization\Company;
use App\Models\UserManagement\User;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Photos of the imported employees from the access card system (as in
 * employee-central-hub): the card whose StaffNo is the employee ID, the one
 * not deleted and last updated when there are several. Only StaffNo, Photo,
 * Del_State and LastUpdate are read. A photo is stored once as a small
 * square WebP thumbnail on the private disk and taken again only when its
 * card was updated since. Run after the daily directory import.
 */
class EmployeePhotoSync
{
    /**
     * Folder of the thumbnails on the private ("local") disk.
     */
    public const DIRECTORY = 'user-photos';

    /**
     * Whether the card system is configured (CARD_DB_DATABASE).
     */
    public function isConfigured(): bool
    {
        return filled(config('database.connections.'.config('merdeka.employee_photos.connection').'.database'));
    }

    /**
     * Take the photos of the respondents: the employees of the assessed departments and of the respondent companies
     * (config survey.respondent_companies).
     *
     * @return array{updated: int, unchanged: int, missing: int}
     */
    public function sync(): array
    {
        $result = ['updated' => 0, 'unchanged' => 0, 'missing' => 0];
        $respondentCompanies = Company::query()->whereIn('code', (array) config('survey.respondent_companies', []))->select('id');
        $users = User::query()
            ->whereNotNull('employee_id')
            ->where(fn (EloquentBuilder $query) => $query->whereNotNull('department_id')->orWhereIn('company_id', $respondentCompanies))
            ->get(['id', 'employee_id', 'photo_path', 'photo_updated_at']);

        $cards = $this->latestCards($users->pluck('employee_id')->map(fn (string $id): string => trim($id))->all());

        foreach ($users as $user) {
            $card = $cards->get(trim((string) $user->employee_id));

            if ($card === null) {
                $result[$user->photo_path === null ? 'missing' : 'unchanged']++;

                continue;
            }

            $cardUpdatedAt = $card->LastUpdate === null ? null : Carbon::parse($card->LastUpdate);

            if ($user->photo_path !== null && ($cardUpdatedAt === null || $user->photo_updated_at?->greaterThanOrEqualTo($cardUpdatedAt))) {
                $result['unchanged']++;

                continue;
            }

            $result[$this->store($user, $card->ID, $cardUpdatedAt) ? 'updated' : 'missing']++;
        }

        return $result;
    }

    /**
     * The card to take each employee's photo from, by employee ID.
     *
     * @param  array<int, string>  $employeeIds
     * @return Collection<string, object{ID: mixed, StaffNo: string, LastUpdate: mixed, Del_State: mixed}>
     */
    protected function latestCards(array $employeeIds): Collection
    {
        return collect(array_chunk(array_values(array_unique($employeeIds)), 500))
            ->flatMap(fn (array $chunk) => $this->cards()
                ->select(['ID', DB::raw('LTRIM(RTRIM(StaffNo)) AS StaffNo'), 'LastUpdate', 'Del_State'])
                ->whereIn(DB::raw('LTRIM(RTRIM(StaffNo))'), $chunk)
                ->whereNotNull('Photo')
                ->get())
            ->sortBy([
                fn (object $a, object $b): int => (int) (bool) $a->Del_State <=> (int) (bool) $b->Del_State,
                fn (object $a, object $b): int => strcmp((string) $b->LastUpdate, (string) $a->LastUpdate),
            ])
            ->unique('StaffNo')
            ->keyBy('StaffNo');
    }

    /**
     * Read one card photo and store it as the user's thumbnail.
     */
    protected function store(User $user, mixed $cardId, ?Carbon $cardUpdatedAt): bool
    {
        $photo = $this->cards()->where('ID', $cardId)->value('Photo');
        $thumbnail = is_string($photo) ? FileApps::thumbnail($photo, (int) config('merdeka.employee_photos.size', 160)) : null;

        if ($thumbnail === null) {
            return false;
        }

        $path = self::DIRECTORY.'/'.$user->id.'.webp';
        Storage::disk('local')->put($path, $thumbnail);
        $user->forceFill(['photo_path' => $path, 'photo_updated_at' => $cardUpdatedAt ?? now()])->saveQuietly();

        return true;
    }

    /**
     * A query on the card table.
     */
    protected function cards(): Builder
    {
        /** @var Connection $connection */
        $connection = DB::connection((string) config('merdeka.employee_photos.connection'));

        return $connection->table((string) config('merdeka.employee_photos.table'));
    }
}
