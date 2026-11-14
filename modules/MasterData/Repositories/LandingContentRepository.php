<?php

namespace Modules\MasterData\Repositories;

use App\Helpers\FileApps;
use App\Models\Content\LandingContent;
use App\Models\UserManagement\User;
use Illuminate\Http\UploadedFile;

class LandingContentRepository implements LandingContentInterface
{
    /**
     * {@inheritDoc}
     */
    public function current(): LandingContent
    {
        return LandingContent::current();
    }

    /**
     * {@inheritDoc}
     */
    public function update(array $data, ?UploadedFile $heroImage, bool $removeHeroImage, User $editor): LandingContent
    {
        $content = LandingContent::query()->orderBy('id')->first() ?? new LandingContent(LandingContent::DEFAULTS);

        $content->fill([
            ...$data,
            'hero_image_path' => FileApps::replaceImage(
                $content->hero_image_path,
                $heroImage,
                $removeHeroImage,
                LandingContent::IMAGE_DIRECTORY,
                'hero_image',
            ),
            'updated_by' => $editor->getKey(),
        ])->save();

        return $content;
    }
}
